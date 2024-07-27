import React, {useEffect, useState} from "react";
import {Button, Card, Divider, Flex, Form, Input, message, Select, Skeleton, Space} from "antd";

import {CloseOutlined, DeleteFilled, MinusCircleOutlined, PlusOutlined, ThunderboltFilled} from "@ant-design/icons";
import {deleteWatchlist, EventAction, getWatchlists, postWatchlist} from "../../utils/api";
import {AxiosError} from "axios";


const formItemLayout = {
    labelCol: {
        xs: {span: 24},
        sm: {span: 4},
    },
    wrapperCol: {
        xs: {span: 24},
        sm: {span: 20},
    },
};

const formItemLayoutWithOutLabel = {
    wrapperCol: {
        xs: {span: 24, offset: 0},
        sm: {span: 20, offset: 4},
    },
};

const triggerEventItems: { label: string, value: EventAction }[] = [
    {
        label: 'When a domain is expired',
        value: 'expiration'
    },
    {
        label: 'When a domain is updated',
        value: 'last changed'
    },
    {
        label: 'When a domain is deleted',
        value: 'deletion'
    },
    {
        label: 'When a domain is transferred',
        value: 'transfer'
    },
    {
        label: 'When a domain is locked',
        value: 'locked'
    },
    {
        label: 'When a domain is unlocked',
        value: 'unlocked'
    },
    {
        label: 'When a domain is reregistered',
        value: 'reregistration'
    },
    {
        label: 'When a domain is reinstantiated',
        value: 'reinstantiation'
    }
]

const trigerActionItems = [
    {
        label: 'Send me an email',
        value: 'email'
    }
]

export default function WatchlistPage() {
    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [watchlists, setWatchlists] = useState<{ token: string }[] | null>()

    const onCreateWatchlist = (values: { domains: string[], triggers: { event: string, action: string }[] }) => {
        const domainsURI = values.domains.map(d => '/api/domains/' + d)

        postWatchlist(domainsURI, values.triggers).then((w) => {
            form.resetFields()
            refreshWatchlists()
            messageApi.success('Watchlist created !')
        }).catch((e: AxiosError) => {
            const data = e?.response?.data as { detail: string }
            messageApi.error(data.detail ?? 'An error occurred')
        })
    }

    const refreshWatchlists = () => getWatchlists().then(w => {
        setWatchlists(w['hydra:member'])
    }).catch(() => setWatchlists(undefined))

    useEffect(() => {
        refreshWatchlists()
    }, [])

    return <Flex gap="middle" align="center" justify="center" vertical>
        <Card title="Create a watchlist" style={{width: '100%'}}>
            {contextHolder}
            <Form
                {...formItemLayoutWithOutLabel}
                form={form}
                onFinish={onCreateWatchlist}
            >
                <Form.List
                    name="domains"
                    rules={[
                        {
                            validator: async (_, domains) => {
                                if (!domains || domains.length < 1) {
                                    return Promise.reject(new Error('At least one domain name'));
                                }
                            },
                        },
                    ]}
                >
                    {(fields, {add, remove}, {errors}) => (
                        <>
                            {fields.map((field, index) => (
                                <Form.Item
                                    {...(index === 0 ? formItemLayout : formItemLayoutWithOutLabel)}
                                    label={index === 0 ? 'Domain names' : ''}
                                    required={true}
                                    key={field.key}
                                >
                                    <Form.Item
                                        {...field}
                                        validateTrigger={['onChange', 'onBlur']}
                                        rules={[{
                                            required: true,
                                            message: 'Required'
                                        }, {
                                            pattern: /^(?=.*\.)\S*[^.\s]$/,
                                            message: 'This domain name does not appear to be valid',
                                            max: 63,
                                            min: 2
                                        }]}
                                        noStyle
                                    >
                                        <Input placeholder="Domain name" style={{width: '60%'}} autoComplete='off'/>
                                    </Form.Item>
                                    {fields.length > 1 ? (
                                        <MinusCircleOutlined
                                            className="dynamic-delete-button"
                                            onClick={() => remove(field.name)}
                                        />
                                    ) : null}
                                </Form.Item>
                            ))}
                            <Form.Item>
                                <Button
                                    type="dashed"
                                    onClick={() => add()}
                                    style={{width: '60%'}}
                                    icon={<PlusOutlined/>}
                                >
                                    Add a Domain name
                                </Button>
                                <Form.ErrorList errors={errors}/>
                            </Form.Item>
                        </>
                    )}
                </Form.List>
                <Form.List name="trigers">
                    {(fields, {add, remove}) => (
                        <>
                            {fields.map((field) => (
                                <Card
                                    size="small"
                                    title={`Trigger ${field.name + 1}`}
                                    key={field.key}
                                    extra={
                                        <CloseOutlined
                                            onClick={() => {
                                                remove(field.name);
                                            }}
                                        />
                                    }
                                >
                                    <Form.Item name={[field.name, 'event']}>
                                        <Select
                                            status="warning"
                                            options={triggerEventItems}
                                        />
                                    </Form.Item>
                                    <Form.Item name={[field.name, 'action']}>
                                        <Select
                                            options={trigerActionItems}
                                        />
                                    </Form.Item>
                                </Card>
                            ))}
                            <Divider/>
                            <Form.Item>
                                <Button
                                    type="dashed"
                                    onClick={() => add()}
                                    style={{width: '60%'}}
                                    icon={<ThunderboltFilled/>}
                                >
                                    Add a trigger
                                </Button>
                            </Form.Item>
                        </>
                    )}
                </Form.List>
                <Form.Item>
                    <Space>
                        <Button type="primary" htmlType="submit">
                            Create
                        </Button>
                        <Button type="default" htmlType="reset">
                            Reset
                        </Button>
                    </Space>
                </Form.Item>
            </Form>
        </Card>

        <Card title="My Watchlists" style={{width: '100%'}}>
            <Skeleton loading={watchlists === undefined} active>
                {watchlists && watchlists.map(watchlist =>
                    <>
                        <Divider/>
                        <Card title={"Watchlist " + watchlist.token} extra={<DeleteFilled onClick={() => {
                            deleteWatchlist(watchlist.token).then(refreshWatchlists)
                        }}/>}>
                        </Card>
                    </>
                )}
            </Skeleton>
        </Card>
    </Flex>
}