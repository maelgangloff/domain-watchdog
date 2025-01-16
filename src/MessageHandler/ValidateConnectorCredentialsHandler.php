<?php

namespace App\MessageHandler;

use App\Message\ValidateConnectorCredentials;
use App\Notifier\ValidateConnectorCredentialsErrorNotification;
use App\Repository\ConnectorRepository;
use App\Service\Connector\AbstractProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Recipient\Recipient;

#[AsMessageHandler]
final readonly class ValidateConnectorCredentialsHandler
{
    public function __construct(
        private ConnectorRepository $connectorRepository,
        #[Autowire(service: 'service_container')]
        private ContainerInterface $locator,
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(ValidateConnectorCredentials $message): void
    {
        foreach ($this->connectorRepository->findAll() as $connector) {
            $provider = $connector->getProvider();

            try {
                if (null === $provider) {
                    throw new \Exception('Provider not found');
                }

                /** @var AbstractProvider $providerClient */
                $providerClient = $this->locator->get($provider->getConnectorProvider());
                $providerClient->authenticate($connector->getAuthData());
            } catch (\Exception) {
                $email = $connector->getUser()->getEmail();
                $this->mailer->send(
                    (new ValidateConnectorCredentialsErrorNotification(new Address($email), $connector))
                        ->asEmailMessage(new Recipient($email))->getMessage()
                );
            }
        }
    }
}
