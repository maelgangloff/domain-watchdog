import type {FormInstance, StepProps} from 'antd'
import {Card, Col, Row, Steps} from 'antd'
import type {Connector} from '../../../utils/api/connectors'
import {ConnectorProvider} from '../../../utils/api/connectors'
import React, {useState} from 'react'
import {t} from "ttag"
import {BankOutlined, UserOutlined} from "@ant-design/icons"
import {providersConfig} from "../../../utils/providers"


export function ConnectorForm({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) {
    const [provider, setProvider] = useState<ConnectorProvider>()
    const providersConfigList = providersConfig()
    const [current, setCurrent] = useState(0)

    const ProviderForm = provider !== undefined ? providersConfigList[provider].form : undefined

    const steps: StepProps[] = [
        {
            title: t`Registrar`,
            icon: <BankOutlined/>,
        },
        {
            title: t`Authentication`,
            icon: <UserOutlined/>,
        }
    ]

    const next = () => {
        setCurrent(current + 1)
    }

    return (
        <>
            <Steps current={current} items={steps} onChange={(c: number) => setCurrent(c)}/>
            <div style={{padding: 20}}>
                {current === 0 && (
                    <>
                        <h2>{t`Choose a registrar`}</h2>
                        <Row gutter={[16, 16]}>
                            {Object.keys(providersConfigList).map((provider: string) => (
                                <Col key={provider as ConnectorProvider} span={8}>
                                    <Card
                                        hoverable
                                        style={{textAlign: "center"}}
                                        onClick={() => {
                                            setProvider(provider as ConnectorProvider)
                                            next()
                                        }}
                                    >
                                        <div style={{fontSize: "3rem"}}><BankOutlined/></div>
                                        <h3>{Object.keys(ConnectorProvider).find(p => ConnectorProvider[p as keyof typeof ConnectorProvider] === provider)}</h3>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    </>
                )}

                {current === 1 && ProviderForm && <ProviderForm form={form} onCreate={onCreate}/>}
            </div>
        </>
    )
}
