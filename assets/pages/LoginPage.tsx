import React, {createContext, useContext, useState} from "react";
import {Alert, Button, Card, Flex, Form, Input} from "antd";
import {login} from "../utils/api";
import {useNavigate} from "react-router-dom";
import {t} from 'ttag'

type FieldType = {
    username: string;
    password: string;
};

export const AuthenticatedContext = createContext<any>(null)

export default function Page() {

    const [error, setError] = useState()
    const navigate = useNavigate()
    const {setIsAuthenticated} = useContext(AuthenticatedContext)

    const onFinish = (data: FieldType) => {
        login(data.username, data.password).then(() => {
            setIsAuthenticated(true)
            navigate('/home')
        }).catch((e) => {
            setIsAuthenticated(false)
            setError(e.response.data.message)
        })
    }

    return <Flex gap="middle" align="center" justify="center" vertical><Card
        title={t`Log in`}
    >
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
                label={t`Username`}
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

            <Form.Item wrapperCol={{offset: 8, span: 16}}>
                <Button type="primary" htmlType="submit">
                    {t`Submit`}
                </Button>
            </Form.Item>
            <Form.Item wrapperCol={{offset: 8, span: 16}}>
                <Button type="primary" htmlType="button" href="/login/oauth">
                    {t`Log in with SSO`}
                </Button>
            </Form.Item>
        </Form>
    </Card>
    </Flex>
}