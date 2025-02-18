import type {FormInstance} from 'antd'
import {Form, Input} from 'antd'
import React from 'react'
import type {Connector} from '../../../utils/api/connectors'
import {ConnectorProvider} from '../../../utils/api/connectors'
import {t} from 'ttag'
import {DefaultConnectorFormItems} from "../../../utils/providers/forms"
import {providersConfig} from "../index"

const formItemLayoutWithOutLabel = {
    wrapperCol: {
        xs: {span: 24, offset: 0},
        sm: {span: 20, offset: 4}
    }
}

export function NamecheapConnectorForm({form, onCreate}: {
    form: FormInstance,
    onCreate: (values: Connector) => void
}) {

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
                <Input value={ConnectorProvider.Namecheap}/>
            </Form.Item>

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

            <DefaultConnectorFormItems tosLink={providersConfig()[ConnectorProvider.Namecheap].tosLink}/>
        </Form>
    )
}
