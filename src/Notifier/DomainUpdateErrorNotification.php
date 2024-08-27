<?php

namespace App\Notifier;

use App\Entity\Domain;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\PushNotificationInterface;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class DomainUpdateErrorNotification extends Notification implements ChatNotificationInterface, EmailNotificationInterface, PushNotificationInterface
{
    public function __construct(
        private readonly Address $sender,
        private readonly Domain $domain
    ) {
        parent::__construct();
    }

    public function asChatMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?ChatMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this->subject("Error: Domain Update $ldhName")
            ->content("Domain name $ldhName tried to be updated. The attempt failed.")
            ->importance(Notification::IMPORTANCE_MEDIUM);

        return ChatMessage::fromNotification($this);
    }

    public function asPushMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?PushMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this->subject("Error: Domain Update $ldhName")
            ->content("Domain name $ldhName tried to be updated. The attempt failed.")
            ->importance(Notification::IMPORTANCE_MEDIUM);

        return PushMessage::fromNotification($this);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        return new EmailMessage((new TemplatedEmail())
            ->from($this->sender)
            ->to($recipient->getEmail())
            ->subject('An error occurred while updating a domain name')
            ->htmlTemplate('emails/errors/domain_update.html.twig')
            ->locale('en')
            ->context([
                'domain' => $this->domain,
            ]));
    }
}
