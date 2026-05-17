<?php

namespace App\Config;

enum DomainPurchaseFailureReason: string
{
    case AlreadyRegistered = 'registered';
    case Exception = 'exception';
}
