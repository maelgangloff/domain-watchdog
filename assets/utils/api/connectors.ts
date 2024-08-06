import {request} from "./index";

export enum ConnectorProvider {
    OVH = 'ovh',
    GANDI = 'gandi'
}

export type Connector = {
    provider: ConnectorProvider
    authData: object
}

export async function getConnectors() {
    const response = await request({
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
            "Content-Type": 'application/json'
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
