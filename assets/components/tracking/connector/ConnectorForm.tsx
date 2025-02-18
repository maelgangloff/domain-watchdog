import type { FormInstance, StepProps} from 'antd'
import {Card, Col, Row, Steps, Typography} from 'antd'
import type {Connector} from '../../../utils/api/connectors'
import {ConnectorProvider} from '../../../utils/api/connectors'
import React, {useState} from 'react'
import {t} from "ttag"
import {BankOutlined, UserOutlined} from "@ant-design/icons"
import {providersConfig} from "../../../utils/providers"


export function ConnectorForm({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) {
    const [provider, setProvider] = useState<ConnectorProvider>()
    const [current, setCurrent] = useState(0)

    const ProviderForm = provider !== undefined ? providersConfig[provider].form : undefined

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
                            {Object.keys(providersConfig).map((provider: string) => (
                                <Col key={provider as ConnectorProvider} span={8}>
                                    <Card
                                        hoverable
                                        style={{textAlign: "center"}}
                                        onClick={() => {
                                            setProvider(provider as ConnectorProvider)
                                            next()
                                        }}
                                    >
                                        <div style={{fontSize: "3rem"}}><BankOutlined style={{color: 'lightblue'}}/>
                                        </div>
                                        <h3>{Object.keys(ConnectorProvider).find(p => ConnectorProvider[p as keyof typeof ConnectorProvider] === provider)}</h3>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    </>
                )}

                {current === 1 && ProviderForm && <ProviderForm form={form} onCreate={onCreate}/>}
            </div>
            <Typography.Text type='secondary'>
                {t`This website is neither affiliated with nor sponsored by the registrars mentioned.
                The names and logos of these companies are used for informational purposes only and remain registered trademarks of their respective owners.
                The use of their services via this website is subject to the terms and conditions set by each registrar and is the sole responsibility of the user.`}
            </Typography.Text>
        </>
    )
}
