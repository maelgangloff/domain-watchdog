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
use Juanparati\RDAPLib\Exceptions\RDAPWrongRequest;
use Juanparati\RDAPLib\RDAPClient;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class TestController extends AbstractController
{

    public function __construct(private EntityRepository           $entityRepository,
                                private DomainRepository           $domainRepository,
                                private DomainEventRepository      $domainEventRepository,
                                private NameserverRepository       $nameserverRepository,
                                private NameserverEntityRepository $nameserverEntityRepository,
                                private EntityEventRepository      $entityEventRepository,
                                private DomainEntityRepository     $domainEntityRepository,
                                private EntityManagerInterface     $em)
    {

    }

    #[Route(path: '/test/{fqdn}', name: 'test')]
    public function testRoute(string $fqdn): Response
    {
        $rdap = new RDAPClient(['domain' => 'https://rdap.nic.fr/domain/']);
        try {
            $res = $rdap->domainLookup($fqdn, RDAPClient::ARRAY_OUTPUT);
        } catch (RDAPWrongRequest $e) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        } catch (ClientExceptionInterface $e) {
            return new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $domain = $this->domainRepository->findOneBy(["handle" => $res['handle']]);
        if ($domain === null) $domain = new Domain();

        $domain->setLdhName($res['ldhName'])
            ->setHandle($res['handle'])
            ->setStatus(array_map(fn($str): DomainStatus => DomainStatus::from($str), $res['status']))
            ->setWhoisStatus($res['whoisStatus']);


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
                "handle" => $rdapNameserver['handle']
            ]);
            if ($nameserver === null) $nameserver = new Nameserver();

            $nameserver
                ->setHandle($rdapNameserver['handle'])
                ->setLdhName($rdapNameserver['ldhName'])
                ->setStatus(array_map(fn($str): DomainStatus => DomainStatus::from($str), $rdapNameserver['status']));

            foreach ($rdapNameserver['entities'] as $rdapEntity) {
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

            }
            $domain->addNameserver($nameserver);
        }


        $this->em->persist($domain);
        $this->em->flush();

        return new Response(null, Response::HTTP_OK);
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