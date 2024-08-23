import {Form, Input} from "antd";
import {t} from "ttag";
import {SearchOutlined} from "@ant-design/icons";
import React from "react";

export type FieldType = {
    ldhName: string
}

export function DomainSearchBar({onFinish}: { onFinish: (values: FieldType) => void }) {
    return <Form
        onFinish={onFinish}
        autoComplete="off"
        style={{width: '100%'}}
    >
        <Form.Item<FieldType>
            name="ldhName"
            rules={[{
                required: true,
                message: t`Required`
            }, {
                pattern: /^(?=.*\.)\S*[^.\s]$/,
                message: t`This domain name does not appear to be valid`,
                max: 63,
                min: 2
            }]}
        >
            <Input style={{textAlign: 'center'}}
                   size="large"
                   prefix={<SearchOutlined/>}
                   placeholder="example.com"
                   autoComplete='off'
                   autoFocus
            />
        </Form.Item>
    </Form>
}