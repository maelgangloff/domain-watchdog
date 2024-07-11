<?php

namespace App\Config;

enum DomainStatus: string
{
    case Validated = 'validated';
    case RenewProhibited = 'renew prohibited';
    case UpdateProhibited = 'update prohibited';
    case TransferProhibited = 'transfer prohibited';
    case DeleteProhibited = 'delete prohibited';
    case Proxy = 'proxy';
    case Private = 'private';
    case Removed = 'removed';
    case Obscured = 'obscured';
    case Associated = 'associated';
    case Active = 'active';
    case Inactive = 'inactive';
    case Locked = 'locked';
    case PendingCreate = 'pending create';
    case PendingRenew = 'pending renew';
    case PendingTransfer = 'pending transfer';
    case PendingUpdate = 'pending update';
    case PendingDelete = 'pending delete';
    case AddPeriod = 'add period';
    case AutoRenewPeriod = 'auto renew period';
    case ClientDeleteProhibited = 'client delete prohibited';
    case ClientHold = 'client hold';
    case ClientRenewProhibited = 'client renew prohibited';
    case ClientTransferProhibited = 'client transfer prohibited';
    case ClientUpdateProhibited = 'client update prohibited';
    case PendingRestore = 'pending restore';
    case RedemptionPeriod = 'redemption period';
    case RenewPeriod = 'renew period';
    case ServerDeleteProhibited = 'server delete prohibited';
    case ServerRenewProhibited = 'server renew prohibited';
    case ServerTransferProhibited = 'server transfer prohibited';
    case ServerUpdateProhibited = 'server update prohibited';
    case ServerHold = 'server hold';
    case TransferPeriod = 'transfer period';
    case Administrative = 'administrative';
    case Reserved = 'reserved';
}
