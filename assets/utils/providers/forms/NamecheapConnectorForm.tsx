import type {FormInstance} from 'antd'
import {Form, Input} from 'antd'
import React from 'react'
import type {Connector} from '../../api/connectors'
import {ConnectorProvider} from '../../api/connectors'
import {t} from 'ttag'
import DefaultConnectorFormItems from "./DefaultConnectorFormItems"
import {formItemLayoutWithOutLabel, providersConfig} from "../index"

export default function NamecheapConnectorForm({form, onCreate}: {
    form: FormInstance,
    onCreate: (values: Connector) => void
}) {


    form.setFieldValue('provider', ConnectorProvider.Namecheap)

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
                label={t`Username`}
                name={['authData', 'ApiUser']}
            >
                <Input autoComplete='off'/>
            </Form.Item>
            <Form.Item
                label={t`API key`}
                name={['authData', 'ApiKey']}
            >
                <Input autoComplete='off'/>
            </Form.Item>
            <DefaultConnectorFormItems tosLink={providersConfig[ConnectorProvider.Namecheap].tosLink}/>
        </Form>
    )
}
