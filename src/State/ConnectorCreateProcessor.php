<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Config\ConnectorProvider;
use App\Entity\Connector;
use App\Entity\User;
use App\Service\Connector\AbstractProvider;
use App\Service\Connector\EppClientProvider;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

readonly class ConnectorCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private LoggerInterface $logger,
        #[Autowire(service: 'service_container')]
        private ContainerInterface $locator,
        private KernelInterface $kernel,
    ) {
    }

    /**
     * @param Connector $data
     *
     * @return Connector
     *
     * @throws \Throwable
     * @throws ExceptionInterface
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $data->setUser($user);

        $provider = $data->getProvider();

        $this->logger->info('User wants to register a connector', [
            'username' => $user->getUserIdentifier(),
            'provider' => $provider->value,
        ]);

        if (null === $provider) {
            throw new BadRequestHttpException('Provider not found');
        }
        $authData = $data->getAuthData();

        if (ConnectorProvider::EPP === $provider) {
            $filesystem = new Filesystem();
            $directory = EppClientProvider::buildEppCertificateFolder($this->kernel->getProjectDir(), $data->getId());
            unset($authData['file_certificate_pem'], $authData['file_certificate_key']); // Prevent alteration from user

            if (isset($authData['certificate_pem'], $authData['certificate_key'])) {
                $pemPath = $directory.'client.pem';
                $keyPath = $directory.'client.key';

                $filesystem->mkdir($directory, 0755);
                $filesystem->dumpFile($pemPath, $authData['certificate_pem']);
                $filesystem->dumpFile($keyPath, $authData['certificate_key']);
                $data->setAuthData([...$authData, 'file_certificate_pem' => $pemPath, 'file_certificate_key' => $keyPath]);
            }

            /** @var AbstractProvider $providerClient */
            $providerClient = $this->locator->get($provider->getConnectorProvider());

            try {
                $data->setAuthData($providerClient->authenticate($authData));
            } catch (\Throwable $exception) {
                $filesystem->remove($directory);
                throw $exception;
            }
        } else {
            /** @var AbstractProvider $providerClient */
            $providerClient = $this->locator->get($provider->getConnectorProvider());
            $data->setAuthData($providerClient->authenticate($authData));
        }

        $this->logger->info('User authentication data with this provider has been validated', [
            'username' => $user->getUserIdentifier(),
            'provider' => $provider->value,
        ]);

        $data->setCreatedAt(new \DateTimeImmutable('now'));
        $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $data;
    }
}
