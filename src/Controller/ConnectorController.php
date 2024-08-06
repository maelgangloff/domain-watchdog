<?php

namespace App\Controller;

use App\Config\Connector\OvhConnector;
use App\Config\ConnectorProvider;
use App\Entity\Connector;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ConnectorController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {
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
     * @throws \Exception
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
        /** @var User $user */
        $user = $this->getUser();
        $connector->setUser($user);

        $provider = $connector->getProvider();

        $this->logger->info('User {username} wants to register a connector from provider {provider}.', [
            'username' => $user->getUserIdentifier(),
            'provider' => $provider->value,
        ]);

        if (ConnectorProvider::OVH === $provider) {
            $authData = OvhConnector::verifyAuthData($connector->getAuthData());
            $connector->setAuthData($authData);

            $this->logger->info('User {username} authentication data with the OVH provider has been validated.', [
                'username' => $user->getUserIdentifier(),
            ]);
        } else {
            throw new BadRequestHttpException('Unknown provider');
        }

        $this->logger->info('The new API connector requested by {username} has been successfully registered.', [
            'username' => $user->getUserIdentifier(),
        ]);

        $connector->setCreatedAt(new \DateTimeImmutable('now'));
        $this->em->persist($connector);
        $this->em->flush();

        return $connector;
    }
}
