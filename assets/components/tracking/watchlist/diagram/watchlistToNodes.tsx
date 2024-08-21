import {Domain, Nameserver, Tld, Watchlist} from "../../../../utils/api";
import React from "react";
import {t} from 'ttag'

import {entityToName} from "../../../../utils/functions/entityToName";

export const domainToNode = (d: Domain) => ({
    id: d.ldhName,
    data: {label: <b>{d.ldhName}</b>},
    style: {
        width: 200
    }
})

export const domainEntitiesToNode = (d: Domain, withRegistrar = false) => d.entities
    .filter(e => !withRegistrar ? !e.roles.includes('registrar') : true)
    .map(e => {
        return {
            id: e.entity.handle,
            type: e.roles.includes('registrant') || e.roles.includes('registrar') ? 'input' : 'output',
            data: {label: entityToName(e)},
            style: {
                width: 200
            }
        }
    })

export const tldToNode = (tld: Tld) => ({
    id: tld.tld,
    data: {label: t`.${tld.tld} Registry`},
    type: 'input',
    style: {
        width: 200
    }
})

export const nsToNode = (ns: Nameserver) => ({
    id: ns.ldhName,
    data: {label: ns.ldhName},
    type: 'output',
    style: {
        width: 200
    }
})

export function watchlistToNodes(watchlist: Watchlist, withRegistrar = false, withTld = false) {

    const domains = watchlist.domains.map(domainToNode)
    const entities = [...new Set(watchlist.domains.map(d => domainEntitiesToNode(d, withRegistrar)).flat())]
    const tlds = [...new Set(watchlist.domains.map(d => d.tld))].map(tldToNode)
    const nameservers = [...new Set(watchlist.domains.map(d => d.nameservers))].flat().map(nsToNode, withRegistrar)

    return [...domains, ...entities, ...nameservers, ...(withTld ? tlds : [])]
}