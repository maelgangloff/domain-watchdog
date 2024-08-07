import React, {useEffect, useState} from "react";
import {Card, Flex, Form, message, Skeleton} from "antd";
import {t} from "ttag";
import {Connector, getConnectors, postConnector} from "../../utils/api/connectors";
import {ConnectorForm} from "../../components/tracking/ConnectorForm";
import {AxiosError} from "axios";
import {ConnectorElement, ConnectorsList} from "../../components/tracking/ConnectorsList";
import {showErrorAPI} from "../../utils";

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
            showErrorAPI(e, messageApi)
        })
    }

    const refreshConnectors = () => getConnectors().then(c => {
        setConnectors(c['hydra:member'])
    }).catch((e: AxiosError) => {
        setConnectors(undefined)
        showErrorAPI(e, messageApi)
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
            {connectors && connectors.length > 0 &&
                <ConnectorsList connectors={connectors} onDelete={refreshConnectors}/>
            }
        </Skeleton>
    </Flex>
}