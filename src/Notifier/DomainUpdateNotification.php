<?php

namespace App\Notifier;

use App\Entity\DomainEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\PushNotificationInterface;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class DomainUpdateNotification extends Notification implements ChatNotificationInterface, EmailNotificationInterface, PushNotificationInterface
{
    public function __construct(
        private readonly Address $sender,
        private readonly DomainEvent $domainEvent
    ) {
        parent::__construct();
    }

    public function asChatMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?ChatMessage
    {
        $ldhName = $this->domainEvent->getDomain()->getLdhName();
        $action = $this->domainEvent->getAction();
        $this->subject("Domain changed $ldhName ($action)")
            ->content("Domain name $ldhName information has been updated ($action).")
            ->importance(Notification::IMPORTANCE_HIGH);

        return ChatMessage::fromNotification($this);
    }

    public function asPushMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?PushMessage
    {
        $ldhName = $this->domainEvent->getDomain()->getLdhName();
        $action = $this->domainEvent->getAction();
        $this->subject("Domain changed $ldhName ($action)")
            ->content("Domain name $ldhName information has been updated ($action).")
            ->importance(Notification::IMPORTANCE_HIGH);

        return PushMessage::fromNotification($this);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        return new EmailMessage((new TemplatedEmail())
            ->from($this->sender)
            ->to($recipient->getEmail())
            ->priority(Email::PRIORITY_HIGHEST)
            ->subject('A domain name has been changed')
            ->htmlTemplate('emails/success/domain_updated.html.twig')
            ->locale('en')
            ->context([
                'event' => $this->domainEvent,
            ]));
    }
}
