// @ts-check
import {defineConfig} from 'astro/config'
import starlight from '@astrojs/starlight'
import starlightLinksValidator from 'starlight-links-validator'
import mermaid from "astro-mermaid"
import starlightCoolerCredit from "starlight-cooler-credit"
import starlightKbd from 'starlight-kbd'

const BASE_URL = 'https://domainwatchdog.eu'

// https://astro.build/config
export default defineConfig({
    site: BASE_URL,
    integrations: [
        starlight({
            title: 'Domain Watchdog',
            defaultLocale: 'en',
            logo: {
                src: './src/assets/logo.png'
            },
            favicon: 'logo.png',
            description: 'An app that uses RDAP to collect publicly available info about domains, track their history, and purchase then when they expire',
            editLink: {
                baseUrl: 'https://github.com/maelgangloff/domain-watchdog/edit/develop/docs/'
            },
            tagline: 'Your companion in the quest for domain names 🔍',
            lastUpdated: true,
            social: [
                {icon: 'github', label: 'GitHub', href: 'https://github.com/maelgangloff/domain-watchdog'},
                {icon: 'seti:docker', label: 'Docker', href: 'https://hub.docker.com/r/maelgangloff/domain-watchdog'}
            ],
            sidebar: [
                {label: 'Getting started', slug: 'features'},
                {
                    label: 'Installation & Configuration',
                    items: [
                        {label: 'Installation', autogenerate: {directory: 'install-config/install'}},
                        {slug: 'install-config/configuration'},
                        {slug: 'install-config/upgrade'},
                    ]
                },
                {
                    label: 'Features',
                    items: [
                        {slug: 'features/search/domain-search'},
                        {label: 'Domain back-order', autogenerate: {directory: 'features/backorder'}},
                        {label: 'Domain tracking', autogenerate: {directory: 'features/tracking'}},
                        {label: 'Infrastructure', autogenerate: {directory: 'features/infrastructure'}},
                    ]
                },
                {
                    label: 'Developing',
                    items: [
                        {slug: 'developing/technical-stack'},
                        {slug: 'developing/implementing-new-provider'},
                        {slug: 'developing/translation'},
                        {label: 'Contributing', autogenerate: {directory: 'developing/contributing'}}
                    ],
                },
                {label: 'Legal', autogenerate: {directory: 'legal'}, collapsed: true},
                {slug: 'acknowledgment'}
            ],
            locales: {
                en: {
                    label: 'English',
                    lang: 'en'
                }
            },
            head: [
                {
                    tag: 'meta',
                    attrs: {
                        name: 'keywords',
                        content: 'Domain Watchdog, RDAP, WHOIS, domain monitoring, domain history, domain expiration, domain tracker'
                    },
                },
                {
                    tag: 'meta',
                    attrs: {
                        name: 'author',
                        content: 'Maël Gangloff'
                    },
                },
                {
                    tag: 'meta',
                    attrs: {name: 'twitter:card', content: 'summary'},
                },
                {
                    tag: 'meta',
                    attrs: {name: 'twitter:url', content: BASE_URL},
                },
                {
                    tag: 'meta',
                    attrs: {property: 'og:image', content: BASE_URL + '/logo.png'},
                },
                {
                    tag: 'meta',
                    attrs: {property: 'og:image:alt', content: 'Domain Watchdog logo'},
                },
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
            ],
            plugins: [
                starlightLinksValidator({
                    errorOnLocalLinks: false
                }),
                starlightCoolerCredit({
                    credit: {
                        title: '',
                        href: 'https://maelgangloff.fr',
                        description: 'Maintained with ♡ by Maël Gangloff & contributors'
                    },
                    showImage: false
                }),
                starlightKbd({
                    types: [
                        {id: 'mac', label: 'macOS', default: true},
                        {id: 'windows', label: 'Windows'},
                    ],
                }),
            ],
            customCss: [
                './src/styles/index.css'
            ]
        }),
        mermaid()
    ]
})
