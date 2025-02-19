import React, {useEffect, useState} from 'react'
import {Card, Flex, Form, message, Skeleton} from 'antd'
import {t} from 'ttag'
import type {Connector} from '../../utils/api/connectors'
import { getConnectors, postConnector} from '../../utils/api/connectors'
import {ConnectorForm} from '../../components/tracking/connector/ConnectorForm'
import type {AxiosError} from 'axios'
import type {ConnectorElement} from '../../components/tracking/connector/ConnectorsList'
import { ConnectorsList} from '../../components/tracking/connector/ConnectorsList'

import {showErrorAPI} from '../../utils/functions/showErrorAPI'

export default function ConnectorPage() {
    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [connectors, setConnectors] = useState<ConnectorElement[] | null>()

    const onCreateConnector = (values: Connector) => {
        postConnector(values).then(() => {
            form.resetFields()
            refreshConnectors()
            messageApi.success(t`Connector created !`)
        }).catch((e: AxiosError) => {
            showErrorAPI(e, messageApi)
        })
    }

    const refreshConnectors = async () => await getConnectors().then(c => {
        setConnectors(c['hydra:member'])
    }).catch((e: AxiosError) => {
        setConnectors(undefined)
        showErrorAPI(e, messageApi)
    })

    useEffect(() => {
        refreshConnectors()
    }, [])

    return (
        <Flex gap='middle' align='center' justify='center' vertical>
            <Card title={t`Create a Connector`} style={{width: '100%'}} size='small'>
                {contextHolder}
                <ConnectorForm form={form} onCreate={onCreateConnector}/>
            </Card>

            <Skeleton loading={connectors === undefined} active>
                {(connectors != null) && connectors.length > 0 &&
                    <ConnectorsList connectors={connectors} onDelete={refreshConnectors}/>}
            </Skeleton>
        </Flex>
    )
}
