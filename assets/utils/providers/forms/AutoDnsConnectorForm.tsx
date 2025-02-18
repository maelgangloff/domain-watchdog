import type {FormInstance} from 'antd'
import {Alert, Checkbox, Form, Input, Typography} from 'antd'
import React from 'react'
import type {Connector} from '../../api/connectors'
import {ConnectorProvider} from '../../api/connectors'
import {t} from 'ttag'
import DefaultConnectorFormItems from "./DefaultConnectorFormItems"
import {formItemLayoutWithOutLabel, providersConfig} from "../index"

export default function AutoDnsConnectorForm({form, onCreate}: {
    form: FormInstance,
    onCreate: (values: Connector) => void
}) {

    form.setFieldValue('provider', ConnectorProvider.AutoDNS)

    return (
        <Form
            {...formItemLayoutWithOutLabel}
            form={form}
            layout='horizontal'
            labelCol={{span: 6}}
            wrapperCol={{span: 14}}
            onFinish={onCreate}
        >
            <Alert
                message={t`This provider does not provide a list of supported TLD. Please double check if the domain you want to register is supported.`}
                type='warning'
                style={{marginBottom: '2em'}}
            />

            <Form.Item
                label={t`AutoDNS Username`}
                name={['authData', 'username']}
                help={<Typography.Link target='_blank' href='https://en.autodns.com/domain-robot-api/'>
                    {t`Because of some limitations in API of AutoDNS, we suggest to create an dedicated user for API with limited rights`}
                </Typography.Link>}
                rules={[{required: true, message: t`Required`}]}
            >
                <Input autoComplete='off' required/>
            </Form.Item>
            <Form.Item
                label={t`AutoDNS Password`}
                name={['authData', 'password']}
                help={<Typography.Text
                    type='secondary'
                >{t`Attention: AutoDNS do not support 2-Factor Authentication on API Users for automated systems`}
                </Typography.Text>}
                rules={[{required: true, message: t`Required`}]}
                required
            >
                <Input.Password autoComplete='off' required placeholder=''/>
            </Form.Item>
            <Form.Item
                label={t`Owner nic-handle`}
                name={['authData', 'contactid']}
                help={<Typography.Text
                    type='secondary'
                >{t`The nic-handle of the domain name owner`} <a
                    href='https://cloud.autodns.com/contacts/domain'
                >{t`You can get it from this page`}
                </a>
                </Typography.Text>}
                rules={[{required: true, message: t`Required`}]}
                required
            >
                <Input autoComplete='off' required placeholder=''/>
            </Form.Item>

            <Form.Item
                label={t`Context Value`}
                name={['authData', 'context']}
                help={<Typography.Text
                    type='secondary'
                >{t`If you not sure, use the default value 4`}
                </Typography.Text>}

                required={false}
            >
                <Input autoComplete='off' required={false} placeholder='4'/>
            </Form.Item>
            <Form.Item
                valuePropName='checked'
                label={t`Owner confirmation`}
                name={['authData', 'ownerConfirm']}

                rules={[{required: true, message: t`Required`}]}
            >
                <Checkbox
                    required
                >{t`Owner confirms his consent of domain order jobs`}
                </Checkbox>
            </Form.Item>

            <DefaultConnectorFormItems tosLink={providersConfig[ConnectorProvider.AutoDNS].tosLink}/>
        </Form>
    )
}
