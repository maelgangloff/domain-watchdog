import {Button, Flex, Form, Input, message} from 'antd'
import {t} from 'ttag'
import React, {useContext, useEffect} from 'react'
import {getUser, login} from '../utils/api'
import {AuthenticatedContext} from '../pages/LoginPage'
import {useNavigate} from 'react-router-dom'

import {showErrorAPI} from '../utils/functions/showErrorAPI'

interface FieldType {
    username: string
    password: string
}

export function LoginForm({ssoLogin}: { ssoLogin?: boolean }) {
    const navigate = useNavigate()
    const [messageApi, contextHolder] = message.useMessage()
    const {setIsAuthenticated} = useContext(AuthenticatedContext)

    useEffect(() => {
        getUser().then(() => {
            setIsAuthenticated(true)
            navigate('/home')
        })
    }, [])

    const onFinish = (data: FieldType) => {
        login(data.username, data.password).then(() => {
            setIsAuthenticated(true)
            navigate('/home')
        }).catch((e) => {
            setIsAuthenticated(false)
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

                <Flex wrap justify="center" gap="middle">
                        <Button type='primary' htmlType='submit'>
                            {t`Submit`}
                        </Button>
                    {ssoLogin &&
                        <Button type='dashed' htmlType='button' href='/login/oauth'>
                            {t`Log in with SSO`}
                        </Button>}
                </Flex>
            </Form>
        </>
    )
}
