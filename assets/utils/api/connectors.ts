import {request} from './index'
import type {ConnectorElement} from '../../components/tracking/connector/ConnectorsList'

export enum ConnectorProvider {
    OVHcloud = 'ovh',
    Gandi = 'gandi',
    AutoDNS = 'autodns',
    Namecheap = 'namecheap',
    'Name.com' = 'namecom',
    EPP = 'epp'
}

export interface Connector {
    provider: ConnectorProvider
    authData: Record<string, Record<string, string>>,

    objURI?: { key: string, value: string }[],
    extURI?: { key: string, value: string }[]
}

interface ConnectorResponse {
    'hydra:totalItems': number
    'hydra:member': ConnectorElement[]
}

export async function getConnectors(): Promise<ConnectorResponse> {
    const response = await request<ConnectorResponse>({
        url: 'connectors'
    })
    return response.data
}

export async function postConnector(connector: Connector) {

    for (const key of ['objURI', 'extURI'] as (keyof Connector)[]) {
        if (key in connector) {
            const obj = connector[key] as { key: string, value: string }[]
            connector.authData[key] = obj.reduce((acc: { [key: string]: string }, x) => ({
                ...acc,
                [x.key]: x.value
            }), {})
            delete connector[key]
        }
    }

    const response = await request<Connector & { id: string }>({
        method: 'POST',
        url: 'connectors',
        data: connector,
        headers: {
            'Content-Type': 'application/json'
        }
    })
    return response.data
}

export async function deleteConnector(token: string): Promise<void> {
    await request({
        method: 'DELETE',
        url: 'connectors/' + token
    })
}
