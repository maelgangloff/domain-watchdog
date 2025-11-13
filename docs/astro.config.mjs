// @ts-check
import {defineConfig} from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
    site: 'https://domainwatchdog.eu',
    integrations: [
        starlight({
            title: 'Domain Watchdog',
            defaultLocale: 'en',
            logo: {
                src: './src/assets/logo.png'
            },
            favicon: 'logo.png',
            description: 'An app that uses RDAP to collect publicly available info about domains, track their history, and purchase then when they are delete',
            editLink: {
                baseUrl: 'https://github.com/maelgangloff/domain-watchdog/edit/develop/docs/'
            },
            tagline: 'Your companion in the quest for domain names 🔍',
            lastUpdated: true,
            social: [{icon: 'github', label: 'GitHub', href: 'https://github.com/maelgangloff/domain-watchdog'}],
            sidebar: [
                {label: 'Getting started', slug: 'features'},
                {
                    label: 'Self hosting',
                    autogenerate: {directory: 'self-hosting'},
                },
                {
                    label: 'Features',
                    items: [
                        {label: 'Domain back-order', autogenerate: {directory: 'features/backorder'}},
                        {label: 'Domain search', autogenerate: {directory: 'features/search'}},
                        {label: 'Domain tracking', autogenerate: {directory: 'features/tracking'}},
                    ]
                },
                {
                    label: 'Developing',
                    items: [
                        {slug: 'developing/technical-stack'},
                        {slug: 'developing/translation'},
                        {label: 'Contributing', autogenerate: {directory: 'developing/contributing'}}
                    ],
                    collapsed: true
                },
                {label: 'Legal', autogenerate: {directory: 'legal'}, collapsed: true}

            ],
            locales: {
                en: {
                    label: 'English',
                    lang: 'en'
                }
            },
            head: [
                {
                    tag: 'script',
                    attrs: {type: 'text/javascript'},
                    content: `var _paq = window._paq = window._paq || [];
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);
_paq.push(['trackAllContentImpressions']);
_paq.push(['trackVisibleContentImpressions']);
_paq.push(['enableHeartBeatTimer']);

(function () {
    var u = "//sonar.domainwatchdog.eu/";
    _paq.push(['setTrackerUrl', u + 'sonar']);
    _paq.push(['setSiteId', '4']);
    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
    g.async = true;
    g.src = u + 'sonar.js';
    s.parentNode.insertBefore(g, s);
})();`
                }
            ]
        })
    ]
});
