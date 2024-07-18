<?php


namespace App\Service;

use App\Config\DomainRole;
use App\Config\EventAction;
use App\Entity\Domain;
use App\Entity\DomainEntity;
use App\Entity\DomainEvent;
use App\Entity\Entity;
use App\Entity\EntityEvent;
use App\Entity\Nameserver;
use App\Entity\NameserverEntity;
use App\Entity\RdapServer;
use App\Repository\DomainEntityRepository;
use App\Repository\DomainEventRepository;
use App\Repository\DomainRepository;
use App\Repository\EntityEventRepository;
use App\Repository\EntityRepository;
use App\Repository\NameserverEntityRepository;
use App\Repository\NameserverRepository;
use App\Repository\RdapServerRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class RDAPService
{

    public function __construct(private HttpClientInterface        $client,
                                private EntityRepository           $entityRepository,
                                private DomainRepository           $domainRepository,
                                private DomainEventRepository      $domainEventRepository,
                                private NameserverRepository       $nameserverRepository,
                                private NameserverEntityRepository $nameserverEntityRepository,
                                private EntityEventRepository      $entityEventRepository,
                                private DomainEntityRepository     $domainEntityRepository,
                                private RdapServerRepository       $rdapServerRepository,
                                private EntityManagerInterface     $em
    )
    {

    }

    /**
     * @throws Exception
     */
    public function registerDomains(array $domains): void
    {
        foreach ($domains as $fqdn) {
            $this->registerDomain($fqdn);
        }
    }

    /**
     * @throws Exception
     */
    private function registerDomain(string $fqdn): void
    {
        $idnDomain = idn_to_ascii($fqdn);

        /** @var RdapServer|null $rdapServer */
        $rdapServer = $this->rdapServerRepository->findOneBy(["tld" => RDAPService::getTld($idnDomain)]);

        if ($rdapServer === null) throw new Exception("Unable to determine which RDAP server to contact");

        try {
            $res = $this->client->request(
                'GET', $rdapServer->getUrl() . 'domain/' . $idnDomain
            )->toArray();
        } catch (Throwable) {
            throw new Exception("Unable to contact RDAP server");
        }

        $domain = $this->domainRepository->findOneBy(["ldhName" => strtolower($res['ldhName'])]);
        if ($domain === null) $domain = new Domain();

        $domain
            ->setLdhName($res['ldhName'])
            ->setHandle($res['handle'])
            ->setStatus($res['status']);


        foreach ($res['events'] as $rdapEvent) {
            $eventAction = EventAction::from($rdapEvent['eventAction']);
            if ($eventAction === EventAction::LastUpdateOfRDAPDatabase) continue;

            $event = $this->domainEventRepository->findOneBy([
                "action" => $eventAction,
                "date" => new DateTimeImmutable($rdapEvent["eventDate"]),
                "domain" => $domain
            ]);

            if ($event === null) $event = new DomainEvent();
            $domain->addEvent($event
                ->setAction($eventAction)
                ->setDate(new DateTimeImmutable($rdapEvent['eventDate'])));

        }

        foreach ($res['entities'] as $rdapEntity) {
            if (!array_key_exists('handle', $rdapEntity)) continue;
            $entity = $this->registerEntity($rdapEntity);

            $this->em->persist($entity);
            $this->em->flush();

            $domainEntity = $this->domainEntityRepository->findOneBy([
                "domain" => $domain,
                "entity" => $entity
            ]);

            if ($domainEntity === null) $domainEntity = new DomainEntity();


            $domain->addDomainEntity($domainEntity
                ->setDomain($domain)
                ->setEntity($entity)
                ->setRoles(array_map(fn($str): DomainRole => DomainRole::from($str), $rdapEntity['roles'])));

            $this->em->persist($domainEntity);
            $this->em->flush();
        }


        foreach ($res['nameservers'] as $rdapNameserver) {
            $nameserver = $this->nameserverRepository->findOneBy([
                "ldhName" => strtolower($rdapNameserver['ldhName'])
            ]);
            if ($nameserver === null) $nameserver = new Nameserver();

            $nameserver->setLdhName($rdapNameserver['ldhName']);

            if (!array_key_exists('entities', $rdapNameserver)) {
                $domain->addNameserver($nameserver);
                continue;
            }

            foreach ($rdapNameserver['entities'] as $rdapEntity) {
                if (!array_key_exists('handle', $rdapEntity)) continue;
                $entity = $this->registerEntity($rdapEntity);

                $this->em->persist($entity);
                $this->em->flush();

                $nameserverEntity = $this->nameserverEntityRepository->findOneBy([
                    "nameserver" => $nameserver,
                    "entity" => $entity
                ]);
                if ($nameserverEntity === null) $nameserverEntity = new NameserverEntity();


                $nameserver->addNameserverEntity($nameserverEntity
                    ->setNameserver($nameserver)
                    ->setEntity($entity)
                    ->setStatus($rdapNameserver['status'])
                    ->setRoles(array_map(fn($str): DomainRole => DomainRole::from($str), $rdapEntity['roles'])));
            }

            $domain->addNameserver($nameserver);
        }

        $domain->updateTimestamps();
        $this->em->persist($domain);
        $this->em->flush();

    }


    /**
     * @throws Exception
     */
    private static function getTld($domain): string
    {
        $lastDotPosition = strrpos($domain, '.');
        if ($lastDotPosition === false) {
            throw new Exception("Domain must contain at least one dot");
        }
        return strtolower(substr($domain, $lastDotPosition + 1));
    }

    /**
     * @throws Exception
     */
    private function registerEntity(array $rdapEntity): Entity
    {
        $entity = $this->entityRepository->findOneBy([
            "handle" => $rdapEntity['handle']
        ]);

        if ($entity === null) $entity = new Entity();

        $entity->setHandle($rdapEntity['handle']);

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
            $entity->setJCard(["vcard", array_values($properties)]);
        }


        if (!array_key_exists('events', $rdapEntity)) return $entity;

        foreach ($rdapEntity['events'] as $rdapEntityEvent) {
            if ($rdapEntityEvent["eventAction"] === EventAction::LastChanged->value) continue;
            $event = $this->entityEventRepository->findOneBy([
                "action" => EventAction::from($rdapEntityEvent["eventAction"]),
                "date" => new DateTimeImmutable($rdapEntityEvent["eventDate"])
            ]);

            if ($event !== null) continue;
            $entity->addEvent(
                (new EntityEvent())
                    ->setEntity($entity)
                    ->setAction(EventAction::from($rdapEntityEvent['eventAction']))
                    ->setDate(new DateTimeImmutable($rdapEntityEvent['eventDate'])));

        }
        return $entity;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateRDAPServers(): void
    {
        $dnsRoot = $this->client->request(
            'GET', 'https://data.iana.org/rdap/dns.json'
        )->toArray();

        foreach ($dnsRoot['services'] as $service) {

            foreach ($service[0] as $tld) {
                foreach ($service[1] as $rdapServerUrl) {
                    $server = $this->rdapServerRepository->findOneBy(["tld" => $tld, "url" => $rdapServerUrl]);
                    if ($server === null) $server = new RdapServer();

                    $server->setTld($tld)->setUrl($rdapServerUrl)->updateTimestamps();

                    $this->em->persist($server);
                }
            }

        }
        $this->em->flush();
    }
}