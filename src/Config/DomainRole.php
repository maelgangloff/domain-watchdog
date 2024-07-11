<?php

namespace App\Config;

enum DomainRole: string
{
    case Registrant = 'registrant';
    case Technical = 'technical';
    case Administrative = 'administrative';
    case Abuse = 'abuse';
    case Billing = 'billing';
    case Registrar = 'registrar';
    case Reseller = 'reseller';
    case Sponsor = 'sponsor';
    case Proxy = 'proxy';
    case Notifications = 'notifications';
    case Noc = 'noc';
}
