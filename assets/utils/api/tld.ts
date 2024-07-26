import {request} from "./index";

interface Tld {
    tld: string
    contractTerminated: boolean
    registryOperator: string
    specification13: boolean
}

export async function getTldList(params: object): Promise<any> {
    return (await request<Tld[]>({
        url: 'tld',
        params,
    })).data
}