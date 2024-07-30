import {Button, Form, FormInstance, Input, Select, Space} from "antd";
import {t} from "ttag";
import {MinusCircleOutlined, PlusOutlined, ThunderboltFilled} from "@ant-design/icons";
import React, {useState} from "react";
import {EventAction} from "../../utils/api";
import {Connector} from "../../utils/api/connectors";


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

export function WatchlistForm({form, connectors, onCreateWatchlist}: {
    form: FormInstance,
    connectors: (Connector & { id: string })[]
    onCreateWatchlist: (values: { domains: string[], triggers: { event: string, action: string }[] }) => void
}) {

    const triggerEventItems: { label: string, value: EventAction }[] = [
        {
            label: t`When a domain is expired`,
            value: 'expiration'
        },
        {
            label: t`When a domain is deleted`,
            value: 'deletion'
        },
        {
            label: t`When a domain is updated`,
            value: 'last changed'
        },
        {
            label: t`When a domain is transferred`,
            value: 'transfer'
        },
        {
            label: t`When a domain is locked`,
            value: 'locked'
        },
        {
            label: t`When a domain is unlocked`,
            value: 'unlocked'
        },
        {
            label: t`When a domain is reregistered`,
            value: 'reregistration'
        },
        {
            label: t`When a domain is reinstantiated`,
            value: 'reinstantiation'
        },
        {
            label: t`When a domain is registered`,
            value: 'registration'
        }
    ]

    const triggerActionItems = [
        {
            label: t`Send me an email`,
            value: 'email'
        },
        {
            label: t`Buy the domain if available`,
            value: 'buy'
        }
    ]
    const [actionsSelect, setActionsSelect] = useState<{ [key: number]: string }>({})

    return <Form
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
                            return Promise.reject(new Error(t`At least one domain name`));
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
                            label={index === 0 ? t`Domain names` : ''}
                            required={true}
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
                            {t`Add a Domain name`}
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
                            return Promise.reject(new Error(t`At least one domain trigger`));
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
                            label={index === 0 ? t`Domain trigger` : ''}
                            required={true}
                            key={field.key}
                        >
                            <Space wrap>
                                <Form.Item {...field}
                                           validateTrigger={['onChange', 'onBlur']}
                                           rules={[{
                                               required: true,
                                               message: t`Required`
                                           }]}
                                           noStyle name={[field.name, 'event']}>
                                    <Select style={{minWidth: 300}} options={triggerEventItems} showSearch
                                            placeholder={t`If this happens`} optionFilterProp="label"/>
                                </Form.Item>
                                <Form.Item {...field}
                                           validateTrigger={['onChange', 'onBlur']}
                                           rules={[{
                                               required: true,
                                               message: t`Required`
                                           }]}
                                           noStyle name={[field.name, 'action']}>
                                    <Select style={{minWidth: 300}} options={triggerActionItems} showSearch
                                            placeholder={t`Then do that`}
                                            optionFilterProp="label" value={actionsSelect[field.key]}
                                            onChange={(e) => setActionsSelect({...actionsSelect, [field.key]: e})}/>
                                </Form.Item>
                                {actionsSelect[field.key] === 'buy' && <Form.Item {...field}
                                                                                  validateTrigger={['onChange', 'onBlur']}
                                                                                  rules={[{
                                                                                      required: true,
                                                                                      message: t`Required`
                                                                                  }]}
                                                                                  noStyle
                                                                                  name={[field.name, 'connector']}>
                                    <Select style={{minWidth: 500}} showSearch
                                            placeholder={t`Connector`}
                                            optionFilterProp="label"
                                            options={connectors.map(c => ({
                                                label: `${c.provider} (${c.id})`,
                                                value: c.id
                                            }))}
                                    />
                                </Form.Item>
                                }
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
                            {t`Add a Trigger`}
                        </Button>
                        <Form.ErrorList errors={errors}/>
                    </Form.Item>
                </>
            )}
        </Form.List>
        <Form.Item>
            <Space>
                <Button type="primary" htmlType="submit">
                    {t`Create`}
                </Button>
                <Button type="default" htmlType="reset">
                    {t`Reset`}
                </Button>
            </Space>
        </Form.Item>
    </Form>
}