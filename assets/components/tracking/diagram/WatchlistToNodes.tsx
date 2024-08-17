import {Watchlist} from "../../../utils/api";
import vCard from "vcf";
import React from "react";
import {t} from 'ttag'

export function watchlistToNodes(watchlist: Watchlist) {
    const domains = watchlist.domains.map(d => ({
        id: d.ldhName,
        data: {label: <b>{d.ldhName}</b>},
        style: {
            width: 200
        },
        parentId: d.tld.tld,
        extent: 'parent'
    }))
    const entities = [...new Set(watchlist.domains
        .map(d => d.entities
            .filter(e => !e.roles.includes('registrar')) //
            .map(e => {
                const jCard = vCard.fromJSON(e.entity.jCard)
                let label = e.entity.handle
                if (jCard.data.fn !== undefined && !Array.isArray(jCard.data.fn)) label = jCard.data.fn.valueOf()

                return {
                    id: e.entity.handle,
                    data: {label},
                    style: {
                        width: 200
                    },
                    parentId: d.tld.tld,
                    extent: 'parent'
                }
            })).flat())]

    const tlds = [...new Set(watchlist.domains.map(d => d.tld))].map(tld => ({
        id: tld.tld,
        data: {label: t`.${tld.tld} Registry`},
        style: {
            width: 200
        }
    }))

    const nameservers = [...new Set(watchlist.domains.map(d => d.nameservers))].flat().map(ns => ({
        id: ns.ldhName,
        data: {label: ns.ldhName},
        style: {
            width: 200
        }
    }))

    return [...domains, ...entities, ...nameservers]
}