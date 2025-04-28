<?php

namespace App\Notifier;

use App\Entity\Connector;
use App\Entity\Domain;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class DomainOrderNotification extends DomainWatchdogNotification
{
    public function __construct(
        private readonly Address $sender,
        private readonly Domain $domain,
        private readonly Connector $connector,
    ) {
        parent::__construct();
    }

    public function asChatMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?ChatMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this
            ->subject("Success: Domain Ordered $ldhName!")
            ->content("Domain name $ldhName has just been purchased. The API provider did not return an error.")
            ->importance(Notification::IMPORTANCE_URGENT);

        return ChatMessage::fromNotification($this);
    }

    public function asPushMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?PushMessage
    {
        $ldhName = $this->domain->getLdhName();
        $this
            ->subject("Success: Domain Ordered $ldhName!")
            ->content("Domain name $ldhName has just been purchased. The API provider did not return an error.")
            ->importance(Notification::IMPORTANCE_URGENT);

        return PushMessage::fromNotification($this);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient): EmailMessage
    {
        $ldhName = $this->domain->getLdhName();

        $email = (new TemplatedEmail())
            ->from($this->sender)
            ->to($recipient->getEmail())
            ->priority(Email::PRIORITY_HIGHEST)
            ->subject("Domain name $ldhName has just been purchased")
            ->htmlTemplate('emails/success/domain_ordered.html.twig')
            ->locale('en')
            ->context([
                'domain' => $this->domain,
                'provider' => $this->connector->getProvider()->value,
            ]);

        $email->getHeaders()
            ->addTextHeader('In-Reply-To', "<$ldhName+ordered@domain-watchdog>")
            ->addTextHeader('References', "<$ldhName+ordered@domain-watchdog>");

        return new EmailMessage($email);
    }
}
