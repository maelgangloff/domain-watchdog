import {t} from "ttag";
import {regionNames} from "../../i18n";

export const ovhFields = () => ({
    appKey: t`Application key`,
    appSecret: t`Application secret`,
    consumerKey: t`Consumer key`
})

export const ovhEndpointList = () => [
    {
        label: t`European Region`,
        value: 'ovh-eu'
    }
]

export const ovhSubsidiaryList = () => [...[
    'CZ', 'DE', 'ES', 'FI', 'FR', 'GB', 'IE', 'IT', 'LT', 'MA', 'NL', 'PL', 'PT', 'SN', 'TN'
].map(c => ({value: c, label: regionNames.of(c) ?? c})), {value: 'EU', label: t`Europe`}]

export const ovhPricingMode = () => [
    {value: 'create-default', label: t`The domain is free and at the standard price`},
    {
        value: 'create-premium',
        label: t`The domain is free but is a premium. Its price varies from one domain to another`
    }
]