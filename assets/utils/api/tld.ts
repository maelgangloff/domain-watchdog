import {request} from "./index";

interface Tld {
    tld: string
    contractTerminated: boolean
    registryOperator: string
    specification13: boolean
}

export async function getTldList(params: object): Promise<Tld[]> {
    let page = 1
    let response = (await request<Tld[]>({
        url: 'tld',
        params: {...params, page},
    })).data
    const tldList: Tld[] = response;

    while (response.length !== 0) {
        page++
        response = (await request<Tld[]>({
            url: 'tld',
            params: {...params, page},
        })).data

        tldList.push(...response)
    }
    return tldList
}