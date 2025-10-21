<?php

namespace App\Service;

use App\Config\DnsKey\Algorithm;
use App\Config\DnsKey\DigestType;
use App\Config\EventAction;
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
use App\Exception\DomainNotFoundException;
use App\Exception\MalformedDomainException;
use App\Exception\TldNotSupportedException;
use App\Exception\UnknownRdapServerException;
use App\Repository\DomainEntityRepository;
use App\Repository\DomainEventRepository;
use App\Repository\DomainRepository;
use App\Repository\EntityEventRepository;
use App\Repository\EntityRepository;
use App\Repository\IcannAccreditationRepository;
use App\Repository\NameserverEntityRepository;
use App\Repository\NameserverRepository;
use App\Repository\RdapServerRepository;
use App\Repository\TldRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RDAPService
{
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

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly EntityRepository $entityRepository,
        private readonly DomainRepository $domainRepository,
        private readonly DomainEventRepository $domainEventRepository,
        private readonly NameserverRepository $nameserverRepository,
        private readonly NameserverEntityRepository $nameserverEntityRepository,
        private readonly EntityEventRepository $entityEventRepository,
        private readonly DomainEntityRepository $domainEntityRepository,
        private readonly RdapServerRepository $rdapServerRepository,
        private readonly TldRepository $tldRepository,
        private readonly IcannAccreditationRepository $icannAccreditationRepository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly StatService $statService,
        private readonly InfluxdbService $influxService,
        #[Autowire(param: 'influxdb_enabled')]
        private readonly bool $influxdbEnabled,
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DomainNotFoundException
     * @throws DecodingExceptionInterface
     * @throws TldNotSupportedException
     * @throws ClientExceptionInterface
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws MalformedDomainException
     * @throws UnknownRdapServerException
     */
    public function registerDomains(array $domains): void
    {
        foreach ($domains as $fqdn) {
            $this->registerDomain($fqdn);
        }
    }

    /**
     * @throws DomainNotFoundException
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws TldNotSupportedException
     * @throws ClientExceptionInterface
     * @throws OptimisticLockException
     * @throws TransportExceptionInterface
     * @throws MalformedDomainException
     * @throws ServerExceptionInterface
     * @throws UnknownRdapServerException
     * @throws \Exception
     */
    public function registerDomain(string $fqdn): Domain
    {
        $idnDomain = RDAPService::convertToIdn($fqdn);
        $tld = $this->getTld($idnDomain);

        $this->logger->debug('Update request for a domain name is requested', [
            'ldhName' => $idnDomain,
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
            throw DomainNotFoundException::fromDomain($idnDomain);
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

    /**
     * @throws TldNotSupportedException
     * @throws MalformedDomainException
     */
    public function getTld(string $domain): Tld
    {
        if (!str_contains($domain, OfficialDataService::DOMAIN_DOT)) {
            $tldEntity = $this->tldRepository->findOneBy(['tld' => OfficialDataService::DOMAIN_DOT]);

            if (null == $tldEntity) {
                throw TldNotSupportedException::fromTld(OfficialDataService::DOMAIN_DOT);
            }

            return $tldEntity;
        }

        $lastDotPosition = strrpos($domain, OfficialDataService::DOMAIN_DOT);

        if (false === $lastDotPosition) {
            throw MalformedDomainException::fromDomain($domain);
        }

        $tld = self::convertToIdn(substr($domain, $lastDotPosition + 1));
        $tldEntity = $this->tldRepository->findOneBy(['tld' => $tld, 'deletedAt' => null]);

        if (null === $tldEntity) {
            $this->logger->debug('Domain name cannot be updated because the TLD is not supported', [
                'ldhName' => $domain,
            ]);
            throw TldNotSupportedException::fromTld($tld);
        }

        return $tldEntity;
    }

    /**
     * @throws MalformedDomainException
     */
    public static function convertToIdn(string $fqdn): string
    {
        $ascii = strtolower(idn_to_ascii($fqdn));

        if (OfficialDataService::DOMAIN_DOT !== $fqdn && !preg_match('/^(xn--)?[a-z0-9-]+(\.[a-z0-9-]+)*$/', $ascii)) {
            throw MalformedDomainException::fromDomain($fqdn);
        }

        return $ascii;
    }

    /**
     * @throws UnknownRdapServerException
     */
    private function fetchRdapServer(Tld $tld): RdapServer
    {
        $tldString = $tld->getTld();
        $rdapServer = $this->rdapServerRepository->findOneBy(['tld' => $tldString], ['updatedAt' => 'DESC']);

        if (null === $rdapServer) {
            $this->logger->debug('Unable to determine which RDAP server to contact', [
                'tld' => $tldString,
            ]);

            throw UnknownRdapServerException::fromTld($tldString);
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
        $this->logger->info('An RDAP query to update this domain name will be made', [
            'ldhName' => $idnDomain,
            'server' => $rdapServerUrl,
        ]);

        try {
            $req = $this->client->request('GET', $rdapServerUrl.'domain/'.$idnDomain);
            $this->statService->incrementStat('stats.rdap_queries.count');

            return $req->toArray();
        } catch (\Exception $e) {
            throw $this->handleRdapException($e, $idnDomain, $domain, $req ?? null);
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
    private function handleRdapException(\Exception $e, string $idnDomain, ?Domain $domain, ?ResponseInterface $response): \Exception
    {
        if (
            ($e instanceof ClientException && Response::HTTP_NOT_FOUND === $e->getResponse()->getStatusCode())
            || ($e instanceof TransportExceptionInterface && null !== $response && !in_array('content-length', $response->getHeaders(false)) && 404 === $response->getStatusCode())
        ) {
            if (null !== $domain) {
                $this->logger->info('Domain name has been deleted from the WHOIS database', [
                    'ldhName' => $idnDomain,
                ]);

                $domain->updateTimestamps();

                if (!$domain->getDeleted() && $domain->getUpdatedAt() !== $domain->getCreatedAt()) {
                    $this->em->persist((new DomainStatus())
                        ->setDomain($domain)
                        ->setCreatedAt($domain->getUpdatedAt())
                        ->setDate($domain->getUpdatedAt())
                        ->setAddStatus([])
                        ->setDeleteStatus($domain->getStatus()));
                }

                $domain->setDeleted(true);
                $this->em->persist($domain);
                $this->em->flush();
            }

            throw DomainNotFoundException::fromDomain($idnDomain);
        }

        $this->logger->error('Unable to perform an RDAP query for this domain name', [
            'ldhName' => $idnDomain,
        ]);

        return $e;
    }

    private function initNewDomain(string $idnDomain, Tld $tld): Domain
    {
        $domain = new Domain();

        $this->logger->debug('Domain name was not known to this instance', [
            'ldhName' => $idnDomain,
        ]);

        return $domain->setTld($tld)->setLdhName($idnDomain)->setDeleted(false);
    }

    private function updateDomainStatus(Domain $domain, array $rdapData): void
    {
        if (isset($rdapData['status']) && is_array($rdapData['status'])) {
            $status = array_map(fn ($s) => strtolower($s), array_unique($rdapData['status']));
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
            $this->logger->warning('Domain name has no WHOIS status', [
                'ldhName' => $domain->getLdhName(),
            ]);
        }
    }

    private function updateDomainHandle(Domain $domain, array $rdapData): void
    {
        if (isset($rdapData['handle'])) {
            $domain->setHandle($rdapData['handle']);
        } else {
            $this->logger->warning('Domain name has no handle key', [
                'ldhName' => $domain->getLdhName(),
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    private function updateDomainEvents(Domain $domain, array $rdapData): void
    {
        $this->domainEventRepository->setDomainEventAsDeleted($domain);

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
                } else {
                    // at this point Doctrine doesn't know that the events are
                    // deleted in the database, so refresh in order to make the diff work
                    $this->em->refresh($event);
                }

                $domain->addEvent($event
                    ->setAction(strtolower($rdapEvent['eventAction']))
                    ->setDate(new \DateTimeImmutable($rdapEvent['eventDate']))
                    ->setDeleted(false));

                $this->em->persist($domain);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function updateDomainEntities(Domain $domain, array $rdapData): void
    {
        $this->domainEntityRepository->setDomainEntityAsDeleted($domain);

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
            } else {
                $this->em->refresh($domainEntity);
            }

            $domain->addDomainEntity($domainEntity
                ->setDomain($domain)
                ->setEntity($entity)
                ->setRoles($roles)
                ->setDeletedAt(null));

            $this->em->persist($domainEntity);
            $this->em->flush();
        }
    }

    /**
     * @throws \Exception
     */
    private function updateDomainNameservers(Domain $domain, array $rdapData): void
    {
        if (isset($rdapData['nameservers']) && is_array($rdapData['nameservers'])) {
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
            $this->logger->warning('Domain name has no nameservers', [
                'ldhName' => $domain->getLdhName(),
            ]);
        }
    }

    private function fetchOrCreateNameserver(array $rdapNameserver, Domain $domain): Nameserver
    {
        $ldhName = strtolower(rtrim($rdapNameserver['ldhName'], '.'));
        $nameserver = $this->nameserverRepository->findOneBy([
            'ldhName' => $ldhName,
        ]);

        $existingDomainNS = $domain->getNameservers()->findFirst(fn (int $key, Nameserver $ns) => $ns->getLdhName() === $ldhName);

        if (null !== $existingDomainNS) {
            return $existingDomainNS;
        } elseif (null === $nameserver) {
            $nameserver = new Nameserver();
        }

        $nameserver->setLdhName($ldhName);

        return $nameserver;
    }

    /**
     * @throws \Exception
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
                ->setStatus(array_map(fn ($s) => strtolower($s), array_unique($rdapNameserver['status'])))
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

        return array_map(fn ($x) => strtolower($x), $roles);
    }

    /**
     * @throws \Exception
     */
    private function registerEntity(array $rdapEntity, array $roles, string $domain, Tld $tld): Entity
    {
        /*
         * If there is no number to identify the entity, one is generated from the domain name and the roles associated with this entity
         */
        if (!isset($rdapEntity['handle']) || '' === $rdapEntity['handle'] || in_array($rdapEntity['handle'], self::ENTITY_HANDLE_BLACKLIST)) {
            sort($roles);
            $rdapEntity['handle'] = 'DW-FAKEHANDLE-'.$domain.'-'.implode(',', $roles);

            $this->logger->warning('Entity has no handle key', [
                'handle' => $rdapEntity['handle'],
                'ldhName' => $domain,
            ]);
        }

        $entity = $this->entityRepository->findOneBy([
            'handle' => $rdapEntity['handle'],
            'tld' => $tld,
        ]);

        if (null === $entity) {
            $entity = (new Entity())->setTld($tld);

            $this->logger->debug('Entity was not known to this instance', [
                'handle' => $rdapEntity['handle'],
                'ldhName' => $domain,
            ]);
        }

        /**
         * If the RDAP server transmits the entity's IANA number, it is used as a priority to identify the entity.
         *
         * @see https://datatracker.ietf.org/doc/html/rfc7483#section-4.8
         */
        $icannAccreditation = null;
        if (isset($rdapEntity['publicIds'])) {
            foreach ($rdapEntity['publicIds'] as $publicId) {
                if ('IANA Registrar ID' === $publicId['type'] && isset($publicId['identifier']) && '' !== $publicId['identifier']) {
                    $icannAccreditation = $this->icannAccreditationRepository->findOneBy([
                        'id' => (int) $publicId['identifier'],
                    ]);
                }
            }
        }

        $entity->setHandle($rdapEntity['handle'])->setIcannAccreditation($icannAccreditation);

        if (isset($rdapEntity['remarks']) && is_array($rdapEntity['remarks'])) {
            $entity->setRemarks($rdapEntity['remarks']);
        }

        if (isset($rdapEntity['vcardArray'])) {
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

        if (isset($rdapEntity['events'])) {
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
                        ->setAction(strtolower($rdapEntityEvent['eventAction']))
                        ->setDate(new \DateTimeImmutable($rdapEntityEvent['eventDate']))
                        ->setDeleted(false));
            }
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
                    try {
                        $blob = hex2bin($rdapDsData['digest']);
                    } catch (\Exception) {
                        $this->logger->warning('DNSSEC digest is not a valid hexadecimal value', [
                            'ldhName' => $domain,
                            'value' => $rdapDsData['digest'],
                        ]);
                        continue;
                    }

                    if (false === $blob) {
                        $this->logger->warning('DNSSEC digest is not a valid hexadecimal value', [
                            'ldhName' => $domain,
                            'value' => $rdapDsData['digest'],
                        ]);
                        continue;
                    }
                    $dsData->setDigest($blob);
                }
                if (array_key_exists('digestType', $rdapDsData)) {
                    $dsData->setDigestType(DigestType::from($rdapDsData['digestType']));
                }

                $digestLengthByte = [
                    DigestType::SHA1->value => 20,
                    DigestType::SHA256->value => 32,
                    DigestType::GOST_R_34_11_94->value => 32,
                    DigestType::SHA384->value => 48,
                    DigestType::GOST_R_34_11_2012->value => 64,
                    DigestType::SM3->value => 32,
                ];

                if (array_key_exists($dsData->getDigestType()->value, $digestLengthByte)
                    && strlen($dsData->getDigest()) / 2 !== $digestLengthByte[$dsData->getDigestType()->value]) {
                    $this->logger->warning('DNSSEC digest does not have a valid length according to the digest type', [
                        'ldhName' => $domain,
                        'value' => $dsData->getDigest(),
                        'type' => $dsData->getDigestType()->name,
                    ]);
                    continue;
                }

                $domain->addDnsKey($dsData);
                $this->em->persist($dsData);
            }
        } else {
            $this->logger->warning('Domain name has no DS record', [
                'ldhName' => $domain->getLdhName(),
            ]);
        }
    }
}
