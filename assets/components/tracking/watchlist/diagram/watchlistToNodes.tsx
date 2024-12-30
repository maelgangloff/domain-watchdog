import {Domain, Nameserver, Tld, Watchlist} from '../../../../utils/api'
import React from 'react'
import {t} from 'ttag'

import {entityToName} from '../../../../utils/functions/entityToName'
import {Node} from '@xyflow/react'

export const domainToNode = (d: Domain): Node => ({
    id: d.ldhName,
    position: {x: 0, y: 0},
    data: {label: <b>{d.ldhName}</b>},
    style: {
        width: 200
    }
})

export const domainEntitiesToNode = (d: Domain, withRegistrar = false): Node[] => {
    const sponsor = d.entities.find(e => !e.deleted && e.roles.includes('sponsor'))
    return d.entities
        .filter(e =>
            !e.deleted &&
            (withRegistrar || !e.roles.includes('registrar')) &&
            ((sponsor == null) || !e.roles.includes('registrar') || e.roles.includes('sponsor'))
        )
        .map(e => {
            return {
                id: e.entity.handle,
                position: {x: 0, y: 0},
                type: e.roles.includes('registrant') || e.roles.includes('registrar') ? 'input' : 'output',
                data: {label: entityToName(e)},
                style: {
                    width: 200
                }
            }
        })
}

export const tldToNode = (tld: Tld): Node => ({
    id: tld.tld,
    position: {x: 0, y: 0},
    data: {label: t`.${tld.tld} Registry`},
    type: 'input',
    style: {
        width: 200
    }
})

export const nsToNode = (ns: Nameserver): Node => ({
    id: ns.ldhName,
    position: {x: 0, y: 0},
    data: {label: ns.ldhName},
    type: 'output',
    style: {
        width: 200
    }
})

export function watchlistToNodes(watchlist: Watchlist, withRegistrar = false, withTld = false): Node[] {
    const domains = watchlist.domains.map(domainToNode)
    const entities = [...new Set(watchlist.domains.map(d => domainEntitiesToNode(d, withRegistrar)).flat())]
    const tlds = [...new Set(watchlist.domains.map(d => d.tld))].filter(t => t.tld !== '.').map(tldToNode)
    const nameservers = [...new Set(watchlist.domains.map(d => d.nameservers))].flat().map(nsToNode, withRegistrar)

    return [...domains, ...entities, ...nameservers, ...(withTld ? tlds : [])]
}
