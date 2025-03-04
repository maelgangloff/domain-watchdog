import {t} from 'ttag'
import {regionNames} from "../../../i18n"
import React, {useState} from 'react'
import type {FormInstance} from "antd"
import {Form, Input, Popconfirm, Select, Typography} from "antd"
import {AppstoreOutlined, LockOutlined, UserOutlined} from "@ant-design/icons"

export default function OvhCloudConnectorForm({form}: {
    form: FormInstance
}) {
    const [open, setOpen] = useState(false)
    const [ovhPricingModeValue, setOvhPricingModeValue] = useState<string | undefined>()

    const ovhEndpointList = [
        {label: t`European Region`, value: 'ovh-eu'},
        {label: t`United States Region`, value: 'ovh-us'},
        {label: t`Canada Region`, value: 'ovh-ca'}
    ]

    const ovhSubsidiaryList = [...[
        'CZ', 'DE', 'ES', 'FI', 'FR', 'GB', 'IE', 'IT', 'LT', 'MA', 'NL', 'PL', 'PT', 'SN', 'TN'
    ].map(c => ({value: c, label: regionNames.of(c) ?? c})), {value: 'EU', label: t`Europe`}]

    const ovhPricingMode = [
        {value: 'create-default', label: t`The domain is free and at the standard price`},
        {
            value: 'create-premium',
            label: t`The domain is free but can be premium. Its price varies from one domain to another`
        }
    ]

    return (
        <>
            <Form.Item
                label={t`Application key`}
                name={['authData', 'appKey']}
                rules={[{required: true, message: t`Required`}]}
                help={<Typography.Link
                    target='_blank'
                    href='https://api.ovh.com/createToken/?GET=/order/cart&GET=/order/cart/*&POST=/order/cart&POST=/order/cart/*&DELETE=/order/cart/*&GET=/domain/extensions'
                >
                    {t`Retrieve a set of tokens from your customer account on the Provider's website`}
                </Typography.Link>}
            >
                <Input prefix={<LockOutlined/>} autoComplete='off'/>
            </Form.Item>

            <Form.Item
                label={t`Application secret`}
                name={['authData', 'appSecret']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input prefix={<AppstoreOutlined/>} autoComplete='off'/>
            </Form.Item>

            <Form.Item
                label={t`Consumer key`}
                name={['authData', 'consumerKey']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input prefix={<UserOutlined/>} autoComplete='off'/>
            </Form.Item>

            <Form.Item
                label={t`OVH Endpoint`}
                name={['authData', 'apiEndpoint']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Select options={ovhEndpointList} optionFilterProp='label'/>
            </Form.Item>
            <Form.Item
                label={t`OVH subsidiary`}
                name={['authData', 'ovhSubsidiary']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Select options={ovhSubsidiaryList} optionFilterProp='label'/>
            </Form.Item>

            <Form.Item
                label={t`OVH pricing mode`}
                name={['authData', 'pricingMode']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Popconfirm
                    title={t`Confirm pricing mode`}
                    description={t`Are you sure about this setting? This may result in additional charges from the API Provider`}
                    onCancel={() => {
                        form.resetFields(['authData'])
                        setOvhPricingModeValue(undefined)
                        setOpen(false)
                    }}
                    onConfirm={() => setOpen(false)}
                    open={open}
                >
                    <Select
                        options={ovhPricingMode} optionFilterProp='label' value={ovhPricingModeValue}
                        onChange={(value: string) => {
                            setOvhPricingModeValue(value)
                            form.setFieldValue(['authData', 'pricingMode'], value)
                            if (value !== 'create-default') {
                                setOpen(true)
                            }
                        }}
                    />
                </Popconfirm>
            </Form.Item>
        </>
    )
}
