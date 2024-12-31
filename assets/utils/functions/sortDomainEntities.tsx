import type {Domain} from '../api'

export const sortDomainEntities = (domain: Domain) => domain.entities
    .filter(e => !e.deleted)
    .sort((e1, e2) => {
        const p = (r: string[]) => r.includes('registrant')
            ? 5
            : r.includes('administrative')
                ? 4
                : r.includes('billing')
                    ? 3
                    : r.includes('registrar') ? 2 : 1
        return p(e2.roles) - p(e1.roles)
    })
