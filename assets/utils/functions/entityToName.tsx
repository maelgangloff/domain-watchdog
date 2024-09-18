import {Entity} from "../api";
import vCard from "vcf";

export const entityToName = (e: { entity: Entity }): string => {
    if (e.entity.jCard.length === 0) return e.entity.handle

    const jCard = vCard.fromJSON(e.entity.jCard)
    let name = e.entity.handle
    if (jCard.data.fn && !Array.isArray(jCard.data.fn) && jCard.data.fn.valueOf() !== '') name = jCard.data.fn.valueOf()
    return name
}