import React from "react"
import {t} from "ttag"
import {Alert, Card, Col, Form, Input, InputNumber, Row, Select, Switch} from "antd"

export default function EppConnectorForm() {

    return <>
        <Alert
            message={undefined}
            type='info'
            style={{marginBottom: '2em'}}
        />

        <Card size="small" title={t`Server configuration`} bordered={false}>
            <Form.Item label={t`Protocol`}>
                <Row gutter={16}>
                    <Col span={12}>
                        <Form.Item
                            help={t`Version`}
                            name={['authData', 'version']}
                            initialValue='1.0'
                            hasFeedback
                            rules={[{required: true, message: t`Required`}]}
                        >
                            <Input autoComplete='off' required/>
                        </Form.Item>
                    </Col>

                    <Col span={12}>
                        <Form.Item
                            help={t`Language`}
                            name={['authData', 'language']}
                            initialValue='en'
                            hasFeedback
                            rules={[{required: true, message: t`Required`}]}
                        >
                            <Input autoComplete='off' required/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>

            <Form.Item
                label={t`Server`}
                name={['authData', 'hostname']}
                hasFeedback
                rules={[{required: true, message: t`Required`}]}>
                <Input addonAfter={
                    <Form.Item
                        name={['authData', 'port']}
                        hasFeedback
                        noStyle
                        initialValue={700}
                        rules={[{required: true, message: t`Required`}]}
                    >
                        <InputNumber autoComplete='off' required/>
                    </Form.Item>
                } placeholder='ssl://epp.nic.tld' autoComplete='off' required/>
            </Form.Item>
        </Card>


        <Card size="small" title={t`Authentication`} bordered={false}>
            <Form.Item label={t`Credentials`}>
                <Row gutter={16}>
                    <Col span={12}>
                        <Form.Item
                            name={['authData', 'auth', 'username']}
                            hasFeedback
                            rules={[{required: true, message: t`Required`}]}
                        >
                            <Input placeholder={t`Username`} autoComplete='off' required/>
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item
                            name={['authData', 'auth', 'password']}
                            hasFeedback
                            rules={[{required: true, message: t`Required`}]}
                        >
                            <Input placeholder={t`Password`} autoComplete='off' required/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>

            <Form.Item label={t`TLS client certificate`}>
                <Row gutter={16}>
                    <Col span={12}>
                        <Form.Item
                            hasFeedback
                            name={['authData', 'certificate_pem']}
                            rules={[{
                                required: false,
                                validator: (_, value: string) =>
                                    value.trim().startsWith('-----BEGIN CERTIFICATE-----') && value.trim().endsWith('-----END CERTIFICATE-----') ?
                                        Promise.resolve() :
                                        Promise.reject(new Error(t`The private key format is invalid`))
                            }]}
                        >
                            <Input.TextArea rows={5} placeholder={`-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----`}/>
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name={['authData', 'certificate_key']}>
                            <Input.TextArea rows={5} placeholder={`-----BEGIN PRIVATE KEY-----
...
-----END PRIVATE KEY-----`}/>
                        </Form.Item>
                        <Form.Item name={['authData', 'ssl', 'passphrase']}>
                            <Input placeholder={t`Private key passphrase (optional)`} autoComplete='off'/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>

            <Form.Item label={t`TLS configuration`}>
                <Row gutter={16}>
                    <Col span={8}>
                        <Form.Item
                            initialValue={true}
                            help={t`Verify peer`}
                            name={['authData', 'ssl', 'verify_peer']}
                        >
                            <Switch/>
                        </Form.Item>
                    </Col>
                    <Col span={8}>
                        <Form.Item
                            initialValue={true}
                            help={t`Verify peer name`}
                            name={['authData', 'ssl', 'verify_peer_name']}
                        >
                            <Switch/>
                        </Form.Item>
                    </Col>
                    <Col span={8}>
                        <Form.Item
                            initialValue={false}
                            help={t`Allow self-signed certificates`}
                            name={['authData', 'ssl', 'allow_self_signed']}
                        >
                            <Switch/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>
        </Card>

        <Card size="small" title={t`Domain configuration`} bordered={false}>
            <Form.Item
                label={t`Registration period`}
                initialValue={1}
                hasFeedback
                rules={[{
                    required: true,
                    message: t`Required`,
                    validator: (_, v: number) => v > 0 && v < 100 ? Promise.resolve() : Promise.reject()
                }]}
                name={['authData', 'domain', 'period']}
            >
                <InputNumber addonAfter={
                    <Form.Item name={['authData', 'domain', 'unit']} noStyle initialValue={'y'}>
                        <Select style={{width: 100}}>
                            <Select.Option value="y">{t`Year`}</Select.Option>
                            <Select.Option value="m">{t`Month`}</Select.Option>
                        </Select>
                    </Form.Item>
                } required/>
            </Form.Item>

            <Form.Item
                label={t`Auth-Info Code`}
                hasFeedback
                rules={[{required: true, message: t`Required`}]}
                name={['authData', 'domain', 'password']}
            >
                <Input required/>
            </Form.Item>

            <Form.Item label={t`NIC Handle`}>
                <Row gutter={16}>
                    <Col span={6}>
                        <Form.Item
                            hasFeedback
                            required
                            rules={[{required: true, message: t`Required`}]}
                            name={['authData', 'domain', 'registrant']}>
                            <Input placeholder={t`Registrant`} required/>
                        </Form.Item>
                    </Col>
                    <Col span={6}>
                        <Form.Item name={['authData', 'domain', 'contacts', 'admin']}>
                            <Input placeholder={t`Administrative`}/>
                        </Form.Item>
                    </Col>
                    <Col span={6}>
                        <Form.Item name={['authData', 'domain', 'contacts', 'tech']}>
                            <Input placeholder={t`Technical`}/>
                        </Form.Item>
                    </Col>
                    <Col span={6}>
                        <Form.Item name={['authData', 'domain', 'contacts', 'billing']}>
                            <Input placeholder={t`Billing`}/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>
        </Card>

    </>
}