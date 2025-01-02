<?php

namespace App\Notifier;

use App\Entity\Domain;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class DomainDeletedNotification extends DomainWatchdogNotification
{
    public function __construct(
        private readonly Address $sender,
        private readonly Domain $domain,
    ) {
        parent::__construct();
    }

    public function asChatMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?ChatMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this->subject("Error: Domain Deleted $ldhName")
            ->content("Domain name $ldhName has been deleted from WHOIS.")
            ->importance(Notification::IMPORTANCE_URGENT);

        return ChatMessage::fromNotification($this);
    }

    public function asPushMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?PushMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this->subject("Error: Domain Deleted $ldhName")
            ->content("Domain name $ldhName has been deleted from WHOIS.")
            ->importance(Notification::IMPORTANCE_URGENT);

        return PushMessage::fromNotification($this);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient): EmailMessage
    {
        $ldhName = $this->domain->getLdhName();

        return new EmailMessage((new TemplatedEmail())
            ->from($this->sender)
            ->to($recipient->getEmail())
            ->subject("Domain name $ldhName has been removed from WHOIS")
            ->htmlTemplate('emails/errors/domain_deleted.html.twig')
            ->locale('en')
            ->context([
                'domain' => $this->domain,
            ]));
    }
}
