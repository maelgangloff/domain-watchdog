<?php

namespace App\Config;

enum EventAction: string
{
    case Registration = 'registration';
    case Reregistration = 'reregistration';
    case LastChanged = 'last changed';
    case Expiration = 'expiration';
    case Deletion = 'deletion';
    case Reinstantiation = 'reinstantiation';
    case Transfer = 'transfer';
    case Locked = 'locked';
    case Unlocked = 'unlocked';
    case LastUpdateOfRDAPDatabase = 'last update of RDAP database';
    case RegistrarExpiration = 'registrar expiration';
    case EnumValidationExpiration = 'enum validation expiration';
}
