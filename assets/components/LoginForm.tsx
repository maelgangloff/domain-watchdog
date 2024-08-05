import {Alert, Button, Form, Input, Space} from "antd";
import {t} from "ttag";
import React, {useContext, useEffect, useState} from "react";
import {getUser, login} from "../utils/api";
import {AuthenticatedContext} from "../pages/LoginPage";
import {useNavigate} from "react-router-dom";


type FieldType = {
    username: string;
    password: string;
}

export function LoginForm({ssoLogin}: { ssoLogin?: boolean }) {

    const [error, setError] = useState<string>()
    const navigate = useNavigate()
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
            if (e.response.data.message !== undefined) {
                setError(e.response.data.message)
            } else {
                setError(e.response.data['hydra:description'])
            }
        })
    }
    return <>
        {error &&
            <Alert
                type='error'
                message={t`Error`}
                banner={true}
                role='role'
                description={error}
                style={{marginBottom: '1em'}}
            />}
        <Form
            name="basic"
            labelCol={{span: 8}}
            wrapperCol={{span: 16}}
            style={{maxWidth: 600}}
            onFinish={onFinish}
            autoComplete="off"
        >
            <Form.Item
                label={t`E-mail`}
                name="username"
                rules={[{required: true, message: t`Required`}]}
            >
                <Input autoFocus/>
            </Form.Item>

            <Form.Item<FieldType>
                label={t`Password`}
                name="password"
                rules={[{required: true, message: t`Required`}]}
            >
                <Input.Password/>
            </Form.Item>

            <Space>
                <Form.Item wrapperCol={{offset: 8, span: 16}}>
                    <Button type="primary" htmlType="submit">
                        {t`Submit`}
                    </Button>
                </Form.Item>
                {ssoLogin && <Form.Item wrapperCol={{offset: 8, span: 16}}>
                    <Button type="dashed" htmlType="button" href="/login/oauth">
                        {t`Log in with SSO`}
                    </Button>
                </Form.Item>}
            </Space>
        </Form>
    </>
}