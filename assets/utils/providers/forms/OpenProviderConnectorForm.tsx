import {Alert, Col, Form, Input, InputNumber, Row, Typography} from 'antd'
import React from 'react'
import {t} from 'ttag'
import {
    DollarOutlined,
    FieldTimeOutlined,
    IdcardOutlined,
    ShoppingOutlined,
    SignatureOutlined,
    ToolOutlined,
    UserOutlined
} from "@ant-design/icons"

export default function OpenProviderConnectorForm() {
    return (
        <>
            <Alert
                message={t`This provider does not provide a list of supported TLD. Please double check if the domain you want to register is supported.`}
                type='warning'
                style={{marginBottom: '2em'}}
            />

            <Form.Item
                label={t`Token`}
                name={['authData', 'token']}
                help={<Typography.Link target='_blank' href='https://docs.openprovider.com/doc/all#tag/Auth'>
                    {t`Obtain an API key by following the provider's instructions`}
                </Typography.Link>}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input prefix={<UserOutlined/>} autoComplete='off' required/>
            </Form.Item>
            <Form.Item label={t`NIC Handle`}>
                <Row gutter={16}>
                    <Col span={4}>
                        <Form.Item
                            hasFeedback
                            required
                            rules={[{required: true, message: t`Required`}]}
                            name={['authData', 'ownerHandle']}>
                            <Input prefix={<SignatureOutlined/>} placeholder={t`Registrant`} required/>
                        </Form.Item>
                    </Col>
                    <Col span={4}>
                        <Form.Item hasFeedback
                                   required
                                   rules={[{required: true, message: t`Required`}]} name={['authData', 'adminHandle']}>
                            <Input prefix={<IdcardOutlined/>} placeholder={t`Administrative`}/>
                        </Form.Item>
                    </Col>
                    <Col span={4}>
                        <Form.Item hasFeedback
                                   required
                                   rules={[{required: true, message: t`Required`}]} name={['authData', 'techHandle']}>
                            <Input prefix={<ToolOutlined/>} placeholder={t`Technical`}/>
                        </Form.Item>
                    </Col>
                    <Col span={4}>
                        <Form.Item hasFeedback
                                   required
                                   rules={[{required: true, message: t`Required`}]}
                                   name={['authData', 'billingHandle']}>
                            <Input prefix={<DollarOutlined/>} placeholder={t`Billing`}/>
                        </Form.Item>
                    </Col>
                    <Col span={4}>
                        <Form.Item name={['authData', 'resellerHandle']}>
                            <Input prefix={<ShoppingOutlined/>} placeholder={t`Reseller`}/>
                        </Form.Item>
                    </Col>
                </Row>
            </Form.Item>
            <Form.Item
                label={t`Registration period`}
                initialValue={1}
                hasFeedback
                rules={[{
                    required: true,
                    message: t`Required`,
                    validator: (_, v: number) => v > 0 && v < 100 ? Promise.resolve() : Promise.reject()
                }]}
                name={['authData', 'period']}
            >
                <InputNumber prefix={<FieldTimeOutlined/>} required/>
            </Form.Item>

            <Form.Item
                label={t`Nameserver group`}
                name={['authData', 'nsGroup']}
                help={<Typography.Link target='_blank'
                                       href='https://cp.openprovider.eu/nameserver/nsgroup-overview.php'>
                    {t`Create an NS group and write the group name here`}
                </Typography.Link>}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input prefix={<UserOutlined/>} autoComplete='off' required/>
            </Form.Item>
        </>
    )
}
