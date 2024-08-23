<?php

namespace App\Controller;

use App\Config\Provider\AbstractProvider;
use App\Entity\Connector;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
    public function createConnector(Request $request, HttpClientInterface $client): Connector
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

        if (null === $provider) {
            throw new \Exception('Provider not found');
        }

        /** @var AbstractProvider $connectorProviderClass */
        $connectorProviderClass = $provider->getConnectorProvider();

        $authData = $connectorProviderClass::verifyAuthData($connector->getAuthData(), $client);
        $connector->setAuthData($authData);

        $this->logger->info('User {username} authentication data with the {provider} provider has been validated.', [
            'username' => $user->getUserIdentifier(),
            'provider' => $provider->value,
        ]);

        $this->logger->info('The new API connector requested by {username} has been successfully registered.', [
            'username' => $user->getUserIdentifier(),
        ]);

        $connector->setCreatedAt(new \DateTimeImmutable('now'));
        $this->em->persist($connector);
        $this->em->flush();

        return $connector;
    }
}
