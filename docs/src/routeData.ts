import {defineRouteMiddleware} from '@astrojs/starlight/route-data'
import type {StarlightRouteData} from '@astrojs/starlight/route-data'
import {getCollection} from 'astro:content'
import swagger from '../swagger_docs.json'

type Head = StarlightRouteData['head']

const LOCALES = ['en', 'fr'] as const
const DEFAULT_LOCALE = 'en'
const SITE_NAME = 'Domain Watchdog'
const API_PREFIX = 'en/interoperability/api'
const DESCRIPTION_MAX_LENGTH = 158

const toKey = (id: string) => id
    .replace(/(^|\/)index$/, '')
    .replace(new RegExp(`^(${LOCALES.join('|')})(/|$)`), '')

const localizedPath = (key: string, locale: string) =>
    locale === DEFAULT_LOCALE && key === '' ? '/' : `/${locale}/${key ? key + '/' : ''}`

let translations: Map<string, Set<string>> | undefined

async function getTranslations() {
    if (translations) return translations
    translations = new Map()
    for (const entry of await getCollection('docs', ({data}) => !data.draft)) {
        const [first] = entry.id.split('/')
        const locale = (LOCALES as readonly string[]).includes(first ?? '') ? first! : DEFAULT_LOCALE
        const key = toKey(entry.id)
        if (!translations.has(key)) translations.set(key, new Set())
        translations.get(key)!.add(locale)
    }
    return translations
}

const apiOperations = new Map<string, { method: string, path: string, summary: string, description: string }>()
for (const [path, operations] of Object.entries(swagger.paths as Record<string, Record<string, unknown>>)) {
    for (const [method, operation] of Object.entries(operations)) {
        const op = operation as { operationId?: string, summary?: string, description?: string }
        if (!op?.operationId) continue
        apiOperations.set(op.operationId.toLowerCase(), {
            method: method.toUpperCase(),
            path,
            summary: op.summary ?? '',
            description: (op.description ?? op.summary ?? '').replace(/\s+/g, ' ').trim()
        })
    }
}

function truncate(text: string, max = DESCRIPTION_MAX_LENGTH) {
    if (text.length <= max) return text
    const cut = text.slice(0, max - 1)
    return cut.slice(0, cut.lastIndexOf(' ')).replace(/[\s,.;:]+$/, '') + '…'
}

function setMeta(head: Head, attr: 'name' | 'property', key: string, content: string) {
    const existing = head.find(h => h.tag === 'meta' && h.attrs?.[attr] === key)
    if (existing) existing.attrs!.content = content
    else head.push({tag: 'meta', attrs: {[attr]: key, content}, content: ''})
}

function apiDescription(id: string): string | undefined {
    if (!id.startsWith(API_PREFIX)) return undefined
    const slug = id.split('/').pop() ?? ''
    const op = apiOperations.get(slug)
    if (op) {
        const body = op.description.replace(/^This (experimental )?endpoint allows (you to )?/i, '')
        const text = `${op.method} ${op.path}: ${body.charAt(0).toUpperCase()}${body.slice(1)}`
        return truncate(text.length < 100 ? `${text} Domain Watchdog API reference: parameters, request body and responses.` : text)
    }
    if (id === API_PREFIX) {
        return 'Domain Watchdog REST API reference: authenticate with JWT, run RDAP domain lookups, manage Watchlists and Connectors, and subscribe to iCal and RSS feeds.'
    }
    return undefined
}

function breadcrumb(locale: string, title: string, site: URL, canonicalHref: string) {
    return {
        '@type': 'BreadcrumbList',
        itemListElement: [
            {'@type': 'ListItem', position: 1, name: SITE_NAME, item: new URL(localizedPath('', locale), site).href},
            {'@type': 'ListItem', position: 2, name: title, item: canonicalHref}
        ]
    }
}

export const onRequest = defineRouteMiddleware(async (context) => {
    const route = context.locals.starlightRoute
    const site = context.site
    if (!site) return

    const head = route.head
    const key = toKey(route.id)
    const locale = route.locale ?? DEFAULT_LOCALE
    const available = (await getTranslations()).get(key) ?? new Set([DEFAULT_LOCALE])

    const canonicalHref = new URL(route.isFallback ? localizedPath(key, DEFAULT_LOCALE) : localizedPath(key, locale), site).href
    const canonical = head.find(h => h.tag === 'link' && h.attrs?.rel === 'canonical')
    if (canonical) canonical.attrs!.href = canonicalHref
    setMeta(head, 'property', 'og:url', canonicalHref)

    for (let i = head.length - 1; i >= 0; i--) {
        const h = head[i]
        if (h?.tag === 'link' && h.attrs?.rel === 'alternate' && h.attrs?.hreflang) head.splice(i, 1)
    }
    if (!route.isFallback && available.size > 1) {
        for (const l of LOCALES) {
            if (!available.has(l)) continue
            head.push({tag: 'link', attrs: {rel: 'alternate', hreflang: l, href: new URL(localizedPath(key, l), site).href}, content: ''})
        }
        head.push({tag: 'link', attrs: {rel: 'alternate', hreflang: 'x-default', href: new URL(localizedPath(key, DEFAULT_LOCALE), site).href}, content: ''})
    }

    const apiDesc = apiDescription(route.id)
    if (apiDesc) {
        setMeta(head, 'name', 'description', apiDesc)
        setMeta(head, 'property', 'og:description', apiDesc)
    }
    const description = apiDesc ?? route.entry.data.description

    if (route.entry.data.template === 'splash') return

    const title = route.entry.data.title.replace(/^\P{L}+/u, '').trim()
    const inLanguage = route.isFallback ? DEFAULT_LOCALE : locale
    const graph: Record<string, unknown>[] = [{
        '@type': 'TechArticle',
        '@id': `${canonicalHref}#article`,
        headline: title,
        ...(description ? {description} : {}),
        url: canonicalHref,
        inLanguage,
        ...(route.lastUpdated ? {dateModified: route.lastUpdated.toISOString()} : {}),
        author: {'@id': 'https://maelgangloff.fr#me'},
        publisher: {'@id': 'https://domainwatchdog.eu/#org'},
        isPartOf: {'@id': 'https://domainwatchdog.eu/#website'},
        about: {'@id': 'https://domainwatchdog.eu/#app'},
        image: new URL('/logo.png', site).href
    }, breadcrumb(inLanguage, title, site, canonicalHref)]

    if (key.startsWith('definitions/') && !route.isFallback) {
        graph.push({
            '@type': 'DefinedTerm',
            '@id': `${canonicalHref}#term`,
            name: title,
            ...(description ? {description} : {}),
            url: canonicalHref,
            inLanguage,
            inDefinedTermSet: {
                '@type': 'DefinedTermSet',
                '@id': `${new URL(localizedPath('definitions', locale), site).href}#glossary`,
                name: locale === 'fr' ? 'Glossaire des noms de domaine' : 'Domain name glossary'
            }
        })
    }

    head.push({
        tag: 'script',
        attrs: {type: 'application/ld+json'},
        content: JSON.stringify({'@context': 'https://schema.org', '@graph': graph})
    })
})
