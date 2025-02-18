import type {FormInstance} from 'antd'
import type {Connector} from '../../../utils/api/connectors'
import {ConnectorProvider} from '../../../utils/api/connectors'
import {providersConfig} from "../../../utils/providers"

export function ConnectorForm({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) {
    return providersConfig()[ConnectorProvider.OVHcloud].form({form, onCreate})
}
