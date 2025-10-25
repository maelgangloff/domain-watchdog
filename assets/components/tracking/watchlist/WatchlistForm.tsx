import type { FormInstance, SelectProps} from 'antd'
import {Button, Form, Input, Select, Space, Tag, Tooltip, Typography} from 'antd'
import {t} from 'ttag'
import {ApiOutlined, MinusCircleOutlined, PlusOutlined} from '@ant-design/icons'
import React from 'react'
import type {Connector} from '../../../utils/api/connectors'
import {
    rdapDomainStatusCodeDetailTranslation,
    rdapEventDetailTranslation,
    rdapEventNameTranslation
} from '../../../utils/functions/rdapTranslation'
import {actionToColor} from '../../../utils/functions/actionToColor'
import {actionToIcon} from '../../../utils/functions/actionToIcon'
import type {EventAction, Watchlist} from '../../../utils/api'
import {formItemLayoutWithOutLabel} from "../../../utils/providers"
import {eppStatusCodeToColor} from "../../../utils/functions/eppStatusCodeToColor"

type TagRender = SelectProps['tagRender']

const formItemLayout = {
    labelCol: {
        xs: {span: 24},
        sm: {span: 4}
    },
    wrapperCol: {
        xs: {span: 24},
        sm: {span: 20}
    }
}

export function WatchlistForm({form, connectors, onFinish, isCreation}: {
    form: FormInstance
    connectors: Array<Connector & { id: string }>
    onFinish: (values: { domains: string[], trackedEvents: string[], trackedEppStatus: string[], token: string }) => void
    isCreation: boolean,
    watchList?: Watchlist,
}) {
    const rdapEventNameTranslated = rdapEventNameTranslation()
    const rdapEventDetailTranslated = rdapEventDetailTranslation()
    const rdapDomainStatusCodeDetailTranslated = rdapDomainStatusCodeDetailTranslation()

    const eventActionTagRenderer: TagRender = ({value, closable, onClose}: {
        value: EventAction
        closable: boolean
        onClose: () => void
    }) => {
        const onPreventMouseDown = (event: React.MouseEvent<HTMLSpanElement>) => {
            event.preventDefault()
            event.stopPropagation()
        }
        return (
            <Tooltip
                title={rdapEventDetailTranslated[value as keyof typeof rdapEventDetailTranslated] || undefined}
            >
                <Tag
                    icon={actionToIcon(value)}
                    color={actionToColor(value)}
                    onMouseDown={onPreventMouseDown}
                    closable={closable}
                    onClose={onClose}
                    style={{marginInlineEnd: 4}}
                >
                    {rdapEventNameTranslated[value as keyof typeof rdapEventNameTranslated]}
                </Tag>
            </Tooltip>
        )
    }

    const domainStatusTagRenderer: TagRender = ({value, closable, onClose}: {
        value: EventAction
        closable: boolean
        onClose: () => void
    }) => {
        const onPreventMouseDown = (event: React.MouseEvent<HTMLSpanElement>) => {
            event.preventDefault()
            event.stopPropagation()
        }
        return (
            <Tooltip
                title={rdapDomainStatusCodeDetailTranslated[value as keyof typeof rdapDomainStatusCodeDetailTranslated] || undefined}
            >
                <Tag
                    color={eppStatusCodeToColor(value)}
                    onMouseDown={onPreventMouseDown}
                    closable={closable}
                    onClose={onClose}
                    style={{marginInlineEnd: 4}}
                >
                    {value}
                </Tag>
            </Tooltip>
        )
    }

    return (
        <Form
            {...formItemLayoutWithOutLabel}
            form={form}
            onFinish={onFinish}
            initialValues={{
                trackedEvents: ['last changed', 'transfer', 'deletion'],
                trackedEppStatus: ['auto renew period', 'redemption period', 'pending delete', 'client hold', 'server hold']
        }}
        >

            <Form.Item name='token' hidden>
                <Input hidden/>
            </Form.Item>

            <Form.Item
                label={t`Name`}
                name='name'
                labelCol={{
                    xs: {span: 24},
                    sm: {span: 4}
                }}
                wrapperCol={{
                    md: {span: 12},
                    sm: {span: 20}
                }}
            >
                <Input
                    placeholder={t`Watchlist Name`}
                    title={t`Naming the Watchlist makes it easier to find in the list below.`}
                    autoComplete='off'
                    autoFocus
                />
            </Form.Item>
            <Form.List
                name='domains'
                rules={[
                    {
                        validator: async (_, domains) => {
                            if (!domains || domains.length < 1) {
                                throw new Error(t`At least one domain name`)
                            }
                        }
                    }
                ]}
            >
                {(fields, {add, remove}, {errors}) => (
                    <>
                        {fields.map((field, index) => (
                            <Form.Item
                                {...(index === 0 ? formItemLayout : formItemLayoutWithOutLabel)}
                                label={index === 0 ? t`Domain names` : ''}
                                required
                                key={field.key}
                            >
                                <Form.Item
                                    {...field}
                                    validateTrigger={['onChange', 'onBlur']}
                                    rules={[{
                                        required: true,
                                        message: t`Required`
                                    }, {
                                        pattern: /^(?=.*\.)\S*[^.\s]$/,
                                        message: t`This domain name does not appear to be valid`,
                                        max: 63,
                                        min: 2
                                    }]}
                                    noStyle
                                >
                                    <Input placeholder={t`Domain name`} style={{width: '60%'}} autoComplete='off'/>
                                </Form.Item>
                                {fields.length > 1
                                    ? (
                                        <MinusCircleOutlined
                                            className='dynamic-delete-button'
                                            onClick={() => remove(field.name)}
                                        />
                                    )
                                    : null}
                            </Form.Item>
                        ))}
                        <Form.Item>
                            <Button
                                type='dashed'
                                onClick={() => add()}
                                style={{width: '60%'}}
                                icon={<PlusOutlined/>}
                            >
                                {t`Add a Domain name`}
                            </Button>
                            <Form.ErrorList errors={errors}/>
                        </Form.Item>
                    </>
                )}
            </Form.List>
            <Form.Item
                label={t`Tracked events`}
                name='trackedEvents'
                rules={[{required: true, message: t`At least one event`, type: 'array'}]}
                labelCol={{
                    xs: {span: 24},
                    sm: {span: 4}
                }}
                wrapperCol={{
                    md: {span: 12},
                    sm: {span: 20}
                }}
                required
            >
                <Select
                    mode='multiple'
                    tagRender={eventActionTagRenderer}
                    style={{width: '100%'}}
                    options={Object.keys(rdapEventNameTranslated).map(e => ({
                        value: e,
                        title: rdapEventDetailTranslated[e as keyof typeof rdapEventDetailTranslated] || undefined,
                        label: rdapEventNameTranslated[e as keyof typeof rdapEventNameTranslated]
                    }))}
                />
            </Form.Item>

            <Form.Item
                label={t`Tracked EPP status`}
                name='trackedEppStatus'
                rules={[{required: true, message: t`At least one EPP status`, type: 'array'}]}
                labelCol={{
                    xs: {span: 24},
                    sm: {span: 4}
                }}
                wrapperCol={{
                    md: {span: 12},
                    sm: {span: 20}
                }}
                required
            >
                <Select
                    mode='multiple'
                    tagRender={domainStatusTagRenderer}
                    style={{width: '100%'}}
                    options={Object.keys(rdapDomainStatusCodeDetailTranslated).map(e => ({
                        value: e,
                        title: rdapDomainStatusCodeDetailTranslated[e as keyof typeof rdapDomainStatusCodeDetailTranslated] || undefined,
                        label: e
                    }))}
                />
            </Form.Item>

            <Form.Item
                label={t`Connector`}
                name='connector'
                labelCol={{
                    xs: {span: 24},
                    sm: {span: 4}
                }}
                wrapperCol={{
                    md: {span: 12},
                    sm: {span: 20}
                }}
                help={t`Please make sure the connector information is valid to purchase a domain that may be available soon.`}
            >
                <Select
                    showSearch
                    allowClear
                    placeholder={t`Connector`}
                    suffixIcon={<ApiOutlined/>}
                    optionFilterProp='label'
                    options={connectors.map(c => ({
                        label: `${c.provider} (${c.id})`,
                        value: c.id
                    }))}
                />
            </Form.Item>
            <Form.List
                name='dsn'
            >
                {(fields, {add, remove}, {errors}) => (
                    <>
                        {fields.map((field, index) => (
                            <Form.Item
                                {...(index === 0 ? formItemLayout : formItemLayoutWithOutLabel)}
                                label={index === 0 ? t`DSN` : ''}
                                required
                                key={field.key}
                            >
                                <Form.Item
                                    {...field}
                                    validateTrigger={['onChange', 'onBlur']}
                                    rules={[{
                                        required: true,
                                        message: t`Required`
                                    }, {
                                        pattern: /:\/\//,
                                        message: t`This DSN does not appear to be valid`
                                    }]}
                                    noStyle
                                >
                                    <Input
                                        placeholder='slack://TOKEN@default?channel=CHANNEL' style={{width: '60%'}}
                                        autoComplete='off'
                                    />
                                </Form.Item>
                                {fields.length > 0
                                    ? (
                                        <MinusCircleOutlined
                                            className='dynamic-delete-button'
                                            onClick={() => remove(field.name)}
                                        />
                                    )
                                    : null}
                            </Form.Item>
                        ))}
                        <Form.Item help={
                            <Typography.Link
                                href='https://symfony.com/doc/current/notifier.html#chat-channel'
                                target='_blank'
                            >
                                {t`Check out this link to the Symfony documentation to help you build the DSN`}
                            </Typography.Link>
                        }
                        >
                            <Button
                                type='dashed'
                                onClick={() => add()}
                                style={{width: '60%'}}
                                icon={<PlusOutlined/>}
                            >
                                {t`Add a Webhook`}
                            </Button>
                            <Form.ErrorList errors={errors}/>
                        </Form.Item>
                    </>
                )}
            </Form.List>
            <Form.Item style={{marginTop: '5em'}}>
                <Space>
                    <Button type='primary' htmlType='submit'>
                        {isCreation ? t`Create` : t`Update`}
                    </Button>
                    <Button type='default' htmlType='reset'>
                        {t`Reset`}
                    </Button>
                </Space>
            </Form.Item>
        </Form>
    )
}
