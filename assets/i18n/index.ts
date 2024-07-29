import {addLocale, useLocale} from 'ttag'

const locale = navigator.language.split('-')[0]
export const regionNames = new Intl.DisplayNames([locale], {type: 'region'})

if (locale !== 'en') {
    fetch(`/locales/${locale}.po.json`).then(response => {
        if (!response.ok) throw new Error(`Failed to load translations for locale ${locale}`);
        response.json().then(translationsObj => {
            addLocale(locale, translationsObj);
            useLocale(locale);
        })
    })
}
