<?php

namespace App\Config;

enum TriggerAction: string
{
    case SendEmail = 'email';
    case SendChat = 'chat';
}
