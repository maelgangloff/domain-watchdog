<?php

namespace App\Controller;

use App\Config\Connector\OvhConnector;
use App\Config\ConnectorProvider;
use App\Entity\Connector;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ovh\Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ConnectorController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer, private readonly EntityManagerInterface $em
    )
    {
    }

    #[Route(
        path: '/api/connectors',
        name: 'connector_get_all_mine',
        defaults: [
            '_api_resource_class' => Connector::class,
            '_api_operation_name' => 'get_all_mine',
        ],
        methods: ['GET']
    )]
    public function getConnector(): Collection
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user->getConnectors();
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: '/api/connectors',
        name: 'connector_create',
        defaults: [
            '_api_resource_class' => Connector::class,
            '_api_operation_name' => 'create',
        ],
        methods: ['POST']
    )]
    public function createConnector(Request $request): Connector
    {
        $connector = $this->serializer->deserialize($request->getContent(), Connector::class, 'json', ['groups' => 'connector:create']);
        $connector->setUser($this->getUser());

        if ($connector->getProvider() === ConnectorProvider::OVH) {
            $authData = OvhConnector::verifyAuthData($connector->getAuthData());
            $connector->setAuthData($authData);
            $ovh = new Api(
                $authData['appKey'],
                $authData['appSecret'],
                $authData['apiEndpoint'],
                $authData['consumerKey']
            );


        } else throw new Exception('Unknown provider');

        $this->em->persist($connector);
        $this->em->flush();

        return $connector;
    }

}