import React, {useState} from "react";
import {Badge, Card, Divider, Flex, Form, FormProps, Input, message, Space, Timeline, Typography} from "antd";
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


const {Title} = Typography

type FieldType = {
    ldhName: string
}

export default function DomainSearchPage() {

    const [domain, setDomain] = useState<Domain | null>(null)
    const [messageApi, contextHolder] = message.useMessage()

    const onFinish: FormProps<FieldType>['onFinish'] = (values) => {
        getDomain(values.ldhName).then(setDomain).catch((e: AxiosError) => {
            const data = e?.response?.data as { detail: string }
            messageApi.error(data.detail ?? 'An error occurred')
            setDomain(null)
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

            {
                domain && <>
                    <Divider/>
                    <Space direction="vertical" size="middle" style={{width: '100%'}}>
                        <Badge.Ribbon text={`.${domain.tld.tld.toUpperCase()} (${domain.tld.type})`}
                                      color={
                                          domain.tld.type === 'ccTLD' ? 'purple' :
                                              (domain.tld.type === 'gTLD' && domain.tld.specification13) ? "volcano" :
                                                  domain.tld.type === 'gTLD' ? "green"
                                                      : "cyan"
                                      }>
                            <Card title={`${domain.ldhName}${domain.handle ? ' (' + domain.handle + ')' : ''}`}
                                  size="small">

                                <Timeline
                                    mode="right"
                                    items={domain.events
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
                            </Card>
                        </Badge.Ribbon>
                    </Space>
                </>
            }
        </Card>
    </Flex>
}