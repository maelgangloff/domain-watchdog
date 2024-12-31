import vCard from 'vcf'
import type {Entity} from '../api'

export const extractDetailsFromJCard = (e: { entity: Entity }): {
    fn?: string
    organization?: string
} => {
    if (e.entity.jCard.length === 0) return {fn: e.entity.handle}
    const jCard = vCard.fromJSON(e.entity.jCard)
    const fn = jCard.data.fn && !Array.isArray(jCard.data.fn) ? jCard.data.fn.valueOf() : undefined
    const organization = jCard.data.org && !Array.isArray(jCard.data.org) ? jCard.data.org.valueOf() : undefined

    return {fn, organization}
}
