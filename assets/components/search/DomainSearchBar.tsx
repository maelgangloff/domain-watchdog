import {Form, Input} from "antd";
import {t} from "ttag";
import {SearchOutlined} from "@ant-design/icons";
import React from "react";

export type FieldType = {
    ldhName: string
}

export function DomainSearchBar({onFinish}: { onFinish: (values: FieldType) => void }) {

    return <Form
        name="basic"
        labelCol={{span: 8}}
        wrapperCol={{span: 16}}
        onFinish={onFinish}
        autoComplete="off"
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
            <Input size="large" prefix={<SearchOutlined/>} placeholder="example.com" autoFocus={true}
                   autoComplete='off'/>
        </Form.Item>
    </Form>
}