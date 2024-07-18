<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WatchList;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class WatchListController extends AbstractController
{

    public function __construct(
        private readonly SerializerInterface $serializer, private readonly EntityManagerInterface $em
    )
    {
    }

    #[Route(
        path: '/api/watchlists',
        name: 'watchlist_get_all_mine',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'get_all_mine',
        ],
        methods: ['GET']
    )]
    public function getWatchLists(): Collection
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getWatchLists();
    }

    #[Route(
        path: '/api/watchlists',
        name: 'watchlist_create',
        defaults: [
            '_api_resource_class' => WatchList::class,
            '_api_operation_name' => 'create',
        ],
        methods: ['POST']
    )]
    public function createWatchList(Request $request): WatchList
    {
        $watchList = $this->serializer->deserialize($request->getContent(), WatchList::class, 'json', ['groups' => 'watchlist:create']);
        $watchList->setUser($this->getUser());
        $this->em->persist($watchList);
        $this->em->flush();

        return $watchList;
    }

}