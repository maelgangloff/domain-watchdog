import {ConnectorProvider} from '../api/connectors'
import OvhCloudConnectorForm from "./forms/OvhCloudConnectorForm"
import type {FormInstance} from "antd"
import type React from "react"
import GandiConnectorForm from "./forms/GandiConnectorForm"
import NamecheapConnectorForm from "./forms/NamecheapConnectorForm"
import AutoDnsConnectorForm from "./forms/AutoDnsConnectorForm"
import NamecomConnectorForm from "./forms/NamecomConnectorForm"

export const formItemLayoutWithOutLabel = {
    wrapperCol: {
        xs: {span: 24, offset: 0},
        sm: {span: 20, offset: 4}
    }
}

export type ProviderConfig = {
    tosLink: string
    form: ({form}: { form: FormInstance }) => React.ReactElement
}

export const providersConfig: Record<ConnectorProvider, ProviderConfig> = {
    [ConnectorProvider.OVHcloud]: {
        tosLink: 'https://www.ovhcloud.com/en/terms-and-conditions/contracts/',
        form: OvhCloudConnectorForm
    },
    [ConnectorProvider.Gandi]: {
        tosLink: 'https://www.gandi.net/en/contracts/terms-of-service',
        form: GandiConnectorForm
    },
    [ConnectorProvider.Namecheap]: {
        tosLink: 'https://www.namecheap.com/legal/universal/universal-tos/',
        form: NamecheapConnectorForm
    },
    [ConnectorProvider.AutoDNS]: {
        tosLink: 'https://www.internetx.com/agb/',
        form: AutoDnsConnectorForm
    },
    [ConnectorProvider["Name.com"]]: {
        tosLink: 'https://www.name.com/policies/',
        form: NamecomConnectorForm
    }
}
