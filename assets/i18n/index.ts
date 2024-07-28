import {addLocale, useLocale} from 'ttag'

const locale = navigator.language.split('-')[0];

if (locale !== 'en') {
    fetch(`/locales/${locale}.po.json`).then(response => {
        if (!response.ok) throw new Error(`Failed to load translations for locale ${locale}`);
        response.json().then(translationsObj => {
            addLocale(locale, translationsObj);
            useLocale(locale);
        })
    })
}
