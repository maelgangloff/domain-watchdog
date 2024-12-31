import {request} from './index'
import type {ConnectorElement} from '../../components/tracking/connector/ConnectorsList'

export enum ConnectorProvider {
    OVH = 'ovh',
    GANDI = 'gandi',
    AUTODNS = 'autodns',
    NAMECHEAP = 'namecheap'
}

export interface Connector {
    provider: ConnectorProvider
    authData: object
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
