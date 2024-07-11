<?php

namespace App\Config;

enum DomainVariantRelation: string
{
    case Registered = 'registered';
    case Unregistered = 'unregistered';
    case RegistrationRestricted = 'registration restricted';
    case OpenRegistration = 'open registration';
    case Conjoined = 'conjoined';
}
