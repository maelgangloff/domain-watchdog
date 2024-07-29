import React, {useEffect, useState} from "react";
import {Card, Flex, Form, message, Skeleton} from "antd";
import {t} from "ttag";
import {Connector, getConnectors, postConnector} from "../../utils/api/connectors";
import {ConnectorForm} from "../../components/tracking/ConnectorForm";
import {AxiosError} from "axios";
import {ConnectorsList} from "../../components/tracking/ConnectorsList";

type ConnectorElement = Connector & { id: string }

export default function ConnectorsPage() {
    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [connectors, setConnectors] = useState<ConnectorElement[] | null>()

    const onCreateConnector = (values: Connector) => {
        postConnector(values).then((w) => {
            form.resetFields()
            refreshConnectors()
            messageApi.success(t`Connector created !`)
        }).catch((e: AxiosError) => {
            const data = e?.response?.data as { detail: string }
            messageApi.error(data.detail ?? t`An error occurred`)
        })
    }

    const refreshConnectors = () => getConnectors().then(c => {
        setConnectors(c['hydra:member'])
    }).catch((e: AxiosError) => {
        const data = e?.response?.data as { detail: string }
        messageApi.error(data.detail ?? t`An error occurred`)
        setConnectors(undefined)
    })

    useEffect(() => {
        refreshConnectors()
    }, [])


    return <Flex gap="middle" align="center" justify="center" vertical>
        <Card title={t`Create a Connector`} style={{width: '100%'}}>
            {contextHolder}
            <ConnectorForm form={form} onCreate={onCreateConnector}/>
        </Card>


        <Skeleton loading={connectors === undefined} active>
            {connectors && connectors.length > 0 && <Card title={t`My Connectors`} style={{width: '100%'}}>
                <ConnectorsList connectors={connectors} onDelete={refreshConnectors}/>
            </Card>
            }
        </Skeleton>
    </Flex>
}