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
use App\Repository\DomainEntityRepository;
use App\Repository\DomainEventRepository;
use App\Repository\DomainRepository;
use App\Repository\EntityEventRepository;
use App\Repository\EntityRepository;
use App\Repository\NameserverEntityRepository;
use App\Repository\NameserverRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
                                private EntityManagerInterface     $em,
                                private ParameterBagInterface      $params
    )
    {

    }

    /**
     * @throws Exception
     */
    public function registerDomains(array $domains): void
    {
        $dnsRoot = json_decode(file_get_contents($this->params->get('kernel.project_dir') . '/src/Config/dns.json'))->services;
        foreach ($domains as $fqdn) {
            $this->registerDomain($dnsRoot, $fqdn);
        }
    }

    /**
     * @throws Exception
     */
    private function registerDomain(array $dnsRoot, string $fqdn): void
    {
        $idnDomain = idn_to_ascii($fqdn);
        try {
            $rdapServer = $this->getRDAPServer($dnsRoot, RDAPService::getTld($idnDomain));
        } catch (Exception) {
            throw new Exception("Unable to determine which RDAP server to contact");
        }

        try {
            $res = $this->client->request(
                'GET', $rdapServer . 'domain/' . $idnDomain
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


        $this->em->persist($domain);
        $this->em->flush();

    }


    /**
     * @throws Exception
     */
    private function getRDAPServer(array $dnsRoot, string $tld)
    {

        foreach ($dnsRoot as $dns) {
            if (in_array($tld, $dns[0])) return $dns[1][0];
        }
        throw new Exception("This TLD ($tld) is not supported");
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
}