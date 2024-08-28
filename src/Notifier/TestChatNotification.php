<?php

namespace App\Notifier;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class TestChatNotification extends DomainWatchdogNotification
{
    public function asChatMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?ChatMessage
    {
        $this
            ->subject('Test notification')
            ->content('This is a test message. If you can read me, this Webhook is configured correctly')
            ->importance(Notification::IMPORTANCE_LOW);

        return ChatMessage::fromNotification($this);
    }

    public function asPushMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?PushMessage
    {
        $this
            ->subject('Test notification')
            ->content('This is a test message. If you can read me, this Webhook is configured correctly')
            ->importance(Notification::IMPORTANCE_LOW);

        return PushMessage::fromNotification($this);
    }
}
