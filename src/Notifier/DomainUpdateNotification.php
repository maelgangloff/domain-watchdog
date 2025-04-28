<?php

namespace App\Notifier;

use App\Entity\DomainEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class DomainUpdateNotification extends DomainWatchdogNotification
{
    public function __construct(
        private readonly Address $sender,
        private readonly DomainEvent $domainEvent,
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

    public function asEmailMessage(EmailRecipientInterface $recipient): EmailMessage
    {
        $ldhName = $this->domainEvent->getDomain()->getLdhName();

        $email = (new TemplatedEmail())
            ->from($this->sender)
            ->to($recipient->getEmail())
            ->priority(Email::PRIORITY_HIGH)
            ->subject("Domain name $ldhName information has been updated")
            ->htmlTemplate('emails/success/domain_updated.html.twig')
            ->locale('en')
            ->context([
                'event' => $this->domainEvent,
            ]);

        $email->getHeaders()
            ->addTextHeader('In-Reply-To', "<$ldhName+updated@domain-watchdog>")
            ->addTextHeader('References', "<$ldhName+updated@domain-watchdog>");

        return new EmailMessage($email);
    }
}
