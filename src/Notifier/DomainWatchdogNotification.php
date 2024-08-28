<?php

namespace App\Notifier;

use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\PushNotificationInterface;

abstract class DomainWatchdogNotification extends Notification implements ChatNotificationInterface, PushNotificationInterface
{
}
