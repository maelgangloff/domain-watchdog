import type {FormInstance} from 'antd'
import {Form, Input, Typography} from 'antd'
import React from 'react'
import type {Connector} from '../../api/connectors'
import {ConnectorProvider} from '../../api/connectors'
import {t} from 'ttag'
import DefaultConnectorFormItems from "./DefaultConnectorFormItems"
import {formItemLayoutWithOutLabel, providersConfig} from "../index"

export default function GandiConnectorForm({form, onCreate}: {
    form: FormInstance,
    onCreate: (values: Connector) => void
}) {

    form.setFieldValue('provider', ConnectorProvider.Gandi)

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
                label={t`Personal Access Token (PAT)`}
                name={['authData', 'token']}
                help={<Typography.Link target='_blank' href='https://admin.gandi.net/organizations/account/pat'>
                    {t`Retrieve a Personal Access Token from your customer account on the Provider's website`}
                </Typography.Link>}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input autoComplete='off'/>
            </Form.Item>
            <Form.Item
                label={t`Organization sharing ID`}
                name={['authData', 'sharingId']}
                help={<Typography.Text
                    type='secondary'
                >{t`It indicates the organization that will pay for the ordered product`}
                </Typography.Text>}
                required={false}
            >
                <Input autoComplete='off' placeholder='xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'/>
            </Form.Item>
            <DefaultConnectorFormItems tosLink={providersConfig[ConnectorProvider.Gandi].tosLink}/>

        </Form>
    )
}
