import {t} from 'ttag'
import {regionNames} from "../../../i18n"
import React, {useState} from 'react'
import type {FormInstance} from "antd"
import {Form, Input, Popconfirm, Select} from "antd"
import type {Connector} from "../../api/connectors"
import {ConnectorProvider} from "../../api/connectors"
import DefaultConnectorFormItems from "./DefaultConnectorFormItems"
import {formItemLayoutWithOutLabel, providersConfig} from "../index"

export default function OvhCloudConnectorForm({form, onCreate}: {
    form: FormInstance,
    onCreate: (values: Connector) => void
}) {
    const [open, setOpen] = useState(false)
    const [ovhPricingModeValue, setOvhPricingModeValue] = useState<string | undefined>()

    form.setFieldValue('provider', ConnectorProvider.OVHcloud)

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
        <Form
            {...formItemLayoutWithOutLabel}
            form={form}
            layout='horizontal'
            labelCol={{span: 6}}
            wrapperCol={{span: 14}}
            onFinish={onCreate}
        >
            <Form.Item
                label={t`Application key`}
                name={['authData', 'appKey']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input autoComplete='off'/>
            </Form.Item>

            <Form.Item
                label={t`Application secret`}
                name={['authData', 'appSecret']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input autoComplete='off'/>
            </Form.Item>

            <Form.Item
                label={t`Consumer key`}
                name={['authData', 'consumerKey']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input autoComplete='off'/>
            </Form.Item>

            <Form.Item
                label={t`Application key`}
                name={['authData', 'appKey']}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input autoComplete='off'/>
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
            <DefaultConnectorFormItems tosLink={providersConfig[ConnectorProvider.OVHcloud].tosLink}/>
        </Form>
    )
}
