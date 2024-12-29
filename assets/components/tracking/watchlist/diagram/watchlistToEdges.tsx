import {Domain, Watchlist} from "../../../../utils/api";
import {rdapRoleTranslation} from "../../../../utils/functions/rdapTranslation";
import {t} from "ttag";

import {rolesToColor} from "../../../../utils/functions/rolesToColor";

export function domainEntitiesToEdges(d: Domain, withRegistrar = false) {
    const rdapRoleTranslated = rdapRoleTranslation()
    const sponsor = d.entities.find(e => !e.deleted && e.roles.includes('sponsor'))
    return d.entities
        .filter(e =>
            !e.deleted &&
            (withRegistrar || !e.roles.includes('registrar')) &&
            (!sponsor || !e.roles.includes('registrar') || e.roles.includes('sponsor'))
        )
        .map(e => ({
            id: `e-${d.ldhName}-${e.entity.handle}`,
            source: e.roles.includes('registrant') || e.roles.includes('registrar') ? e.entity.handle : d.ldhName,
            target: e.roles.includes('registrant') || e.roles.includes('registrar') ? d.ldhName : e.entity.handle,
            style: {stroke: rolesToColor(e.roles), strokeWidth: 3},
            label: e.roles
                .map(r => rdapRoleTranslated[r as keyof typeof rdapRoleTranslated] || r)
                .join(', '),
            animated: e.roles.includes('registrant'),
        }))
}

export const domainNSToEdges = (d: Domain) => d.nameservers
    .map(ns => ({
        id: `ns-${d.ldhName}-${ns.ldhName}`,
        source: d.ldhName,
        target: ns.ldhName,
        style: {stroke: 'grey', strokeWidth: 3},
        label: 'DNS'
    }))

export const tldToEdge = (d: Domain) => ({
    id: `tld-${d.ldhName}-${d.tld.tld}`,
    source: d.tld.tld,
    target: d.ldhName,
    style: {stroke: 'yellow', strokeWidth: 3},
    label: t`Registry`
})

export function watchlistToEdges(watchlist: Watchlist, withRegistrar = false, withTld = false) {
    const entitiesEdges = watchlist.domains.map(d => domainEntitiesToEdges(d, withRegistrar)).flat()
    const nameserversEdges = watchlist.domains.map(domainNSToEdges).flat()
    const tldEdge = watchlist.domains.map(tldToEdge)

    return [...entitiesEdges, ...nameserversEdges, ...(withTld ? tldEdge : [])]
}
