import React from "react"
import {t} from "ttag"
import {Alert, Card, Col, Form, Input, InputNumber, Row, Select, Space, Switch} from "antd"
import {
    CloseOutlined,
    DatabaseOutlined,
    DollarOutlined,
    FieldTimeOutlined,
    IdcardOutlined,
    KeyOutlined,
    LockOutlined,
    PlusOutlined,
    SignatureOutlined,
    ToolOutlined,
    UserOutlined
} from "@ant-design/icons"

const DynamicKeyValueList = ({name, label, initialValue, keyPlaceholder, valuePlaceholder}: {
    name: string[],
    label: string,
    initialValue: { [key: string]: string }[],
    keyPlaceholder: string,
    valuePlaceholder: string
}) => <Form.Item label={label}>
    <Form.List name={name} initialValue={initialValue}>
        {(subFields, subOpt) => (
            <>
                {subFields.map((subField, index) => (
                    <Row key={subField.key} gutter={[16, 16]}>
                        <Col span={10}>
                            <Form.Item name={[subField.name, 'key']}>
                                <Input placeholder={keyPlaceholder}/>
                            </Form.Item>
                        </Col>
                        <Col span={10}>
                            <Form.Item name={[subField.name, 'value']}>
                                <Input placeholder={valuePlaceholder}/>
                            </Form.Item>
                        </Col>
                        <Col span={4}>
                            <Space>
                                <CloseOutlined
                                    onClick={() => subOpt.remove(subField.name)}
                                />
                                {index === subFields.length - 1 && <PlusOutlined onClick={() => subOpt.add()}/>}
                            </Space>
                        </Col>
                    </Row>
                ))}
            </>
        )}
    </Form.List>
</Form.Item>

export default function EppConnectorForm() {

    return <>
        <Alert
            message={t`The EPP connector is a special type of connector. Be careful.`}
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
                <Input prefix={<DatabaseOutlined/>} addonAfter={
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
                            <Input prefix={<UserOutlined/>} placeholder={t`Username`} autoComplete='off' required/>
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item
                            name={['authData', 'auth', 'password']}
                            hasFeedback
                            rules={[{required: true, message: t`Required`}]}
                        >
                            <Input prefix={<LockOutlined/>} placeholder={t`Password`} autoComplete='off' required/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>

            <Form.Item label={t`TLS client certificate`}>
                <Row gutter={16}>
                    <Col span={12}>
                        <Form.Item
                            hasFeedback
                            name={['authData', 'certificate_pem']}>
                            <Input.TextArea rows={5} placeholder={`-----BEGIN CERTIFICATE-----
...
-----END CERTIFICATE-----`}/>
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name={['authData', 'certificate_key']}>
                            <Input.TextArea rows={5} placeholder={`-----BEGIN ENCRYPTED PRIVATE KEY-----
...
-----END ENCRYPTED PRIVATE KEY-----`}/>
                        </Form.Item>
                        <Form.Item name={['authData', 'auth', 'ssl', 'passphrase']}>
                            <Input prefix={<KeyOutlined/>} placeholder={t`Private key passphrase (optional)`}
                                   autoComplete='off'/>
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
                            name={['authData', 'auth', 'ssl', 'verify_peer']}
                        >
                            <Switch/>
                        </Form.Item>
                    </Col>
                    <Col span={8}>
                        <Form.Item
                            initialValue={true}
                            help={t`Verify peer name`}
                            name={['authData', 'auth', 'ssl', 'verify_peer_name']}
                        >
                            <Switch/>
                        </Form.Item>
                    </Col>
                    <Col span={8}>
                        <Form.Item
                            initialValue={false}
                            help={t`Allow self-signed certificates`}
                            name={['authData', 'auth', 'ssl', 'allow_self_signed']}
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
                <InputNumber prefix={<FieldTimeOutlined/>} addonAfter={
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
                <Input prefix={<LockOutlined/>} required/>
            </Form.Item>

            <Form.Item label={t`NIC Handle`}>
                <Row gutter={16}>
                    <Col span={6}>
                        <Form.Item
                            hasFeedback
                            required
                            rules={[{required: true, message: t`Required`}]}
                            name={['authData', 'domain', 'registrant']}>
                            <Input prefix={<SignatureOutlined/>} placeholder={t`Registrant`} required/>
                        </Form.Item>
                    </Col>
                    <Col span={6}>
                        <Form.Item name={['authData', 'domain', 'contacts', 'admin']}>
                            <Input prefix={<IdcardOutlined/>} placeholder={t`Administrative`}/>
                        </Form.Item>
                    </Col>
                    <Col span={6}>
                        <Form.Item name={['authData', 'domain', 'contacts', 'tech']}>
                            <Input prefix={<ToolOutlined/>} placeholder={t`Technical`}/>
                        </Form.Item>
                    </Col>
                    <Col span={6}>
                        <Form.Item name={['authData', 'domain', 'contacts', 'billing']}>
                            <Input prefix={<DollarOutlined/>} placeholder={t`Billing`}/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>
        </Card>

        <Card size="small" title={t`Protocol configuration`} bordered={false}>
            <DynamicKeyValueList name={['objURI']} label={t`Services`} initialValue={[
                {key: 'urn:ietf:params:xml:ns:host-1.0', value: 'host'},
                {key: 'urn:ietf:params:xml:ns:contact-1.0', value: 'contact'},
                {key: 'urn:ietf:params:xml:ns:domain-1.0', value: 'domain'}
            ]} keyPlaceholder='urn:ietf:params:xml:ns:host-1.0' valuePlaceholder='host'/>

            <DynamicKeyValueList name={['extURI']} label={t`Extensions`} initialValue={[
                {key: 'urn:ietf:params:xml:ns:rgp-1.0', value: 'rgp'}
            ]} keyPlaceholder='urn:ietf:params:xml:ns:rgp-1.0' valuePlaceholder='rgp'/>

        </Card>
    </>
}