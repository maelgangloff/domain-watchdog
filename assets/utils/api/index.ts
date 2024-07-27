import axios, {AxiosRequestConfig, AxiosResponse} from "axios";


export type EventAction =
    'registration'
    | 'reregistration'
    | 'last changed'
    | 'expiration'
    | 'deletion'
    | 'reinstantiation'
    | 'transfer'
    | 'locked'
    | 'unlocked'
    | 'last update of RDAP database'
    | 'registrar expiration'
    | 'enum validation expiration'
    | string

export interface Event {
    action: EventAction
    date: string
}

export interface Entity {
    handle: string
    jCard: any
}

export interface Nameserver {
    ldhName: string
    entities: Entity[]
}

export interface Tld {
    tld: string
    contractTerminated: boolean
    dateOfContractSignature: string
    registryOperator: string
    delegationDate: string
    removalDate: string
    specification13: boolean
    type: string
}

export interface Domain {
    ldhName: string
    handle: string
    status: string[]
    events: Event[]
    entities: {
        entity: Entity
        events: Event[]
        roles: string[]
    }[]
    nameservers: Nameserver[]
    tld: Tld
    deleted: boolean
}

export interface User {
    email: string
    roles: string[]
}

export interface Watchlist {
    domains: string[]
    triggers: Event[]
}

export async function request<T = any, R = AxiosResponse<T>, D = any>(config: AxiosRequestConfig): Promise<R> {
    const axiosConfig: AxiosRequestConfig = {
        ...config,
        baseURL: '/api',
        withCredentials: true,
        headers: {
            ...config.headers,
            Accept: 'application/ld+json'
        }
    }
    return await axios.request<T, R, D>(axiosConfig)
}

export * from './domain'
export * from './tld'
export * from './user'
export * from './watchlist'


