import type { Tld} from './index'
import {request} from './index'

interface TldList {
    'hydra:totalItems': number
    'hydra:member': Tld[]
}

export async function getTldList(params: object): Promise<TldList> {
    return (await request<TldList>({
        url: 'tld',
        params
    })).data
}
