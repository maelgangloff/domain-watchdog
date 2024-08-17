import {Domain, Watchlist} from "../../../../utils/api";
import {translateRoles} from "../../../search/EntitiesList";
import {t} from "ttag";

const rolesToColor = (roles: string[]) => roles.includes('registrant') ? 'green' :
    roles.includes('administrative') ? 'blue' :
        roles.includes('technical') ? 'orange' : 'violet'

export function domainEntitiesToEdges(d: Domain) {
    const domainRole = translateRoles()
    return d.entities
        .filter(e => !e.roles.includes('registrar')) //
        .map(e => ({
            id: `e-${d.ldhName}-${e.entity.handle}`,
            source: e.roles.includes('registrant') ? e.entity.handle : d.ldhName,
            target: e.roles.includes('registrant') ? d.ldhName : e.entity.handle,
            style: {stroke: rolesToColor(e.roles), strokeWidth: 3},
            label: e.roles
                .map(r => Object.keys(domainRole).includes(r) ? domainRole[r as keyof typeof domainRole] : r)
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

export function watchlistToEdges(watchlist: Watchlist) {
    const entitiesEdges = watchlist.domains.map(domainEntitiesToEdges).flat()
    const nameserversEdges = watchlist.domains.map(domainNSToEdges).flat()

    return [...entitiesEdges, ...nameserversEdges]
}
