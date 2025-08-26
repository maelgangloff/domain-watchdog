<?php

namespace App\Service;

use App\Config\DnsKey\Algorithm;
use App\Config\DnsKey\DigestType;
use App\Config\EventAction;
use App\Config\TldType;
use App\Entity\DnsKey;
use App\Entity\Domain;
use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
use App\Entity\DomainStatus;
use App\Entity\Entity;
use App\Entity\EntityEvent;
use App\Entity\Nameserver;
use App\Entity\NameserverEntity;
use App\Entity\RdapServer;
use App\Entity\Tld;
use App\Repository\DomainEntityRepository;
use App\Repository\DomainEventRepository;
use App\Repository\DomainRepository;
use App\Repository\EntityEventRepository;
use App\Repository\EntityRepository;
use App\Repository\NameserverEntityRepository;
use App\Repository\NameserverRepository;
use App\Repository\RdapServerRepository;
use App\Repository\TldRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class RDAPService
{
    /* @see https://www.iana.org/domains/root/db */
    public const ISO_TLD_EXCEPTION = ['ac', 'eu', 'uk', 'su', 'tp'];
    public const INFRA_TLD = ['arpa'];
    public const SPONSORED_TLD = [
        'aero',
        'asia',
        'cat',
        'coop',
        'edu',
        'gov',
        'int',
        'jobs',
        'mil',
        'museum',
        'post',
        'tel',
        'travel',
        'xxx',
    ];
    public const TEST_TLD = [
        'xn--kgbechtv',
        'xn--hgbk6aj7f53bba',
        'xn--0zwm56d',
        'xn--g6w251d',
        'xn--80akhbyknj4f',
        'xn--11b5bs3a9aj6g',
        'xn--jxalpdlp',
        'xn--9t4b11yi5a',
        'xn--deba0ad',
        'xn--zckzah',
        'xn--hlcj6aya9esc7a',
    ];

    public const ENTITY_HANDLE_BLACKLIST = [
        'REDACTED_FOR_PRIVACY',
        'ANO00-FRNIC',
        'not applicable',
        'REDACTED FOR PRIVACY-REGISTRANT',
        'REDACTED FOR PRIVACY-TECH',
        'REDACTED FOR PRIVACY',
        'REDACTED-SIDN',
        'REGISTRANT',
        'REGISTRAR',
        'ABUSE-CONTACT',
        'None',
        'Private',
    ];

    /* @see https://www.iana.org/assignments/registrar-ids/registrar-ids.xhtml */
    public const IANA_RESERVED_IDS = [
        1, 3, 8, 119, 365, 376, 9994, 9995, 9996, 9997, 9998, 9999, 10009, 4000001, 8888888,
    ];

    public function __construct(private HttpClientInterface $client,
        private EntityRepository $entityRepository,
        private DomainRepository $domainRepository,
        private DomainEventRepository $domainEventRepository,
        private NameserverRepository $nameserverRepository,
        private NameserverEntityRepository $nameserverEntityRepository,
        private EntityEventRepository $entityEventRepository,
        private DomainEntityRepository $domainEntityRepository,
        private RdapServerRepository $rdapServerRepository,
        private TldRepository $tldRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private StatService $statService,
        private InfluxdbService $influxService,
        #[Autowire(param: 'influxdb_enabled')]
        private bool $influxdbEnabled,
    ) {
    }

    /**
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function registerDomains(array $domains): void
    {
        foreach ($domains as $fqdn) {
            $this->registerDomain($fqdn);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function registerDomain(string $fqdn): Domain
    {
        $idnDomain = RDAPService::convertToIdn($fqdn);
        $tld = $this->getTld($idnDomain);

        $this->logger->info('An update request for domain name {idnDomain} is requested.', [
            'idnDomain' => $idnDomain,
        ]);

        $rdapServer = $this->fetchRdapServer($tld);
        $domain = $this->domainRepository->findOneBy(['ldhName' => $idnDomain]);

        $rdapData = $this->fetchRdapResponse($rdapServer, $idnDomain, $domain);
        $this->em->beginTransaction();

        if (null === $domain) {
            $domain = $this->initNewDomain($idnDomain, $tld);
            $this->em->persist($domain);
        }

        $this->em->lock($domain, LockMode::PESSIMISTIC_WRITE);

        $this->updateDomainStatus($domain, $rdapData);

        if (in_array('free', $domain->getStatus())) {
            throw new NotFoundHttpException("The domain name $idnDomain is not present in the WHOIS database.");
        }

        $domain
            ->setRdapServer($rdapServer)
            ->setDelegationSigned(isset($rdapData['secureDNS']['delegationSigned']) && $rdapData['secureDNS']['delegationSigned']);

        $this->updateDomainHandle($domain, $rdapData);

        $this->updateDomainEvents($domain, $rdapData);
        $this->updateDomainEntities($domain, $rdapData);
        $this->updateDomainNameservers($domain, $rdapData);
        $this->updateDomainDsData($domain, $rdapData);

        $domain->setDeleted(false)->updateTimestamps();

        $this->em->flush();
        $this->em->commit();

        return $domain;
    }

    public function getTld(string $domain): Tld
    {
        if (!str_contains($domain, '.')) {
            $tldEntity = $this->tldRepository->findOneBy(['tld' => '.']);

            if (null == $tldEntity) {
                throw new NotFoundHttpException("The requested TLD $domain is not yet supported, please try again with another one");
            }

            return $tldEntity;
        }

        $lastDotPosition = strrpos($domain, '.');

        if (false === $lastDotPosition) {
            throw new BadRequestException('Domain must contain at least one dot');
        }

        $tld = strtolower(idn_to_ascii(substr($domain, $lastDotPosition + 1)));
        $tldEntity = $this->tldRepository->findOneBy(['tld' => $tld]);

        if (null === $tldEntity) {
            throw new NotFoundHttpException("The requested TLD $tld is not yet supported, please try again with another one");
        }

        return $tldEntity;
    }

    public static function convertToIdn(string $fqdn): string
    {
        return strtolower(idn_to_ascii($fqdn));
    }

    private function fetchRdapServer(Tld $tld): RdapServer
    {
        $tldString = $tld->getTld();
        $rdapServer = $this->rdapServerRepository->findOneBy(['tld' => $tldString], ['updatedAt' => 'DESC']);

        if (null === $rdapServer) {
            throw new NotFoundHttpException("TLD $tldString : Unable to determine which RDAP server to contact");
        }

        return $rdapServer;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    private function fetchRdapResponse(RdapServer $rdapServer, string $idnDomain, ?Domain $domain): array
    {
        $rdapServerUrl = $rdapServer->getUrl();
        $this->logger->notice('An RDAP query to update the domain name {idnDomain} will be made to {server}.', [
            'idnDomain' => $idnDomain,
            'server' => $rdapServerUrl,
        ]);

        try {
            $this->statService->incrementStat('stats.rdap_queries.count');

            $req = $this->client->request('GET', $rdapServerUrl.'domain/'.$idnDomain);

            return $req->toArray();
        } catch (\Exception $e) {
            throw $this->handleRdapException($e, $idnDomain, $domain);
        } finally {
            if ($this->influxdbEnabled && isset($req)) {
                $this->influxService->addRdapQueryPoint($rdapServer, $idnDomain, $req->getInfo());
            }
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    private function handleRdapException(\Exception $e, string $idnDomain, ?Domain $domain): \Exception
    {
        if ($e instanceof ClientException && 404 === $e->getResponse()->getStatusCode()) {
            if (null !== $domain) {
                $this->logger->notice('The domain name {idnDomain} has been deleted from the WHOIS database.', [
                    'idnDomain' => $idnDomain,
                ]);

                $domain->setDeleted(true)->updateTimestamps();
                $this->em->persist($domain);
                $this->em->flush();
            }

            throw new NotFoundHttpException("The domain name $idnDomain is not present in the WHOIS database.");
        }

        return $e;
    }

    private function initNewDomain(string $idnDomain, Tld $tld): Domain
    {
        $domain = new Domain();

        $this->logger->info('The domain name {idnDomain} was not known to this Domain Watchdog instance.', [
            'idnDomain' => $idnDomain,
        ]);

        return $domain->setTld($tld)->setLdhName($idnDomain)->setDeleted(false);
    }

    private function updateDomainStatus(Domain $domain, array $rdapData): void
    {
        if (isset($rdapData['status'])) {
            $status = array_unique($rdapData['status']);
            $addedStatus = array_diff($status, $domain->getStatus());
            $deletedStatus = array_diff($domain->getStatus(), $status);
            $domain->setStatus($status);

            if (count($addedStatus) > 0 || count($deletedStatus) > 0) {
                $this->em->persist($domain);

                if ($domain->getUpdatedAt() !== $domain->getCreatedAt()) {
                    $this->em->persist((new DomainStatus())
                        ->setDomain($domain)
                        ->setCreatedAt(new \DateTimeImmutable('now'))
                        ->setDate($domain->getUpdatedAt())
                        ->setAddStatus($addedStatus)
                        ->setDeleteStatus($deletedStatus));
                }
            }
        } else {
            $this->logger->warning('The domain name {idnDomain} has no WHOIS status.', [
                'idnDomain' => $domain->getLdhName(),
            ]);
        }
    }

    private function updateDomainHandle(Domain $domain, array $rdapData): void
    {
        if (isset($rdapData['handle'])) {
            $domain->setHandle($rdapData['handle']);
        } else {
            $this->logger->warning('The domain name {idnDomain} has no handle key.', [
                'idnDomain' => $domain->getLdhName(),
            ]);
        }
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    private function updateDomainEvents(Domain $domain, array $rdapData): void
    {
        foreach ($domain->getEvents()->getIterator() as $event) {
            $event->setDeleted(true);
        }

        if (isset($rdapData['events']) && is_array($rdapData['events'])) {
            foreach ($rdapData['events'] as $rdapEvent) {
                if ($rdapEvent['eventAction'] === EventAction::LastUpdateOfRDAPDatabase->value) {
                    continue;
                }

                $event = $this->domainEventRepository->findOneBy([
                    'action' => $rdapEvent['eventAction'],
                    'date' => new \DateTimeImmutable($rdapEvent['eventDate']),
                    'domain' => $domain,
                ]);

                if (null === $event) {
                    $event = new DomainEvent();
                }

                $domain->addEvent($event
                    ->setAction($rdapEvent['eventAction'])
                    ->setDate(new \DateTimeImmutable($rdapEvent['eventDate']))
                    ->setDeleted(false));

                $this->em->persist($domain);
            }
        }
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    private function updateDomainEntities(Domain $domain, array $rdapData): void
    {
        foreach ($domain->getDomainEntities()->getIterator() as $domainEntity) {
            $domainEntity->setDeleted(true);
        }

        if (!isset($rdapData['entities']) || !is_array($rdapData['entities'])) {
            return;
        }

        foreach ($rdapData['entities'] as $rdapEntity) {
            $roles = $this->extractEntityRoles($rdapData['entities'], $rdapEntity);
            $entity = $this->registerEntity($rdapEntity, $roles, $domain->getLdhName(), $domain->getTld());

            $domainEntity = $this->domainEntityRepository->findOneBy([
                'domain' => $domain,
                'entity' => $entity,
            ]);

            if (null === $domainEntity) {
                $domainEntity = new DomainEntity();
            }

            $domain->addDomainEntity($domainEntity
                ->setDomain($domain)
                ->setEntity($entity)
                ->setRoles($roles)
                ->setDeleted(false));

            $this->em->persist($domainEntity);
            $this->em->flush();
        }
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function updateDomainNameservers(Domain $domain, array $rdapData): void
    {
        if (array_key_exists('nameservers', $rdapData) && is_array($rdapData['nameservers'])) {
            $domain->getNameservers()->clear();
            $this->em->persist($domain);

            foreach ($rdapData['nameservers'] as $rdapNameserver) {
                $nameserver = $this->fetchOrCreateNameserver($rdapNameserver, $domain);
                $this->updateNameserverEntities($nameserver, $rdapNameserver, $domain->getTld());

                if (!$domain->getNameservers()->contains($nameserver)) {
                    $domain->addNameserver($nameserver);
                }
            }
        } else {
            $this->logger->warning('The domain name {idnDomain} has no nameservers.', [
                'idnDomain' => $domain->getLdhName(),
            ]);
        }
    }

    private function fetchOrCreateNameserver(array $rdapNameserver, Domain $domain): Nameserver
    {
        $nameserver = $this->nameserverRepository->findOneBy([
            'ldhName' => strtolower($rdapNameserver['ldhName']),
        ]);

        $existingDomainNS = $domain->getNameservers()->findFirst(fn (int $key, Nameserver $ns) => $ns->getLdhName() === $rdapNameserver['ldhName']);

        if (null !== $existingDomainNS) {
            return $existingDomainNS;
        } elseif (null === $nameserver) {
            $nameserver = new Nameserver();
        }

        $nameserver->setLdhName($rdapNameserver['ldhName']);

        return $nameserver;
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function updateNameserverEntities(Nameserver $nameserver, array $rdapNameserver, Tld $tld): void
    {
        if (!isset($rdapNameserver['entities']) || !is_array($rdapNameserver['entities'])) {
            return;
        }

        foreach ($rdapNameserver['entities'] as $rdapEntity) {
            $roles = $this->extractEntityRoles($rdapNameserver['entities'], $rdapEntity);
            $entity = $this->registerEntity($rdapEntity, $roles, $nameserver->getLdhName(), $tld);

            $nameserverEntity = $this->nameserverEntityRepository->findOneBy([
                'nameserver' => $nameserver,
                'entity' => $entity,
            ]);

            if (null === $nameserverEntity) {
                $nameserverEntity = new NameserverEntity();
            }

            $nameserver->addNameserverEntity($nameserverEntity
                ->setNameserver($nameserver)
                ->setEntity($entity)
                ->setStatus(array_unique($rdapNameserver['status']))
                ->setRoles($roles));

            $this->em->persist($nameserverEntity);
            $this->em->flush();
        }
    }

    private function extractEntityRoles(array $entities, array $targetEntity): array
    {
        $roles = array_map(
            fn ($e) => $e['roles'],
            array_filter(
                $entities,
                fn ($e) => isset($targetEntity['handle']) && isset($e['handle'])
                    ? $targetEntity['handle'] === $e['handle']
                    : (
                        isset($targetEntity['vcardArray']) && isset($e['vcardArray'])
                                ? $targetEntity['vcardArray'] === $e['vcardArray']
                                : $targetEntity === $e
                    )
            )
        );

        if (count($roles) !== count($roles, COUNT_RECURSIVE)) {
            $roles = array_merge(...$roles);
        }

        return $roles;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    private function registerEntity(array $rdapEntity, array $roles, string $domain, Tld $tld): Entity
    {
        /*
         * If the RDAP server transmits the entity's IANA number, it is used as a priority to identify the entity
         */
        $isIANAid = false;
        if (isset($rdapEntity['publicIds'])) {
            foreach ($rdapEntity['publicIds'] as $publicId) {
                if ('IANA Registrar ID' === $publicId['type'] && isset($publicId['identifier']) && '' !== $publicId['identifier']) {
                    $rdapEntity['handle'] = $publicId['identifier'];
                    $isIANAid = true;
                    break;
                }
            }
        }

        /*
         * If there is no number to identify the entity, one is generated from the domain name and the roles associated with this entity
         */
        if (!isset($rdapEntity['handle']) || '' === $rdapEntity['handle'] || in_array($rdapEntity['handle'], self::ENTITY_HANDLE_BLACKLIST)) {
            sort($roles);
            $rdapEntity['handle'] = 'DW-FAKEHANDLE-'.$domain.'-'.implode(',', $roles);

            $this->logger->warning('The entity {handle} has no handle key.', [
                'handle' => $rdapEntity['handle'],
            ]);
        }

        $entity = $this->entityRepository->findOneBy([
            'handle' => $rdapEntity['handle'],
            'tld' => is_numeric($rdapEntity['handle']) ? null : $tld,
        ]);

        if (null === $entity) {
            $entity = new Entity();

            $this->logger->info('The entity {handle} was not known to this Domain Watchdog instance.', [
                'handle' => $rdapEntity['handle'],
            ]);
        }

        $entity->setHandle($rdapEntity['handle'])->setTld(is_numeric($rdapEntity['handle']) ? null : $tld);

        if (isset($rdapEntity['remarks']) && is_array($rdapEntity['remarks']) && !is_numeric($rdapEntity['handle'])) {
            $entity->setRemarks($rdapEntity['remarks']);
        }

        if (isset($rdapEntity['vcardArray']) && !in_array($rdapEntity['handle'], self::IANA_RESERVED_IDS)) {
            if (empty($entity->getJCard())) {
                if (!array_key_exists('elements', $rdapEntity['vcardArray'])) {
                    $entity->setJCard($rdapEntity['vcardArray']);
                } else {
                    /*
                     * UZ registry
                     */
                    $entity->setJCard([
                        'vcard',
                        $rdapEntity['vcardArray']['elements'],
                    ]);
                }
            } else {
                $properties = [];
                if (!array_key_exists('elements', $rdapEntity['vcardArray'])) {
                    foreach ($rdapEntity['vcardArray'][1] as $prop) {
                        $properties[$prop[0]] = $prop;
                    }
                } else {
                    /*
                     * UZ registry
                     */
                    foreach ($rdapEntity['vcardArray']['elements'] as $prop) {
                        $properties[$prop[0]] = $prop;
                    }
                }
                foreach ($entity->getJCard()[1] as $prop) {
                    $properties[$prop[0]] = $prop;
                }
                $entity->setJCard(['vcard', array_values($properties)]);
            }
        }

        if ($isIANAid || !isset($rdapEntity['events']) || in_array($rdapEntity['handle'], self::IANA_RESERVED_IDS)) {
            return $entity;
        }

        /** @var EntityEvent $event */
        foreach ($entity->getEvents()->getIterator() as $event) {
            $event->setDeleted(true);
        }

        $this->em->persist($entity);

        foreach ($rdapEntity['events'] as $rdapEntityEvent) {
            $eventAction = $rdapEntityEvent['eventAction'];
            if ($eventAction === EventAction::LastChanged->value || $eventAction === EventAction::LastUpdateOfRDAPDatabase->value) {
                continue;
            }
            $event = $this->entityEventRepository->findOneBy([
                'action' => $rdapEntityEvent['eventAction'],
                'date' => new \DateTimeImmutable($rdapEntityEvent['eventDate']),
            ]);

            if (null !== $event) {
                $event->setDeleted(false);
                continue;
            }
            $entity->addEvent(
                (new EntityEvent())
                    ->setEntity($entity)
                    ->setAction($rdapEntityEvent['eventAction'])
                    ->setDate(new \DateTimeImmutable($rdapEntityEvent['eventDate']))
                    ->setDeleted(false));
        }

        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    private function updateDomainDsData(Domain $domain, array $rdapData): void
    {
        $domain->getDnsKey()->clear();
        $this->em->persist($domain);
        $this->em->flush();

        if (array_key_exists('secureDNS', $rdapData) && array_key_exists('dsData', $rdapData['secureDNS']) && is_array($rdapData['secureDNS']['dsData'])) {
            foreach ($rdapData['secureDNS']['dsData'] as $rdapDsData) {
                $dsData = new DnsKey();
                if (array_key_exists('keyTag', $rdapDsData)) {
                    $dsData->setKeyTag(pack('n', $rdapDsData['keyTag']));
                }
                if (array_key_exists('algorithm', $rdapDsData)) {
                    $dsData->setAlgorithm(Algorithm::from($rdapDsData['algorithm']));
                }
                if (array_key_exists('digest', $rdapDsData)) {
                    $blob = hex2bin($rdapDsData['digest']);
                    if (false === $blob) {
                        throw new ServiceUnavailableHttpException('DNSSEC digest is not a valid hexadecimal value.');
                    }
                    $dsData->setDigest($blob);
                }
                if (array_key_exists('digestType', $rdapDsData)) {
                    $dsData->setDigestType(DigestType::from($rdapDsData['digestType']));
                }

                $domain->addDnsKey($dsData);
                $this->em->persist($dsData);
            }
        } else {
            $this->logger->warning('The domain name {idnDomain} has no DS record.', [
                'idnDomain' => $domain->getLdhName(),
            ]);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ORMException
     */
    public function updateRDAPServersFromIANA(): void
    {
        $this->logger->info('Start of update the RDAP server list from IANA.');

        $dnsRoot = $this->client->request(
            'GET', 'https://data.iana.org/rdap/dns.json'
        )->toArray();

        $this->updateRDAPServers($dnsRoot);
    }

    /**
     * @throws ORMException
     * @throws \Exception
     */
    private function updateRDAPServers(array $dnsRoot): void
    {
        foreach ($dnsRoot['services'] as $service) {
            foreach ($service[0] as $tld) {
                if ('' === $tld && null === $this->tldRepository->findOneBy(['tld' => $tld])) {
                    $this->em->persist((new Tld())->setTld('')->setType(TldType::root));
                    $this->em->flush();
                }

                $tldReference = $this->em->getReference(Tld::class, $tld);

                foreach ($service[1] as $rdapServerUrl) {
                    $server = $this->rdapServerRepository->findOneBy(['tld' => $tldReference, 'url' => $rdapServerUrl]);

                    if (null === $server) {
                        $server = new RdapServer();
                    }

                    $server
                        ->setTld($tldReference)
                        ->setUrl($rdapServerUrl)
                        ->setUpdatedAt(new \DateTimeImmutable($dnsRoot['publication'] ?? 'now'));

                    $this->em->persist($server);
                }
            }
        }
        $this->em->flush();
    }

    /**
     * @throws ORMException
     */
    public function updateRDAPServersFromFile(string $fileName): void
    {
        if (!file_exists($fileName)) {
            return;
        }

        $this->logger->info('Start of update the RDAP server list from custom config file.');
        $this->updateRDAPServers(Yaml::parseFile($fileName));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateTldListIANA(): void
    {
        $this->logger->info('Start of retrieval of the list of TLDs according to IANA.');
        $tldList = array_map(
            fn ($tld) => strtolower($tld),
            explode(PHP_EOL,
                $this->client->request(
                    'GET', 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt'
                )->getContent()
            ));
        array_shift($tldList);

        foreach ($tldList as $tld) {
            if ('' === $tld) {
                continue;
            }

            $tldEntity = $this->tldRepository->findOneBy(['tld' => $tld]);

            if (null === $tldEntity) {
                $tldEntity = new Tld();
                $tldEntity->setTld($tld);

                $this->logger->notice('New TLD detected according to IANA ({tld}).', [
                    'tld' => $tld,
                ]);
            }

            $type = $this->getTldType($tld);

            if (null !== $type) {
                $tldEntity->setType($type);
            } elseif (null === $tldEntity->isContractTerminated()) { // ICANN managed, must be a ccTLD
                $tldEntity->setType(TldType::ccTLD);
            } else {
                $tldEntity->setType(TldType::gTLD);
            }

            $this->em->persist($tldEntity);
        }
        $this->em->flush();
    }

    private function getTldType(string $tld): ?TldType
    {
        if (in_array($tld, self::ISO_TLD_EXCEPTION)) {
            return TldType::ccTLD;
        }
        if (in_array(strtolower($tld), self::INFRA_TLD)) {
            return TldType::iTLD;
        }
        if (in_array(strtolower($tld), self::SPONSORED_TLD)) {
            return TldType::sTLD;
        }
        if (in_array(strtolower($tld), self::TEST_TLD)) {
            return TldType::tTLD;
        }

        return null;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws \Exception
     */
    public function updateGTldListICANN(): void
    {
        $this->logger->info('Start of retrieval of the list of gTLDs according to ICANN.');

        $gTldList = $this->client->request(
            'GET', 'https://www.icann.org/resources/registries/gtlds/v2/gtlds.json'
        )->toArray()['gTLDs'];

        foreach ($gTldList as $gTld) {
            if ('' === $gTld['gTLD']) {
                continue;
            }
            /** @var Tld|null $gtTldEntity */
            $gtTldEntity = $this->tldRepository->findOneBy(['tld' => $gTld['gTLD']]);

            if (null === $gtTldEntity) {
                $gtTldEntity = new Tld();
                $gtTldEntity->setTld($gTld['gTLD']);
                $this->logger->notice('New gTLD detected according to ICANN ({tld}).', [
                    'tld' => $gTld['gTLD'],
                ]);
            }

            $gtTldEntity
                ->setContractTerminated($gTld['contractTerminated'])
                ->setRegistryOperator($gTld['registryOperator'])
                ->setSpecification13($gTld['specification13'])
                ->setType(TldType::gTLD);

            if (null !== $gTld['removalDate']) {
                $gtTldEntity->setRemovalDate(new \DateTimeImmutable($gTld['removalDate']));
            }
            if (null !== $gTld['delegationDate']) {
                $gtTldEntity->setDelegationDate(new \DateTimeImmutable($gTld['delegationDate']));
            }
            if (null !== $gTld['dateOfContractSignature']) {
                $gtTldEntity->setDateOfContractSignature(new \DateTimeImmutable($gTld['dateOfContractSignature']));
            }
            $this->em->persist($gtTldEntity);
        }

        $this->em->flush();
    }
}
