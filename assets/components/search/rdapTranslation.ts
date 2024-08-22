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
 * @see https://www.icann.org/resources/pages/epp-status-codes-2014-06-16-en
 */
export const rdapStatusCodeDetailTranslation = () => ({
    'validated': t`Signifies that the data of the object instance has been found to be accurate.`,
    'renew prohibited': t`Renewal or reregistration of the object instance is forbidden.`,
    'update prohibited': t`Updates to the object instance are forbidden.`,
    'transfer prohibited': t`Transfers of the registration from one registrar to another are forbidden.`,
    'delete prohibited': t`Deletion of the registration of the object instance is forbidden.`,
    'proxy': t`The registration of the object instance has been performed by a third party.`,
    'private': t`The information of the object instance is not designated for public consumption.`,
    'removed': t`Some of the information of the object instance has not been made available and has been removed.`,
    'obscured': t`Some of the information of the object instance has been altered for the purposes of not readily revealing the actual information of the object instance.`,
    'associated': t`The object instance is associated with other object instances in the registry.`,
    'locked': t`Changes to the object instance cannot be made, including the association of other object instances.`,

    'active': t`This is the standard status for a domain, meaning it has no pending operations or prohibitions.`,
    'inactive': t`This status code indicates that delegation information (name servers) has not been associated with your domain. Your domain is not activated in the DNS and will not resolve.`,
    'pending create': t`This status code indicates that a request to create your domain has been received and is being processed.`,
    'pending renew': t`This status code indicates that a request to renew your domain has been received and is being processed.`,
    'pending transfer': t`This status code indicates that a request to transfer your domain to a new registrar has been received and is being processed.`,
    'pending update': t`This status code indicates that a request to update your domain has been received and is being processed.`,
    'pending delete': t`This status code may be mixed with redemptionPeriod or pendingRestore. In such case, depending on the status (i.e. redemptionPeriod or pendingRestore) set in the domain name, the corresponding description presented above applies. If this status is not combined with the redemptionPeriod or pendingRestore status, the pendingDelete status code indicates that your domain has been in redemptionPeriod status for 30 days and you have not restored it within that 30-day period. Your domain will remain in this status for several days, after which time your domain will be purged and dropped from the registry database. Once deletion occurs, the domain is available for re-registration in accordance with the registry's policies.`,
    'add period': t`This grace period is provided after the initial registration of a domain name. If the registrar deletes the domain name during this period, the registry may provide credit to the registrar for the cost of the registration.`,
    'auto renew period': t`This grace period is provided after a domain name registration period expires and is extended (renewed) automatically by the registry. If the registrar deletes the domain name during this period, the registry provides a credit to the registrar for the cost of the renewal.`,
    'ok': t`This is the standard status for a domain, meaning it has no pending operations or prohibitions.`,
    'client delete prohibited': t`This status code tells your domain's registry to reject requests to delete the domain.`,
    'client hold': t`This status code tells your domain's registry to not activate your domain in the DNS and as a consequence, it will not resolve. It is an uncommon status that is usually enacted during legal disputes, non-payment, or when your domain is subject to deletion.`,
    'client renew prohibited': t`This status code tells your domain's registry to reject requests to renew your domain. It is an uncommon status that is usually enacted during legal disputes or when your domain is subject to deletion.`,
    'client transfer prohibited': t`This status code tells your domain's registry to reject requests to transfer the domain from your current registrar to another.`,
    'client update prohibited': t`This status code tells your domain's registry to reject requests to update the domain.`,
    'pending restore': t`This status code indicates that your registrar has asked the registry to restore your domain that was in redemptionPeriod status. Your registry will hold the domain in this status while waiting for your registrar to provide required restoration documentation. If your registrar fails to provide documentation to the registry operator within a set time period to confirm the restoration request, the domain will revert to redemptionPeriod status.`,
    'redemption period': t`This status code indicates that your registrar has asked the registry to delete your domain. Your domain will be held in this status for 30 days. After five calendar days following the end of the redemptionPeriod, your domain is purged from the registry database and becomes available for registration.`,
    'renew period': t`This grace period is provided after a domain name registration period is explicitly extended (renewed) by the registrar. If the registrar deletes the domain name during this period, the registry provides a credit to the registrar for the cost of the renewal.`,
    'server delete prohibited': t`This status code prevents your domain from being deleted. It is an uncommon status that is usually enacted during legal disputes, at your request, or when a redemptionPeriod status is in place.`,
    'server renew prohibited': t`This status code indicates your domain's Registry Operator will not allow your registrar to renew your domain. It is an uncommon status that is usually enacted during legal disputes or when your domain is subject to deletion.`,
    'server transfer prohibited': t`This status code prevents your domain from being transferred from your current registrar to another. It is an uncommon status that is usually enacted during legal or other disputes, at your request, or when a redemptionPeriod status is in place.`,
    'server update prohibited': t`This status code locks your domain preventing it from being updated. It is an uncommon status that is usually enacted during legal disputes, at your request, or when a redemptionPeriod status is in place.`,
    'server hold': t`This status code is set by your domain's Registry Operator. Your domain is not activated in the DNS.`,
    'transfer period': t`This grace period is provided after the successful transfer of a domain name from one registrar to another. If the new registrar deletes the domain name during this period, the registry provides a credit to the registrar for the cost of the transfer.`,

    'administrative': t`The object instance has been allocated administratively (i.e., not for use by the recipient in their own right in operational networks).`,
    'reserved': t`The object instance has been allocated to an IANA special-purpose address registry.`,
})
