import {Button, Form, Input, message} from 'antd'
import {t} from 'ttag'
import React from 'react'
import {register} from '../utils/api'
import {useNavigate} from 'react-router-dom'

import {showErrorAPI} from '../utils/functions/showErrorAPI'

interface FieldType {
    username: string
    password: string
}

export function RegisterForm() {
    const navigate = useNavigate()
    const [messageApi, contextHolder] = message.useMessage()

    const onFinish = (data: FieldType) => {
        register(data.username, data.password).then(() => {
            navigate('/home')
        }).catch((e) => {
            showErrorAPI(e, messageApi)
        })
    }
    return (
        <>
            {contextHolder}
            <Form
                name='basic'
                labelCol={{span: 8}}
                wrapperCol={{span: 16}}
                style={{maxWidth: 600}}
                onFinish={onFinish}
                autoComplete='off'
            >
                <Form.Item
                    label={t`Email address`}
                    name='username'
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Input autoFocus/>
                </Form.Item>

                <Form.Item<FieldType>
                    label={t`Password`}
                    name='password'
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Input.Password/>
                </Form.Item>

                <Form.Item wrapperCol={{offset: 8, span: 16}}>
                    <Button block type='primary' htmlType='submit'>
                        {t`Register`}
                    </Button>
                </Form.Item>
            </Form>
        </>
    )
}
