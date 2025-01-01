import type {Domain} from '.'
import {request} from '.'

export async function getDomain(ldhName: string, forced = false): Promise<Domain> {
    const response = await request<Domain>({
        url: 'domains/' + ldhName,
        params: {forced}
    })
    return response.data
}
