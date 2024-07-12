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

    #[Route(path: '/test', name: 'test')]
    public function testRoute(EntityRepository           $entityRepository,
                              DomainRepository           $domainRepository,
                              DomainEventRepository      $domainEventRepository,
                              NameserverRepository       $nameserverRepository,
                              NameserverEntityRepository $nameserverEntityRepository,
                              EntityEventRepository      $entityEventRepository,
                              DomainEntityRepository     $domainEntityRepository,
                              EntityManagerInterface     $em
    ): Response
    {
        $rdap = new RDAPClient(['domain' => 'https://rdap.nic.fr/domain/']);
        try {
            $res = $rdap->domainLookup('maelgangloff.fr', RDAPClient::ARRAY_OUTPUT);
        } catch (RDAPWrongRequest $e) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        } catch (ClientExceptionInterface $e) {
            return new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $domain = $domainRepository->findOneBy(["handle" => $res['handle']]);
        if ($domain === null) $domain = new Domain();

        $domain->setLdhName($res['ldhName'])
            ->setHandle($res['handle'])
            ->setStatus(array_map(fn($str): DomainStatus => DomainStatus::from($str), $res['status']))
            ->setWhoisStatus($res['whoisStatus']);


        foreach ($res['events'] as $rdapEvent) {
            $event = $domainEventRepository->findOneBy([
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
            $entity = $entityRepository->findOneBy([
                "handle" => $rdapEntity['handle']
            ]);

            if ($entity === null) $entity = new Entity();
            $entity->setHandle($rdapEntity['handle']);


            foreach ($rdapEntity['events'] as $rdapEntityEvent) {
                $event = $entityEventRepository->findOneBy([
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

            $domainEntity = $domainEntityRepository->findOneBy([
                "domain" => $domain,
                "entity" => $entity
            ]);
            if ($domainEntity === null) $domainEntity = new DomainEntity();


            $domain->addDomainEntity($domainEntity
                ->setDomain($domain)
                ->setEntity($entity)
                ->setRoles(array_map(fn($str): DomainRole => DomainRole::from($str), $rdapEntity['roles'])));

        }

        $em->persist($domain);
        $em->flush();


        foreach ($res['nameservers'] as $rdapNameserver) {
            $nameserver = $nameserverRepository->findOneBy([
                "handle" => $rdapNameserver['handle']
            ]);
            if ($nameserver === null) $nameserver = new Nameserver();

            $nameserver
                ->setHandle($rdapNameserver['handle'])
                ->setLdhName($rdapNameserver['ldhName'])
                ->setStatus(array_map(fn($str): DomainStatus => DomainStatus::from($str), $rdapNameserver['status']));

            foreach ($rdapNameserver['entities'] as $rdapEntity) {
                $entity = $entityRepository->findOneBy([
                    "handle" => $rdapEntity['handle']
                ]);

                if ($entity === null) $entity = new Entity();
                $entity->setHandle($rdapEntity['handle']);


                foreach ($rdapEntity['events'] as $rdapEntityEvent) {
                    $event = $entityEventRepository->findOneBy([
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


                $nameserverEntity = $nameserverEntityRepository->findOneBy([
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


        $em->persist($domain);
        $em->flush();

        return new Response(null, Response::HTTP_OK);
    }

}