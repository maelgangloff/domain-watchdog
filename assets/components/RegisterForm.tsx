import {Alert, Button, Form, Input} from "antd";
import {t} from "ttag";
import React, {useState} from "react";
import {register} from "../utils/api";
import {useNavigate} from "react-router-dom";


type FieldType = {
    username: string;
    password: string;
}

export function RegisterForm() {

    const [error, setError] = useState<string>()
    const navigate = useNavigate()

    const onFinish = (data: FieldType) => {
        register(data.username, data.password).then(() => {
            navigate('/home')
        }).catch((e) => {
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

            <Form.Item wrapperCol={{offset: 8, span: 16}}>
                <Button block type="primary" htmlType="submit">
                    {t`Register`}
                </Button>
            </Form.Item>
        </Form>
    </>
}