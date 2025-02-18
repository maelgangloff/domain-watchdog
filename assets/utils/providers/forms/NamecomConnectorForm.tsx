import type {FormInstance} from 'antd'
import {Alert, Form, Input} from 'antd'
import React from 'react'
import type {Connector} from '../../api/connectors'
import {ConnectorProvider} from '../../api/connectors'
import {t} from 'ttag'
import DefaultConnectorFormItems from "./DefaultConnectorFormItems"
import {formItemLayoutWithOutLabel, providersConfig} from "../index"

export default function NamecomConnectorForm({form, onCreate}: {
    form: FormInstance,
    onCreate: (values: Connector) => void
}) {

    form.setFieldValue('provider', ConnectorProvider["Name.com"])

    return (
        <Form
            {...formItemLayoutWithOutLabel}
            form={form}
            layout='horizontal'
            labelCol={{span: 6}}
            wrapperCol={{span: 14}}
            onFinish={onCreate}
        >
            <Alert
                message={t`This provider does not provide a list of supported TLD. Please double check if the domain you want to register is supported.`}
                type='warning'
                style={{marginBottom: '2em'}}
            />
            <Form.Item
                label={t`Username`}
                name={['authData', 'username']}
            >
                <Input autoComplete='off'/>
            </Form.Item>
            <Form.Item
                label={t`API key`}
                name={['authData', 'token']}
            >
                <Input autoComplete='off'/>
            </Form.Item>
            <DefaultConnectorFormItems tosLink={providersConfig[ConnectorProvider["Name.com"]].tosLink}/>
        </Form>
    )
}
