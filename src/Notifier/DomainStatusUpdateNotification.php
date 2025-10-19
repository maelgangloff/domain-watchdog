<?php

namespace App\Notifier;

use App\Entity\Domain;
use App\Entity\DomainStatus;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class DomainStatusUpdateNotification extends DomainWatchdogNotification
{
    public function __construct(
        private readonly Address $sender,
        private readonly Domain $domain,
        private readonly DomainStatus $domainStatus,
    ) {
        parent::__construct();
    }

    public function asChatMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?ChatMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this->subject("Domain EPP status changed $ldhName")
            ->content("Domain name $ldhName EPP status has been updated.")
            ->importance(Notification::IMPORTANCE_HIGH);

        return ChatMessage::fromNotification($this);
    }

    public function asPushMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?PushMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this->subject("Domain EPP status changed $ldhName")
            ->content("Domain name $ldhName EPP status has been updated.")
            ->importance(Notification::IMPORTANCE_HIGH);

        return PushMessage::fromNotification($this);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient): EmailMessage
    {
        $ldhName = $this->domain->getLdhName();

        $email = (new TemplatedEmail())
            ->from($this->sender)
            ->to($recipient->getEmail())
            ->priority(Email::PRIORITY_HIGH)
            ->subject("Domain EPP status changed $ldhName")
            ->htmlTemplate('emails/success/domain_updated.html.twig')
            ->locale('en')
            ->context([
                'domain' => $this->domain,
                'domainStatus' => $this->domainStatus,
            ]);

        $email->getHeaders()
            ->addTextHeader('In-Reply-To', "<$ldhName+updated@domain-watchdog>")
            ->addTextHeader('References', "<$ldhName+updated@domain-watchdog>");

        return new EmailMessage($email);
    }
}
