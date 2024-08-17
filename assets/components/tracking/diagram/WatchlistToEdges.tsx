import {Watchlist} from "../../../utils/api";
import {translateRoles} from "../../search/EntitiesList";

const rolesToColor = (roles: string[]) => roles.includes('registrant') ? 'green' :
    roles.includes('administrative') ? 'blue' :
        roles.includes('technical') ? 'orange' : 'violet'


export function watchlistToEdges(watchlist: Watchlist) {
    const domainRole = translateRoles()

    const entitiesEdges = watchlist.domains
        .map(d => d.entities
            .filter(e => !e.roles.includes('registrar')) //
            .map(e => ({
                id: `e-${d.ldhName}-${e.entity.handle}`,
                source: e.roles.includes('registrant') ? e.entity.handle : d.ldhName,
                target: e.roles.includes('registrant') ? d.ldhName : e.entity.handle,
                style: {stroke: rolesToColor(e.roles), strokeWidth: 3},
                label: e.roles.map(r => Object.keys(domainRole).includes(r) ? domainRole[r as keyof typeof domainRole] : r).join(', '),
                animated: e.roles.includes('registrant'),
            }))
        ).flat()

    const nameserversEdges = watchlist.domains
        .map(d => d.nameservers
            .map(ns => ({
                id: `ns-${d.ldhName}-${ns.ldhName}`,
                source: d.ldhName,
                target: ns.ldhName,
                style: {stroke: 'grey', strokeWidth: 3},
                label: 'DNS'
            }))).flat()

    return [...entitiesEdges, ...nameserversEdges]
}
