import type {IcannAccreditation} from './index'
import {request} from './index'

interface IcannAccreditationList {
    'hydra:totalItems': number
    'hydra:member': IcannAccreditation[]
}

export async function getIcannAccreditations(params: object): Promise<IcannAccreditationList> {
    return (await request<IcannAccreditationList>({
        url: 'entities/icann-accreditations',
        params
    })).data
}
