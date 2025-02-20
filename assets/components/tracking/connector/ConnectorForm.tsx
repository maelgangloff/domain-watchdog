import type {FormInstance} from 'antd'
import {Button, Card, Col, Form, Input, Row, Steps, Typography} from 'antd'
import type {Connector} from '../../../utils/api/connectors'
import {ConnectorProvider} from '../../../utils/api/connectors'
import React, {useState} from 'react'
import {t} from "ttag"
import {BankOutlined, LockOutlined, SignatureOutlined} from "@ant-design/icons"
import {formItemLayoutWithOutLabel, providersConfig} from "../../../utils/providers"
import DefaultConnectorFormItems from "../../../utils/providers/forms/DefaultConnectorFormItems"

export function ConnectorForm({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) {
    const [provider, setProvider] = useState<ConnectorProvider>()
    const [current, setCurrent] = useState(0)
    const ProviderForm = provider !== undefined ? providersConfig[provider].form : undefined
    const next = () => setCurrent(current + 1)
    const prev = () => setCurrent(current - 1)

    form.setFieldValue('provider', provider)

    return (
        <Form
            {...formItemLayoutWithOutLabel}
            form={form}
            layout='horizontal'
            labelCol={{span: 6}}
            wrapperCol={{span: 14}}
            onFinish={onCreate}
        >

            <Form.Item name="provider" noStyle>
                <Input type="hidden"/>
            </Form.Item>

            <Steps current={current} items={[
                {
                    title: t`Registrar`,
                    icon: <BankOutlined/>,
                },
                {
                    title: t`Authentication`,
                    icon: <LockOutlined/>,
                    disabled: current < 2
                },
                {
                    title: t`Consent`,
                    icon: <SignatureOutlined/>,
                    disabled: current < 1
                }
            ]} onChange={(c: number) => setCurrent(c)}/>
            <div style={{padding: 20}}>
                {current === 0 && (
                    <>
                        <h2>{t`Choose a registrar`}</h2>
                        <Row gutter={[16, 16]} justify='center'>
                            {Object.keys(providersConfig).map((provider: string) => (
                                <Col key={provider as ConnectorProvider} xs={24} sm={12} md={8} lg={6} xl={4}>
                                    <Card
                                        hoverable
                                        style={{textAlign: "center"}}
                                        onClick={() => {
                                            setProvider(provider as ConnectorProvider)
                                            next()
                                        }}
                                    >
                                        <div style={{fontSize: "3rem"}}>
                                            <BankOutlined style={{color: 'lightblue'}}/>
                                        </div>
                                        <h3>{Object.keys(ConnectorProvider).find(p => ConnectorProvider[p as keyof typeof ConnectorProvider] === provider)}</h3>
                                    </Card>
                                </Col>
                            ))}
                        </Row>
                    </>
                )}

                <div hidden={current !== 1}>
                    {ProviderForm && <ProviderForm form={form}/>}
                </div>
                <div hidden={current !== 2}>
                    {provider && <DefaultConnectorFormItems tosLink={providersConfig[provider].tosLink}/>}
                </div>

                <div style={{marginTop: 24}}>
                    {current > 0 &&
                        <Button style={{margin: '0 8px'}} onClick={() => prev()}>
                            {t`Previous`}
                        </Button>
                    }
                    {current === 1 &&
                        <Button type="primary" onClick={() => next()}>
                            {t`Next`}
                        </Button>
                    }
                    {current === 2 &&
                        <Button type='primary' htmlType='submit'>
                            {t`Create`}
                        </Button>
                    }
                </div>

            </div>
            <Typography.Text type='secondary'>
                {t`This website is neither affiliated with nor sponsored by the registrars mentioned.
                The names and logos of these companies are used for informational purposes only and remain registered trademarks of their respective owners.
                The use of their services via this website is subject to the terms and conditions set by each registrar and is the sole responsibility of the user.`}
            </Typography.Text>
        </Form>
    )
}
