import React, {createContext, useContext, useEffect, useState} from "react";
import {Alert, Button, Card, Form, Input} from "antd";
import {getConfiguration, getUser, InstanceConfig, login} from "../utils/api";
import {useNavigate} from "react-router-dom";
import {t} from 'ttag'
import TextPage from "./TextPage";

type FieldType = {
    username: string;
    password: string;
}

const gridStyle: React.CSSProperties = {
    width: '50%',
    textAlign: 'center',
}

export const AuthenticatedContext = createContext<any>(null)

export default function LoginPage() {

    const [error, setError] = useState<string>()
    const [configuration, setConfiguration] = useState<InstanceConfig>()
    const navigate = useNavigate()
    const {setIsAuthenticated} = useContext(AuthenticatedContext)

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

    useEffect(() => {
        getUser().then(() => {
            setIsAuthenticated(true)
            navigate('/home')
        })
        getConfiguration().then(setConfiguration)
    }, [])

    return <Card title={t`Log in`} style={{width: '100%'}}>
        <Card.Grid style={gridStyle} hoverable={false}>
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
                    <Button block type="primary" htmlType="submit">
                        {t`Submit`}
                    </Button>
                </Form.Item>
                {configuration?.ssoLogin && <Form.Item wrapperCol={{offset: 8, span: 16}}>
                    <Button type="dashed" htmlType="button" href="/login/oauth" block>
                        {t`Log in with SSO`}
                    </Button>
                </Form.Item>}
            </Form>
        </Card.Grid>
        <Card.Grid style={gridStyle} hoverable={false}>
            <TextPage resource='ads.md'/>
        </Card.Grid>
    </Card>
}