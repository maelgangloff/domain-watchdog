<?php

namespace App\Notifier;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class TestChatNotification extends Notification implements ChatNotificationInterface
{
    public function asChatMessage(?RecipientInterface $recipient = null, ?string $transport = null): ?ChatMessage
    {
        $this->subject('Test notification');
        $this->content('This is a test message. If you can read me, this Webhook is configured correctly');

        return ChatMessage::fromNotification($this);
    }
}
