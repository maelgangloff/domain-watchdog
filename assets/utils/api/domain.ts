import type {Domain} from '.'
import { request} from '.'

export async function getDomain(ldhName: string): Promise<Domain> {
    const response = await request<Domain>({
        url: 'domains/' + ldhName
    })
    return response.data
}
