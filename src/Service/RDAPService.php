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
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
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
                                private TldRepository              $tldRepository,
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
    public function registerDomain(string $fqdn): Domain
    {
        $idnDomain = idn_to_ascii($fqdn);
        $tld = $this->getTld($idnDomain);

        /** @var RdapServer|null $rdapServer */
        $rdapServer = $this->rdapServerRepository->findOneBy(["tld" => $tld], ["updatedAt" => "DESC"]);

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
            ->setTld($tld)
            ->setLdhName($res['ldhName'])
            ->setStatus($res['status']);

        if (array_key_exists('handle', $res)) $domain->setHandle($res['handle']);


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
            if (!array_key_exists('handle', $rdapEntity) || $rdapEntity['handle'] === '') continue;
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
                if (!array_key_exists('handle', $rdapEntity) || $rdapEntity['handle'] === '') continue;
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

        return $domain;
    }


    /**
     * @throws Exception
     */
    private function getTld($domain): ?object
    {
        $lastDotPosition = strrpos($domain, '.');
        if ($lastDotPosition === false) {
            throw new Exception("Domain must contain at least one dot");
        }
        $tld = strtolower(substr($domain, $lastDotPosition + 1));

        return $this->tldRepository->findOneBy(["tld" => $tld]);
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
            $eventAction = $rdapEntityEvent["eventAction"];
            if ($eventAction === EventAction::LastChanged->value || $eventAction === EventAction::LastUpdateOfRDAPDatabase->value) continue;
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
     * @throws ORMException
     */
    public function updateRDAPServers(): void
    {
        $dnsRoot = $this->client->request(
            'GET', 'https://data.iana.org/rdap/dns.json'
        )->toArray();

        foreach ($dnsRoot['services'] as $service) {

            foreach ($service[0] as $tld) {
                if ($tld === "") continue;
                $tldReference = $this->em->getReference(Tld::class, $tld);
                foreach ($service[1] as $rdapServerUrl) {
                    $server = $this->rdapServerRepository->findOneBy(["tld" => $tldReference, "url" => $rdapServerUrl]); //ICI
                    if ($server === null) $server = new RdapServer();
                    $server->setTld($tldReference)->setUrl($rdapServerUrl)->updateTimestamps();

                    $this->em->persist($server);
                }
            }

        }
        $this->em->flush();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function updateTldListIANA(): void
    {
        $tldList = array_map(
            fn($tld) => strtolower($tld),
            explode(PHP_EOL,
                $this->client->request(
                    'GET', 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt'
                )->getContent()
            ));
        array_shift($tldList);
        $storedTldList = array_map(fn($tld) => $tld->getTld(), $this->tldRepository->findAll());


        foreach (array_diff($tldList, $storedTldList) as $tld) {
            if ($tld === "") continue;
            $this->em->persist((new Tld())->setTld($tld));
        }
        $this->em->flush();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws Exception
     */
    public function updateGTldListICANN(): void
    {
        $gTldList = $this->client->request(
            'GET', 'https://www.icann.org/resources/registries/gtlds/v2/gtlds.json'
        )->toArray()['gTLDs'];

        foreach ($gTldList as $gTld) {
            if ($gTld['gTLD'] === "") continue;
            $gtTldEntity = $this->tldRepository->findOneBy(['tld' => $gTld['gTLD']]);
            if ($gtTldEntity === null) $gtTldEntity = new Tld();

            $gtTldEntity
                ->setTld($gTld['gTLD'])
                ->setContractTerminated($gTld['contractTerminated'])
                ->setRegistryOperator($gTld['registryOperator'])
                ->setSpecification13($gTld['specification13']);
            if ($gTld['removalDate'] !== null) $gtTldEntity->setRemovalDate(new DateTimeImmutable($gTld['removalDate']));
            if ($gTld['delegationDate'] !== null) $gtTldEntity->setDelegationDate(new DateTimeImmutable($gTld['delegationDate']));
            if ($gTld['dateOfContractSignature'] !== null) $gtTldEntity->setDateOfContractSignature(new DateTimeImmutable($gTld['dateOfContractSignature']));
            $this->em->persist($gtTldEntity);
        }
        $this->em->flush();
    }
}