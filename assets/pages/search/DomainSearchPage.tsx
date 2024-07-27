import React, {useState} from "react";
import {Card, Divider, Flex, Form, FormProps, Input, message, Timeline} from "antd";
import {
    ClockCircleOutlined,
    DeleteOutlined,
    SearchOutlined,
    ShareAltOutlined,
    SignatureOutlined,
    SyncOutlined
} from "@ant-design/icons";
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
            <Divider/>
            {
                domainData && <>
                    <Timeline
                        mode="right"
                        items={domainData.events
                            .sort((e1, e2) => new Date(e2.date).getTime() - new Date(e1.date).getTime())
                            .map(({action, date}) => {

                                    let color, dot
                                    if (action === 'registration') {
                                        color = 'green'
                                        dot = <SignatureOutlined style={{fontSize: '16px'}}/>
                                    } else if (action === 'expiration') {
                                        color = 'red'
                                        dot = <ClockCircleOutlined style={{fontSize: '16px'}}/>
                                    } else if (action === 'transfer') {
                                        color = 'orange'
                                        dot = <ShareAltOutlined style={{fontSize: '16px'}}/>
                                    } else if (action === 'last changed') {
                                        color = 'blue'
                                        dot = <SyncOutlined style={{fontSize: '16px'}}/>
                                    } else if (action === 'deletion') {
                                        color = 'red'
                                        dot = <DeleteOutlined style={{fontSize: '16px'}}/>
                                    }

                                    return {
                                        label: new Date(date).toUTCString(),
                                        children: action,
                                        color,
                                        dot,
                                        pending: new Date(date).getTime() > new Date().getTime()
                                    }
                                }
                            )
                        }
                    />
                </>
            }
        </Card>
    </Flex>
}