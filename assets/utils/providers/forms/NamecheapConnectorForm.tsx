import {Form, Input, Typography} from 'antd'
import React from 'react'
import {t} from 'ttag'
import {LockOutlined, UserOutlined} from "@ant-design/icons"

export default function NamecheapConnectorForm() {
    return (
        <>
            <Form.Item
                label={t`Username`}
                name={['authData', 'ApiUser']}
                help={<Typography.Link target='_blank' href='https://ap.www.namecheap.com/settings/tools/apiaccess/'>
                    {t`Retrieve an API key and whitelist this instance's IP address on Namecheap's website`}
                </Typography.Link>}
            >
                <Input prefix={<UserOutlined/>} autoComplete='off'/>
            </Form.Item>
            <Form.Item
                label={t`API key`}
                name={['authData', 'ApiKey']}
            >
                <Input prefix={<LockOutlined/>} autoComplete='off'/>
            </Form.Item>
        </>
    )
}
