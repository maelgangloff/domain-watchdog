<?php

namespace App\Config;

/**
 * @see https://www.iana.org/assignments/rdap-json-values/rdap-json-values.xhtml
 */
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
