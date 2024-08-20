import {t} from "ttag";

/**
 * @see https://www.iana.org/assignments/rdap-json-values/rdap-json-values.xhtml
 */
export const rdapRoleTranslation = () => ({
    registrant: t`Registrant`,
    technical: t`Technical`,
    administrative: t`Administrative`,
    abuse: t`Abuse`,
    billing: t`Billing`,
    registrar: t`Registrar`,
    reseller: t`Reseller`,
    sponsor: t`Sponsor`,
    proxy: t`Proxy`,
    notifications: t`Notifications`,
    noc: t`Noc`
})


/**
 * @see https://www.iana.org/assignments/rdap-json-values/rdap-json-values.xhtml
 */
export const rdapRoleDetailTranslation = () => ({
    registrant: t`The entity object instance is the registrant of the registration. In some registries, this is known as a maintainer.`,
    technical: t`The entity object instance is a technical contact for the registration.`,
    administrative: t`The entity object instance is an administrative contact for the registration.`,
    abuse: t`The entity object instance handles network abuse issues on behalf of the registrant of the registration.`,
    billing: t`The entity object instance handles payment and billing issues on behalf of the registrant of the registration.`,
    registrar: t`The entity object instance represents the authority responsible for the registration in the registry.`,
    reseller: t`The entity object instance represents a third party through which the registration was conducted (i.e., not the registry or registrar).`,
    sponsor: t`The entity object instance represents a domain policy sponsor, such as an ICANN-approved sponsor.`,
    proxy: t`The entity object instance represents a proxy for another entity object, such as a registrant.`,
    notifications: t`An entity object instance designated to receive notifications about association object instances.`,
    noc: t`The entity object instance handles communications related to a network operations center (NOC).`
})


/**
 * @see https://www.iana.org/assignments/rdap-json-values/rdap-json-values.xhtml
 */
export const rdapEventNameTranslation = () => ({
    registration: t`Registration`,
    reregistration: t`Reregistration`,
    'last changed': t`Changed`,
    expiration: t`Expiration`,
    deletion: t`Deletion`,
    reinstantiation: t`Reinstantiation`,
    transfer: t`Transfer`,
    locked: t`Locked`,
    unlocked: t`Unlocked`,
    'registrar expiration': t`Registrar expiration`,
    'enum validation expiration': t`ENUM validation expiration`
})

/**
 * @see https://www.iana.org/assignments/rdap-json-values/rdap-json-values.xhtml
 */
export const rdapEventDetailTranslation = () => ({
    registration: t`The object instance was initially registered.`,
    reregistration: t`The object instance was registered subsequently to initial registration.`,
    'last changed': t`An action noting when the information in the object instance was last changed.`,
    expiration: t`The object instance has been removed or will be removed at a predetermined date and time from the registry.`,
    deletion: t`The object instance was removed from the registry at a point in time that was not predetermined.`,
    reinstantiation: t`The object instance was reregistered after having been removed from the registry.`,
    transfer: t`The object instance was transferred from one registrar to another.`,
    locked: t`The object instance was locked.`,
    unlocked: t`The object instance was unlocked.`,
    'registrar expiration': t`An action noting the expiration date of the object in the registrar system.`,
    'enum validation expiration': t`Association of phone number represented by this ENUM domain to registrant has expired or will expire at a predetermined date and time.`
})

/**
 * @see https://www.iana.org/assignments/rdap-json-values/rdap-json-values.xhtml
 */
export const rdapStatusCodeDetailTranslation = () => ({
    validated: t`Signifies that the data of the object instance has been found to be accurate.`,
    'renew prohibited': t`Renewal or reregistration of the object instance is forbidden.`,
    'update prohibited': t`Updates to the object instance are forbidden.`,
    'transfer prohibited': t`Transfers of the registration from one registrar to another are forbidden.`,
    'delete prohibited': t`Deletion of the registration of the object instance is forbidden.`,
    'proxy': t`The registration of the object instance has been performed by a third party.`,
    'private': t`The information of the object instance is not designated for public consumption.`,
    'removed': t`Some of the information of the object instance has not been made available and has been removed.`,
    'obscured': t`Some of the information of the object instance has been altered for the purposes of not readily revealing the actual information of the object instance.`,
    'associated': t`The object instance is associated with other object instances in the registry.`,
    'active': t`The object instance is in use. For domain names, it signifies that the domain name is published in DNS. For network and autnum registrations, it signifies that they are allocated or assigned for use in operational networks.`,
    'inactive': t`The object instance is not in use.`,
    'locked': t`Changes to the object instance cannot be made, including the association of other object instances.`,
    'pending create': t`A request has been received for the creation of the object instance, but this action is not yet complete.`,
    'pending renew': t`A request has been received for the renewal of the object instance, but this action is not yet complete.`,
    'pending transfer': t`A request has been received for the transfer of the object instance, but this action is not yet complete.`,
    'pending update': t`A request has been received for the update or modification of the object instance, but this action is not yet complete.`,
    'pending delete': t`A request has been received for the deletion or removal of the object instance, but this action is not yet complete. For domains, this might mean that the name is no longer published in DNS but has not yet been purged from the registry database.`,
    'add period': t`This grace period is provided after the initial registration of the object. If the object is deleted by the client during this period, the server provides a credit to the client for the cost of the registration.`,
    'auto renew period': t`This grace period is provided after an object registration period expires and is extended (renewed) automatically by the server. If the object is deleted by the client during this period, the server provides a credit to the client for the cost of the auto renewal.`,
    'client delete prohibited': t`The client requested that requests to delete the object MUST be rejected.`,
    'client hold': t`The client requested that the DNS delegation information MUST NOT be published for the object.`,
    'client renew prohibited': t`The client requested that requests to renew the object MUST be rejected.`,
    'client transfer prohibited': t`The client requested that requests to transfer the object MUST be rejected.`,
    'client update prohibited': t`The client requested that requests to update the object (other than to remove this status) MUST be rejected.`,
    'pending restore': t`An object is in the process of being restored after being in the redemption period state.`,
    'redemption period': t`A delete has been received, but the object has not yet been purged because an opportunity exists to restore the object and abort the deletion process.`,
    'renew period': t`This grace period is provided after an object registration period is explicitly extended (renewed) by the client. If the object is deleted by the client during this period, the server provides a credit to the client for the cost of the renewal.`,
    'server delete prohibited': t`The server set the status so that requests to delete the object MUST be rejected.`,
    'server renew prohibited': t`The server set the status so that requests to renew the object MUST be rejected.`,
    'server transfer prohibited': t`The server set the status so that requests to transfer the object MUST be rejected.`,
    'server update prohibited': t`The server set the status so that requests to update the object (other than to remove this status) MUST be rejected.`,
    'server hold': t`The server set the status so that DNS delegation information MUST NOT be published for the object.`,
    'transfer period': t`This grace period is provided after the successful transfer of object registration sponsorship from one client to another client. If the object is deleted by the client during this period, the server provides a credit to the client for the cost of the transfer.`,
    'administrative': t`The object instance has been allocated administratively (i.e., not for use by the recipient in their own right in operational networks).`,
    'reserved': t`The object instance has been allocated to an IANA special-purpose address registry.`,
})
