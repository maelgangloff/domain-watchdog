<?php

namespace App\Notifier;

use App\Entity\Connector;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

class ValidateConnectorCredentialsErrorNotification extends Notification
{
    public function __construct(
        private readonly Address $sender,
        private readonly Connector $connector,
    ) {
        parent::__construct();
    }

    public function asEmailMessage(EmailRecipientInterface $recipient): EmailMessage
    {
        return new EmailMessage((new TemplatedEmail())
            ->from($this->sender)
            ->to($recipient->getEmail())
            ->subject('Connector credentials error')
            ->htmlTemplate('emails/errors/connector_credentials.html.twig')
            ->locale('en')
            ->context([
                'connector' => $this->connector,
            ]));
    }
}
