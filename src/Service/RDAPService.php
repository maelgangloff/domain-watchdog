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
    const ISO_TLD_EXCEPTION = ['ac', 'eu', 'uk', 'su', 'tp'];
    const INFRA_TLD = ['arpa'];
    const SPONSORED_TLD = ['edu', 'gov', 'int', 'mil'];
    const TEST_TLD = [
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
        'xn--hlcj6aya9esc7a'
    ];

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

        $domain->setTld($tld)->setLdhName($res['ldhName']);

        if (array_key_exists('status', $res)) $domain->setStatus($res['status']);
        if (array_key_exists('handle', $res)) $domain->setHandle($res['handle']);

        $this->em->persist($domain);
        $this->em->flush();

        foreach ($res['events'] as $rdapEvent) {
            if ($rdapEvent['eventAction'] === EventAction::LastUpdateOfRDAPDatabase->value) continue;

            $event = $this->domainEventRepository->findOneBy([
                "action" => $rdapEvent['eventAction'],
                "date" => new DateTimeImmutable($rdapEvent["eventDate"]),
                "domain" => $domain
            ]);

            if ($event === null) $event = new DomainEvent();
            $domain->addEvent($event
                ->setAction($rdapEvent['eventAction'])
                ->setDate(new DateTimeImmutable($rdapEvent['eventDate'])));

        }

        if (array_key_exists('entities', $res) && is_array($res['entities'])) {

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

                $roles = array_map(
                    fn($e) => $e['roles'],
                    array_filter(
                        $res['entities'],
                        fn($e) => array_key_exists('handle', $e) && $e['handle'] === $rdapEntity['handle']
                    )
                );

                if (count($roles) !== count($roles, COUNT_RECURSIVE)) $roles = array_merge(...$roles);

                $domain->addDomainEntity($domainEntity
                    ->setDomain($domain)
                    ->setEntity($entity)
                    ->setRoles($roles));

                $this->em->persist($domainEntity);
                $this->em->flush();
            }
        }

        if (array_key_exists('nameservers', $res) && is_array($res['nameservers'])) {
            foreach ($res['nameservers'] as $rdapNameserver) {
                $nameserver = $this->nameserverRepository->findOneBy([
                    "ldhName" => strtolower($rdapNameserver['ldhName'])
                ]);
                if ($nameserver === null) $nameserver = new Nameserver();

                $nameserver->setLdhName($rdapNameserver['ldhName']);

                if (!array_key_exists('entities', $rdapNameserver) || !is_array($rdapNameserver['entities'])) {
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

                    $roles = array_merge(
                        ...array_map(
                            fn(array $e): array => $e['roles'],
                            array_filter(
                                $rdapNameserver['entities'],
                                fn($e) => array_key_exists('handle', $e) && $e['handle'] === $rdapEntity['handle']
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
                $entity->setJCard(["vcard", array_values($properties)]);
            }
        }

        if (!array_key_exists('events', $rdapEntity)) return $entity;

        foreach ($rdapEntity['events'] as $rdapEntityEvent) {
            $eventAction = $rdapEntityEvent["eventAction"];
            if ($eventAction === EventAction::LastChanged->value || $eventAction === EventAction::LastUpdateOfRDAPDatabase->value) continue;
            $event = $this->entityEventRepository->findOneBy([
                "action" => $rdapEntityEvent["eventAction"],
                "date" => new DateTimeImmutable($rdapEntityEvent["eventDate"])
            ]);

            if ($event !== null) continue;
            $entity->addEvent(
                (new EntityEvent())
                    ->setEntity($entity)
                    ->setAction($rdapEntityEvent["eventAction"])
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
                    $server = $this->rdapServerRepository->findOneBy(["tld" => $tldReference, "url" => $rdapServerUrl]);
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

        foreach ($tldList as $tld) {
            if ($tld === "") continue;
            $tldEntity = $this->tldRepository->findOneBy(['tld' => $tld]);
            if ($tldEntity === null) $tldEntity = new Tld();

            if ($tldEntity->getType() === null) {
                $type = $this->getTldType($tld);
                if ($type !== null) {
                    $tldEntity->setType($type);
                } elseif ($tldEntity->isContractTerminated() === null) {
                    $tldEntity->setType(TldType::ccTLD);
                } else {
                    $tldEntity->setType(TldType::gTLD);
                }
            }

            $this->em->persist($tldEntity);
        }
        $this->em->flush();
    }

    private function getTldType(string $tld): ?TldType
    {

        if (in_array($tld, self::ISO_TLD_EXCEPTION)) return TldType::ccTLD;
        if (in_array(strtolower($tld), self::INFRA_TLD)) return TldType::iTLD;
        if (in_array(strtolower($tld), self::SPONSORED_TLD)) return TldType::sTLD;
        if (in_array(strtolower($tld), self::TEST_TLD)) return TldType::tTLD;

        return null;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws Exception
     * @throws ORMException
     */
    public function updateGTldListICANN(): void
    {
        $gTldList = $this->client->request(
            'GET', 'https://www.icann.org/resources/registries/gtlds/v2/gtlds.json'
        )->toArray()['gTLDs'];

        foreach ($gTldList as $gTld) {
            if ($gTld['gTLD'] === "") continue;
            /** @var Tld $gtTldEntity */
            $gtTldEntity = $this->em->getReference(Tld::class, $gTld['gTLD']);

            $gtTldEntity
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