import type {FormInstance} from 'antd'
import {Form, Input, Typography} from 'antd'
import React from 'react'
import {ConnectorProvider} from '../../api/connectors'
import {t} from 'ttag'

export default function NamecheapConnectorForm({form}: {
    form: FormInstance
}) {


    form.setFieldValue('provider', ConnectorProvider.Namecheap)

    return (
        <>
            <Form.Item
                label={t`Username`}
                name={['authData', 'ApiUser']}
                help={<Typography.Link target='_blank' href='https://ap.www.namecheap.com/settings/tools/apiaccess/'>
                    {t`Retreive an API key and whitelist this instance's IP address on Namecheap's website`}
                </Typography.Link>}
            >
                <Input autoComplete='off'/>
            </Form.Item>
            <Form.Item
                label={t`API key`}
                name={['authData', 'ApiKey']}
            >
                <Input autoComplete='off'/>
            </Form.Item>
        </>
    )
}
