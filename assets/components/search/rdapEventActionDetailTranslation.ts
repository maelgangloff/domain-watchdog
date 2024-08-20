import {t} from "ttag";

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