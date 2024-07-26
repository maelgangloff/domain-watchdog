import React, {useState} from "react";
import {Card, Flex, Form, FormProps, Input, message} from "antd";
import {SearchOutlined} from "@ant-design/icons";
import {Domain, getDomain} from "../../utils/api";
import {AxiosError} from "axios"


type FieldType = {
    ldhName: string
}

export default function DomainSearchPage() {

    const [domainData, setDomainData] = useState<Domain | null>(null)
    const [messageApi, contextHolder] = message.useMessage()

    const onFinish: FormProps<FieldType>['onFinish'] = (values) => {
        getDomain(values.ldhName).then(setDomainData).catch((e: AxiosError) => {
            const data = e?.response?.data as { detail: string }
            messageApi.error(data.detail ?? 'An error occurred')

        })
    }


    return <Flex gap="middle" align="center" justify="center" vertical>
        <Card title="Domain finder">
            {contextHolder}
            <Form
                name="basic"
                labelCol={{span: 8}}
                wrapperCol={{span: 16}}
                style={{width: '50em'}}
                onFinish={onFinish}
                autoComplete="off"
            >
                <Form.Item<FieldType>
                    name="ldhName"
                    rules={[{
                        required: true,
                        message: 'Required'
                    }, {
                        pattern: /^(?=.*\.)\S*[^.\s]$/,
                        message: 'This domain name does not appear to be valid',
                        max: 63,
                        min: 2
                    }]}
                >
                    <Input size="large" prefix={<SearchOutlined/>} placeholder="example.com"/>
                </Form.Item>

            </Form>
        </Card>
        <Card>
        </Card>
    </Flex>
}