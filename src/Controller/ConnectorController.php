<?php

namespace App\Controller;

use App\Config\ConnectorProvider;
use App\Entity\Connector;
use App\Entity\User;
use App\Service\Connector\AbstractProvider;
use App\Service\Connector\EppClientProvider;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ConnectorController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'service_container')]
        private readonly ContainerInterface $locator,
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
     * @throws ExceptionInterface
     * @throws \Throwable
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
    public function createConnector(Connector $connector): Connector
    {
        /** @var User $user */
        $user = $this->getUser();
        $connector->setUser($user);

        $provider = $connector->getProvider();

        $this->logger->info('User {username} wants to register a connector from provider {provider}.', [
            'username' => $user->getUserIdentifier(),
            'provider' => $provider->value,
        ]);

        if (null === $provider) {
            throw new BadRequestHttpException('Provider not found');
        }
        $authData = $connector->getAuthData();

        if (ConnectorProvider::EPP === $provider) {
            $filesystem = new Filesystem();
            $directory = sprintf('%s/%s/', EppClientProvider::EPP_CERTIFICATES_PATH, $connector->getId());
            unset($authData['file_certificate_pem'], $authData['file_certificate_key']); // Prevent alteration from user

            if (isset($authData['certificate_pem'], $authData['certificate_key'])) {
                $pemPath = $directory.'client.pem';
                $keyPath = $directory.'client.key';

                $filesystem->mkdir($directory, 0755);
                $filesystem->dumpFile($pemPath, $authData['certificate_pem']);
                $filesystem->dumpFile($keyPath, $authData['certificate_key']);
                $connector->setAuthData([...$authData, 'file_certificate_pem' => $pemPath, 'file_certificate_key' => $keyPath]);
            }

            /** @var AbstractProvider $providerClient */
            $providerClient = $this->locator->get($provider->getConnectorProvider());

            try {
                $connector->setAuthData($providerClient->authenticate($authData));
            } catch (\Throwable $exception) {
                $filesystem->remove($directory);
                throw $exception;
            }
        } else {
            /** @var AbstractProvider $providerClient */
            $providerClient = $this->locator->get($provider->getConnectorProvider());
            $connector->setAuthData($providerClient->authenticate($authData));
        }

        $this->logger->info('User {username} authentication data with the {provider} provider has been validated.', [
            'username' => $user->getUserIdentifier(),
            'provider' => $provider->value,
        ]);

        $connector->setCreatedAt(new \DateTimeImmutable('now'));
        $this->em->persist($connector);
        $this->em->flush();

        return $connector;
    }

    /**
     * @throws \Exception
     */
    #[Route(
        path: '/api/connectors/{id}',
        name: 'connector_delete',
        defaults: [
            '_api_resource_class' => Connector::class,
            '_api_operation_name' => 'delete',
        ],
        methods: ['DELETE']
    )]
    public function deleteConnector(Connector $connector): void
    {
        foreach ($connector->getWatchLists()->getIterator() as $watchlist) {
            $watchlist->setConnector(null);
        }

        $provider = $connector->getProvider();

        if (null === $provider) {
            throw new BadRequestHttpException('Provider not found');
        }

        if (ConnectorProvider::EPP === $provider) {
            (new Filesystem())->remove(sprintf('%s/%s/', EppClientProvider::EPP_CERTIFICATES_PATH, $connector->getId()));
        }

        $this->em->remove($connector);
        $this->em->flush();
    }
}
