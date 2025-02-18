import type {Connector} from '../api/connectors'
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
    tokenLink: string
    form: ({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) => React.ReactElement
}

export const providersConfig: Record<ConnectorProvider, ProviderConfig> = {
    [ConnectorProvider.OVHcloud]: {
        tosLink: 'https://www.ovhcloud.com/en/terms-and-conditions/contracts/',
        tokenLink: 'https://api.ovh.com/createToken/?GET=/order/cart&GET=/order/cart/*&POST=/order/cart&POST=/order/cart/*&DELETE=/order/cart/*&GET=/domain/extensions',
        form: OvhCloudConnectorForm
    },
    [ConnectorProvider.Gandi]: {
        tosLink: 'https://www.gandi.net/en/contracts/terms-of-service',
        tokenLink: 'https://admin.gandi.net/organizations/account/pat',
        form: GandiConnectorForm
    },
    [ConnectorProvider.Namecheap]: {
        tosLink: 'https://www.namecheap.com/legal/universal/universal-tos/',
        tokenLink: 'https://ap.www.namecheap.com/settings/tools/apiaccess/',
        form: NamecheapConnectorForm
    },
    [ConnectorProvider.AutoDNS]: {
        tosLink: 'https://www.internetx.com/agb/',
        tokenLink: 'https://en.autodns.com/domain-robot-api/',
        form: AutoDnsConnectorForm
    },
    [ConnectorProvider["Name.com"]]: {
        tosLink: 'https://www.name.com/policies/',
        tokenLink: 'https://www.name.com/account/settings/api',
        form: NamecomConnectorForm
    }
}
