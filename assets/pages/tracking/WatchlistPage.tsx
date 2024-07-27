import React, {useEffect, useState} from "react";
import {Button, Card, Divider, Flex, Form, Input, message, Popconfirm, Select, Skeleton, Space, Typography} from "antd";

import {DeleteFilled, MinusCircleOutlined, PlusOutlined, ThunderboltFilled} from "@ant-design/icons";
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

type Watchlist = { token: string, domains: { ldhName: string }[], triggers?: { event: EventAction, action: string }[] }

export default function WatchlistPage() {
    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [watchlists, setWatchlists] = useState<Watchlist[] | null>()

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
                <Form.List
                    name="triggers"
                    rules={[
                        {
                            validator: async (_, domains) => {
                                if (!domains || domains.length < 1) {
                                    return Promise.reject(new Error('At least one domain trigger'));
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
                                    label={index === 0 ? 'Domain trigger' : ''}
                                    required={true}
                                    key={field.key}
                                >

                                    <Space wrap>
                                        <Form.Item {...field}
                                                   validateTrigger={['onChange', 'onBlur']}
                                                   rules={[{
                                                       required: true,
                                                       message: 'Required'
                                                   }]}
                                                   noStyle name={[field.name, 'event']}>
                                            <Select style={{minWidth: 300}} options={triggerEventItems} showSearch
                                                    placeholder="If this happens" optionFilterProp="label"/>
                                        </Form.Item>
                                        <Form.Item {...field}
                                                   validateTrigger={['onChange', 'onBlur']}
                                                   rules={[{
                                                       required: true,
                                                       message: 'Required'
                                                   }]}
                                                   noStyle name={[field.name, 'action']}>
                                            <Select style={{minWidth: 300}} options={trigerActionItems} showSearch
                                                    placeholder="Then do that"
                                                    optionFilterProp="label"/>
                                        </Form.Item>
                                    </Space>


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
                                    icon={<ThunderboltFilled/>}
                                >
                                    Add a Trigger
                                </Button>
                                <Form.ErrorList errors={errors}/>
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


        <Skeleton loading={watchlists === undefined} active>
            {watchlists && watchlists.length > 0 && <Card title="My Watchlists" style={{width: '100%'}}>
                {watchlists.map(watchlist =>
                    <>
                        <Card title={"Watchlist " + watchlist.token} extra={<Popconfirm
                            title="Delete the Watchlist"
                            description="Are you sure to delete this Watchlist ?"
                            onConfirm={() => deleteWatchlist(watchlist.token).then(refreshWatchlists)}
                            okText="Yes"
                            cancelText="No"
                        ><DeleteFilled/> </Popconfirm>}>
                            <Typography.Paragraph>
                                Domains : {watchlist?.domains.map(d => d.ldhName).join(',')}
                            </Typography.Paragraph>
                            {
                                watchlist.triggers && <Typography.Paragraph>
                                    Triggers : {watchlist.triggers.map(t => `${t.event} => ${t.action}`).join(',')}
                                </Typography.Paragraph>
                            }
                        </Card>
                        <Divider/>
                    </>
                )}
            </Card>
            }
        </Skeleton>
    </Flex>
}