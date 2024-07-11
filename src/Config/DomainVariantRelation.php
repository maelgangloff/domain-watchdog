<?php

namespace App\Config;

/**
 * @see https://www.iana.org/assignments/rdap-json-values/rdap-json-values.xhtml
 */
enum DomainVariantRelation: string
{
    case Registered = 'registered';
    case Unregistered = 'unregistered';
    case RegistrationRestricted = 'registration restricted';
    case OpenRegistration = 'open registration';
    case Conjoined = 'conjoined';
}
