<?php


namespace App\Controller;

use App\Config\DomainRole;
use App\Config\DomainStatus;
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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class TestController extends AbstractController
{

    public function __construct(private HttpClientInterface                 $client,
                                private readonly EntityRepository           $entityRepository,
                                private readonly DomainRepository           $domainRepository,
                                private readonly DomainEventRepository      $domainEventRepository,
                                private readonly NameserverRepository       $nameserverRepository,
                                private readonly NameserverEntityRepository $nameserverEntityRepository,
                                private readonly EntityEventRepository      $entityEventRepository,
                                private readonly DomainEntityRepository     $domainEntityRepository,
                                private readonly EntityManagerInterface     $em)
    {

    }

    #[Route(path: '/test/{fqdn}', name: 'test')]
    public function testRoute(string $fqdn): Response
    {
        $rdapServer = $this->getRdapServer($fqdn);

        $res = $this->client->request(
            'GET', $rdapServer . 'domain/' . $fqdn
        )->toArray();

        $domain = $this->domainRepository->findOneBy(["ldhName" => strtolower($res['ldhName'])]);
        if ($domain === null) $domain = new Domain();

        $domain
            ->setLdhName($res['ldhName'])
            ->setHandle($res['handle'])
            ->setStatus(array_map(fn($str): DomainStatus => DomainStatus::from($str), $res['status']));


        foreach ($res['events'] as $rdapEvent) {
            $event = $this->domainEventRepository->findOneBy([
                "action" => EventAction::from($rdapEvent["eventAction"]),
                "date" => new DateTimeImmutable($rdapEvent["eventDate"]),
                "domain" => $res['handle']
            ]);

            if ($event === null) $event = new DomainEvent();
            $domain->addEvent($event
                ->setAction(EventAction::from($rdapEvent['eventAction']))
                ->setDate(new DateTimeImmutable($rdapEvent['eventDate'])));

        }

        foreach ($res['entities'] as $rdapEntity) {
            if (!array_key_exists('handle', $rdapEntity)) continue;
            $entity = $this->processEntity($rdapEntity);

            $domainEntity = $this->domainEntityRepository->findOneBy([
                "domain" => $domain,
                "entity" => $entity
            ]);

            if ($domainEntity === null) $domainEntity = new DomainEntity();


            $domain->addDomainEntity($domainEntity
                ->setDomain($domain)
                ->setEntity($entity)
                ->setRoles(array_map(fn($str): DomainRole => DomainRole::from($str), $rdapEntity['roles'])));

            $this->em->persist($entity);
            $this->em->flush();
        }

        $this->em->persist($domain);
        $this->em->flush();


        foreach ($res['nameservers'] as $rdapNameserver) {
            $nameserver = $this->nameserverRepository->findOneBy([
                "ldhName" => strtolower($rdapNameserver['ldhName'])
            ]);
            if ($nameserver === null) $nameserver = new Nameserver();

            $nameserver->setLdhName($rdapNameserver['ldhName']);
            if (array_key_exists('handle', $rdapNameserver)) $nameserver->setHandle($rdapNameserver['handle']);

            if (!array_key_exists('entities', $rdapNameserver)) {
                $domain->addNameserver($nameserver);
                continue;
            }

            foreach ($rdapNameserver['entities'] as $rdapEntity) {
                if (!array_key_exists('handle', $rdapEntity)) continue;

                $entity = $this->processEntity($rdapEntity);

                $nameserverEntity = $this->nameserverEntityRepository->findOneBy([
                    "nameserver" => $nameserver,
                    "entity" => $entity
                ]);
                if ($nameserverEntity === null) $nameserverEntity = new NameserverEntity();


                $nameserver->addNameserverEntity($nameserverEntity
                    ->setNameserver($nameserver)
                    ->setStatus(array_map(fn($str): DomainStatus => DomainStatus::from($str), $rdapNameserver['status']))
                    ->setEntity($entity)
                    ->setRoles(array_map(fn($str): DomainRole => DomainRole::from($str), $rdapEntity['roles'])));

                $this->em->persist($entity);
                $this->em->flush();
            }
            $domain->addNameserver($nameserver);
        }


        $this->em->persist($domain);
        $this->em->flush();

        return new Response(null, Response::HTTP_OK);
    }

    private function getRdapServer(string $fqdn)
    {
        $tld = $this->getTld($fqdn);

        $dnsRoot = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . '/src/Config/dns.json'))->services;
        foreach ($dnsRoot as $dns) {
            if (in_array($tld, $dns[0])) return $dns[1][0];
        }
        throw new Exception("This TLD ($tld) is not supported.");
    }

    private function getTld($domain)
    {
        $lastDotPosition = strrpos($domain, '.');
        if ($lastDotPosition === false) {
            throw new Exception("Domain must contain at least one dot.");
        }
        return substr($domain, $lastDotPosition + 1);
    }

    private function processEntity(array $rdapEntity): Entity
    {
        $entity = $this->entityRepository->findOneBy([
            "handle" => $rdapEntity['handle']
        ]);

        if ($entity === null) $entity = new Entity();
        $entity
            ->setHandle($rdapEntity['handle'])
            ->setJCard($rdapEntity['vcardArray']);

        if (!array_key_exists('events', $rdapEntity)) return $entity;

        foreach ($rdapEntity['events'] as $rdapEntityEvent) {
            $event = $this->entityEventRepository->findOneBy([
                "action" => EventAction::from($rdapEntityEvent["eventAction"]),
                "date" => new DateTimeImmutable($rdapEntityEvent["eventDate"]),
                "entity" => $entity
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