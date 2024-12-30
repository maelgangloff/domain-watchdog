import axios, {AxiosRequestConfig, AxiosResponse} from 'axios'

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

export type TriggerAction = 'email' | string

export interface Event {
    action: EventAction
    date: string
    deleted: boolean
}

export interface Entity {
    handle: string
    jCard: ['vcard', Array<[
        string,
        { [key: string]: string | string[] },
        string,
            string | string[],
    ]>] | []
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
    entities: Array<{
        entity: Entity
        events: Event[]
        roles: string[]
        deleted: boolean
    }>
    nameservers: Nameserver[]
    tld: Tld
    deleted: boolean
    updatedAt: string
    delegationSigned: boolean
}

export interface User {
    email: string
    roles: string[]
}

export interface WatchlistRequest {
    name?: string
    domains: string[]
    triggers: Array<{ event: EventAction, action: TriggerAction }>
    connector?: string
    dsn?: string[]
}

export interface Watchlist {
    name?: string
    token: string
    domains: Domain[]
    triggers?: Array<{ event: EventAction, action: string }>
    dsn?: string[]
    connector?: {
        id: string
        provider: string
        createdAt: string
    }
    createdAt: string
}

export interface InstanceConfig {
    ssoLogin: boolean
    limtedFeatures: boolean
    registerEnabled: boolean
}

export interface Statistics {
    rdapQueries: number
    alertSent: number
    domainPurchased: number
    domainPurchaseFailed: number
    domainCount: Array<{ tld: string, domain: number }>
    domainCountTotal: number
    domainTracked: number
}

export interface TrackedDomains {
    'hydra:totalItems': number
    'hydra:member': Domain[]
}

export async function request<T = object, R = AxiosResponse<T>, D = object>(config: AxiosRequestConfig): Promise<R> {
    const axiosConfig: AxiosRequestConfig = {
        ...config,
        baseURL: '/api',
        withCredentials: true,
        headers: {
            Accept: 'application/ld+json',
            ...config.headers
        }
    }
    return await axios.request<T, R, D>(axiosConfig)
}

export * from './domain'
export * from './tld'
export * from './user'
export * from './watchlist'
