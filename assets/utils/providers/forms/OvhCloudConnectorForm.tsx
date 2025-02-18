import {t} from 'ttag'
import {regionNames} from "../../../i18n"
import React, {useState} from 'react'
import type { FormInstance} from "antd"
import {Form, Input, Popconfirm, Select} from "antd"
import type {Connector} from "../../api/connectors"
import { ConnectorProvider} from "../../api/connectors"
import {DefaultConnectorFormItems} from "./index"
import {providersConfig} from "../index"

const ovhFieldsFunction = () => ({
    appKey: t`Application key`,
    appSecret: t`Application secret`,
    consumerKey: t`Consumer key`
})

const ovhEndpointListFunction = () => [
    {
        label: t`European Region`,
        value: 'ovh-eu'
    }
]

const ovhSubsidiaryListFunction = () => [...[
    'CZ', 'DE', 'ES', 'FI', 'FR', 'GB', 'IE', 'IT', 'LT', 'MA', 'NL', 'PL', 'PT', 'SN', 'TN'
].map(c => ({value: c, label: regionNames.of(c) ?? c})), {value: 'EU', label: t`Europe`}]

const ovhPricingModeFunction = () => [
    {value: 'create-default', label: t`The domain is free and at the standard price`},
    {
        value: 'create-premium',
        label: t`The domain is free but can be premium. Its price varies from one domain to another`
    }
]

const formItemLayoutWithOutLabel = {
    wrapperCol: {
        xs: {span: 24, offset: 0},
        sm: {span: 20, offset: 4}
    }
}

export function OvhCloudConnectorForm({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) {
    const ovhFields = ovhFieldsFunction()
    const ovhEndpointList = ovhEndpointListFunction()
    const ovhSubsidiaryList = ovhSubsidiaryListFunction()
    const ovhPricingMode = ovhPricingModeFunction()
    const [open, setOpen] = useState(false)
    const [ovhPricingModeValue, setOvhPricingModeValue] = useState<string | undefined>()

    return (
        <Form
            {...formItemLayoutWithOutLabel}
            form={form}
            layout='horizontal'
            labelCol={{span: 6}}
            wrapperCol={{span: 14}}
            onFinish={onCreate}
        >
            <Form.Item name='provider' hidden>
                <Input value={ConnectorProvider.OVHcloud}/>
            </Form.Item>

            {
                Object.keys(ovhFields).map(fieldName => <Form.Item
                    key={ovhFields[fieldName as keyof typeof ovhFields]}
                    label={ovhFields[fieldName as keyof typeof ovhFields]}
                    name={['authData', fieldName]}
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Input autoComplete='off'/>
                </Form.Item>)
            }
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

            <DefaultConnectorFormItems tosLink={providersConfig()[ConnectorProvider.OVHcloud].tosLink}/>
        </Form>
    )
}
