<?php

namespace App\Service;

use App\Config\EventAction;
use App\Config\TldType;
use App\Entity\Domain;
use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    /**
     * @see https://www.iana.org/domains/root/db
     */
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

    private const IMPORTANT_EVENTS = [EventAction::Deletion->value, EventAction::Expiration->value];
    private const IMPORTANT_STATUS = [
        'redemption period',
        'pending delete',
        'pending create',
        'pending renew',
        'pending restore',
        'pending transfer',
        'pending update',
        'add period',
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
        private StatService $statService
    ) {
    }

    /**
     * Determines if a domain name needs special attention.
     * These domain names are those whose last event was expiration or deletion.
     *
     * @throws \Exception
     */
    public static function isToBeWatchClosely(Domain $domain): bool
    {
        $status = $domain->getStatus();
        if ((!empty($status) && count(array_intersect($status, self::IMPORTANT_STATUS))) || $domain->getDeleted()) {
            return true;
        }

        /** @var DomainEvent[] $events */
        $events = $domain->getEvents()
            ->filter(fn (DomainEvent $e) => $e->getDate() <= new \DateTimeImmutable('now'))
            ->toArray();

        usort($events, fn (DomainEvent $e1, DomainEvent $e2) => $e2->getDate() <=> $e1->getDate());

        return !empty($events) && in_array($events[0]->getAction(), self::IMPORTANT_EVENTS);
    }

    /**
     * @throws HttpExceptionInterface
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws \Throwable
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
     * @throws \Throwable
     */
    public function registerDomain(string $fqdn): Domain
    {
        $idnDomain = strtolower(idn_to_ascii($fqdn));

        $tld = $this->getTld($idnDomain);

        $this->logger->info('An update request for domain name {idnDomain} is requested.', [
            'idnDomain' => $idnDomain,
        ]);

        /** @var RdapServer|null $rdapServer */
        $rdapServer = $this->rdapServerRepository->findOneBy(['tld' => $tld], ['updatedAt' => 'DESC']);

        if (null === $rdapServer) {
            throw new NotFoundHttpException('Unable to determine which RDAP server to contact');
        }

        /** @var ?Domain $domain */
        $domain = $this->domainRepository->findOneBy(['ldhName' => $idnDomain]);

        $rdapServerUrl = $rdapServer->getUrl();

        $this->logger->notice('An RDAP query to update the domain name {idnDomain} will be made to {server}.', [
            'idnDomain' => $idnDomain,
            'server' => $rdapServerUrl,
        ]);

        try {
            $this->statService->incrementStat('stats.rdap_queries.count');

            $res = $this->client->request(
                'GET', $rdapServerUrl.'domain/'.$idnDomain
            )->toArray();
        } catch (\Throwable $e) {
            if ($e instanceof ClientException && 404 === $e->getResponse()->getStatusCode()) {
                if (null !== $domain) {
                    $this->logger->notice('The domain name {idnDomain} has been deleted from the WHOIS database.', [
                        'idnDomain' => $idnDomain,
                    ]);

                    $domain->setDeleted(true)
                        ->updateTimestamps();
                    $this->em->persist($domain);
                    $this->em->flush();
                }

                throw new NotFoundHttpException('The domain name is not present in the WHOIS database.');
            }

            throw $e;
        }

        if (null === $domain) {
            $domain = new Domain();

            $this->logger->info('The domain name {idnDomain} was not known to this Domain Watchdog instance.', [
                'idnDomain' => $idnDomain,
            ]);
        }

        $domain->setTld($tld)->setLdhName($idnDomain)->setDeleted(false);

        if (array_key_exists('status', $res)) {
            $domain->setStatus($res['status']);
        } else {
            $this->logger->warning('The domain name {idnDomain} has no WHOIS status.', [
                'idnDomain' => $idnDomain,
            ]);
        }

        if (array_key_exists('handle', $res)) {
            $domain->setHandle($res['handle']);
        } else {
            $this->logger->warning('The domain name {idnDomain} has no handle key.', [
                'idnDomain' => $idnDomain,
            ]);
        }

        $this->em->persist($domain);
        $this->em->flush();

        /** @var DomainEvent $event */
        foreach ($domain->getEvents()->getIterator() as $event) {
            $event->setDeleted(true);
        }

        if (array_key_exists('events', $res) && is_array($res['events'])) {
            foreach ($res['events'] as $rdapEvent) {
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
                    ->setDeleted(false)
                );
            }
        }

        /** @var DomainEntity $domainEntity */
        foreach ($domain->getDomainEntities()->getIterator() as $domainEntity) {
            $domainEntity->setDeleted(true);
        }

        if (array_key_exists('entities', $res) && is_array($res['entities'])) {
            foreach ($res['entities'] as $rdapEntity) {
                if (!array_key_exists('handle', $rdapEntity) || '' === $rdapEntity['handle']) {
                    continue;
                }

                $entity = $this->registerEntity($rdapEntity);

                $this->em->persist($entity);
                $this->em->flush();

                $domainEntity = $this->domainEntityRepository->findOneBy([
                    'domain' => $domain,
                    'entity' => $entity,
                ]);

                if (null === $domainEntity) {
                    $domainEntity = new DomainEntity();
                }

                $roles = array_map(
                    fn ($e) => $e['roles'],
                    array_filter(
                        $res['entities'],
                        fn ($e) => array_key_exists('handle', $e) && $e['handle'] === $rdapEntity['handle']
                    )
                );

                /*
                 * Flatten the array
                 */
                if (count($roles) !== count($roles, COUNT_RECURSIVE)) {
                    $roles = array_merge(...$roles);
                }

                $domain->addDomainEntity($domainEntity
                    ->setDomain($domain)
                    ->setEntity($entity)
                    ->setRoles($roles)
                    ->setDeleted(false)
                );

                $this->em->persist($domainEntity);
                $this->em->flush();
            }
        }

        if (array_key_exists('nameservers', $res) && is_array($res['nameservers'])) {
            $domain->getNameservers()->clear();

            foreach ($res['nameservers'] as $rdapNameserver) {
                $nameserver = $this->nameserverRepository->findOneBy([
                    'ldhName' => strtolower($rdapNameserver['ldhName']),
                ]);

                $domainNS = $domain->getNameservers()->findFirst(fn (int $key, Nameserver $ns) => $ns->getLdhName() === $rdapNameserver['ldhName']);

                if (null !== $domainNS) {
                    $nameserver = $domainNS;
                }
                if (null === $nameserver) {
                    $nameserver = new Nameserver();
                }

                $nameserver->setLdhName($rdapNameserver['ldhName']);

                if (!array_key_exists('entities', $rdapNameserver) || !is_array($rdapNameserver['entities'])) {
                    $domain->addNameserver($nameserver);
                    continue;
                }

                foreach ($rdapNameserver['entities'] as $rdapEntity) {
                    if (!array_key_exists('handle', $rdapEntity) || '' === $rdapEntity['handle']) {
                        continue;
                    }
                    $entity = $this->registerEntity($rdapEntity);

                    $this->em->persist($entity);
                    $this->em->flush();

                    $nameserverEntity = $this->nameserverEntityRepository->findOneBy([
                        'nameserver' => $nameserver,
                        'entity' => $entity,
                    ]);
                    if (null === $nameserverEntity) {
                        $nameserverEntity = new NameserverEntity();
                    }

                    $roles = array_merge(
                        ...array_map(
                            fn (array $e): array => $e['roles'],
                            array_filter(
                                $rdapNameserver['entities'],
                                fn ($e) => array_key_exists('handle', $e) && $e['handle'] === $rdapEntity['handle']
                            )
                        )
                    );

                    $nameserver->addNameserverEntity($nameserverEntity
                        ->setNameserver($nameserver)
                        ->setEntity($entity)
                        ->setStatus($rdapNameserver['status'])
                        ->setRoles($roles));
                }

                $domain->addNameserver($nameserver);
            }
        } else {
            $this->logger->warning('The domain name {idnDomain} has no nameservers.', [
                'idnDomain' => $idnDomain,
            ]);
        }

        $domain->updateTimestamps();
        $this->em->persist($domain);
        $this->em->flush();

        return $domain;
    }

    private function getTld($domain): ?object
    {
        $lastDotPosition = strrpos($domain, '.');
        if (false === $lastDotPosition) {
            throw new BadRequestException('Domain must contain at least one dot');
        }
        $tld = strtolower(idn_to_ascii(substr($domain, $lastDotPosition + 1)));

        return $this->tldRepository->findOneBy(['tld' => $tld]);
    }

    /**
     * @throws \Exception
     */
    private function registerEntity(array $rdapEntity): Entity
    {
        $entity = $this->entityRepository->findOneBy([
            'handle' => $rdapEntity['handle'],
        ]);

        if (null === $entity) {
            $entity = new Entity();

            $this->logger->info('The entity {handle} was not known to this Domain Watchdog instance.', [
                'handle' => $rdapEntity['handle'],
            ]);
        }

        $entity->setHandle($rdapEntity['handle']);

        if (array_key_exists('vcardArray', $rdapEntity)) {
            if (empty($entity->getJCard())) {
                $entity->setJCard($rdapEntity['vcardArray']);
            } else {
                $properties = [];
                foreach ($rdapEntity['vcardArray'][1] as $prop) {
                    $properties[$prop[0]] = $prop;
                }
                foreach ($entity->getJCard()[1] as $prop) {
                    $properties[$prop[0]] = $prop;
                }
                $entity->setJCard(['vcard', array_values($properties)]);
            }
        }

        if (!array_key_exists('events', $rdapEntity)) {
            return $entity;
        }

        /** @var EntityEvent $event */
        foreach ($entity->getEvents()->getIterator() as $event) {
            $event->setDeleted(true);
        }

        $this->em->persist($entity);
        $this->em->flush();

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

        return $entity;
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
                if ('' === $tld) {
                    continue;
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
                        ->setUpdatedAt(new \DateTimeImmutable(array_key_exists('publication', $dnsRoot) ? $dnsRoot['publication'] : 'now'));

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
            /** @var Tld $gtTldEntity */
            $gtTldEntity = $this->tldRepository->findOneBy(['tld' => $gTld['gTLD']]);

            if (null == $gtTldEntity) {
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
