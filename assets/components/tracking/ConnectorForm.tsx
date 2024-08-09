import {Button, Checkbox, Form, FormInstance, Input, Popconfirm, Select, Space, Typography} from "antd";
import React, {useState} from "react";
import {Connector, ConnectorProvider} from "../../utils/api/connectors";
import {t} from "ttag";
import {BankOutlined} from "@ant-design/icons";
import {
    ovhEndpointList as ovhEndpointListFunction,
    ovhFields as ovhFieldsFunction,
    ovhPricingMode as ovhPricingModeFunction,
    ovhSubsidiaryList as ovhSubsidiaryListFunction
} from "../../utils/providers/ovh";
import {helpGetTokenLink, tosHyperlink} from "../../utils/providers";

const formItemLayoutWithOutLabel = {
    wrapperCol: {
        xs: {span: 24, offset: 0},
        sm: {span: 20, offset: 4},
    },
};

export function ConnectorForm({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) {
    const [provider, setProvider] = useState<string>()
    const ovhFields = ovhFieldsFunction()
    const ovhEndpointList = ovhEndpointListFunction()
    const ovhSubsidiaryList = ovhSubsidiaryListFunction()
    const ovhPricingMode = ovhPricingModeFunction()
    const [open, setOpen] = useState(false)
    const [ovhPricingModeValue, setOvhPricingModeValue] = useState<string | undefined>()


    return <Form
        {...formItemLayoutWithOutLabel}
        form={form}
        layout="horizontal"
        labelCol={{span: 6}}
        wrapperCol={{span: 14}}
        onFinish={onCreate}
    >
        <Form.Item
            label={t`Provider`}
            name="provider"
            help={helpGetTokenLink(provider)}
            rules={[{required: true, message: t`Required`}]}
        >
            <Select
                allowClear
                placeholder={t`Please select a Provider`}
                suffixIcon={<BankOutlined/>}
                options={Object.keys(ConnectorProvider).map((c) => ({
                    value: ConnectorProvider[c as keyof typeof ConnectorProvider],
                    label: (
                        <>
                            <BankOutlined/>{" "} {c}
                        </>
                    ),
                }))}
                value={provider}
                onChange={setProvider}
                autoFocus
            />
        </Form.Item>

        {
            provider === ConnectorProvider.OVH && <>
                {
                    Object.keys(ovhFields).map(fieldName => <Form.Item
                        label={ovhFields[fieldName as keyof typeof ovhFields]}
                        name={['authData', fieldName]}
                        rules={[{required: true, message: t`Required`}]}
                    >
                        <Input autoComplete='off'/>
                    </Form.Item>)
                }
                <Form.Item
                    label={t`OVH Endpoint`}
                    name={['authData', 'apiEndpoint']}
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Select options={ovhEndpointList} optionFilterProp="label"/>
                </Form.Item>
                <Form.Item
                    label={t`OVH subsidiary`}
                    name={['authData', 'ovhSubsidiary']}
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Select options={ovhSubsidiaryList} optionFilterProp="label"/>
                </Form.Item>

                <Form.Item
                    label={t`OVH pricing mode`}
                    name={['authData', 'pricingMode']}
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Popconfirm
                        title={t`Confirm pricing mode`}
                        description={t`Are you sure about this setting? This may result in additional charges from the API Provider`}
                        onCancel={() => {
                            form.resetFields(['authData'])
                            setOvhPricingModeValue(undefined)
                            setOpen(false)
                        }}
                        onConfirm={() => setOpen(false)}
                        open={open}
                    >
                        <Select options={ovhPricingMode} optionFilterProp="label" value={ovhPricingModeValue}
                                onChange={(value: string) => {
                                    setOvhPricingModeValue(value)
                                    form.setFieldValue(['authData', 'pricingMode'], value)
                                    if (value !== 'create-default') {
                                        setOpen(true)
                                    }
                                }}/>
                    </Popconfirm>
                </Form.Item>
            </>
        }
        {
            provider === ConnectorProvider.GANDI && <>
                <Form.Item
                    label={t`Personal Access Token (PAT)`}
                    name={['authData', 'token']}
                    rules={[{required: true, message: t`Required`}]}>
                    <Input autoComplete='off'/>
                </Form.Item>
                <Form.Item
                    label={t`Organization sharing ID`}
                    name={['authData', 'sharingId']}
                    help={<Typography.Text
                        type='secondary'>{t`It indicates the organization that will pay for the ordered product`}</Typography.Text>}
                    required={false}>
                    <Input autoComplete='off' placeholder='xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'/>
                </Form.Item>
            </>
        }

        {
            provider !== undefined && <>
                <Form.Item
                    valuePropName="checked"
                    label={t`API Terms of Service`}
                    name={['authData', 'acceptConditions']}
                    rules={[{required: true, message: t`Required`}]}
                    style={{marginTop: '3em'}}
                >
                    <Checkbox
                        required={true}>
                        <Typography.Link target='_blank' href={tosHyperlink(provider)}>
                            {t`I have read and accepted the conditions of use of the Provider API, accessible from this hyperlink`}
                        </Typography.Link>
                    </Checkbox>
                </Form.Item>
                <Form.Item
                    valuePropName="checked"
                    label={t`Legal age`}
                    name={['authData', 'ownerLegalAge']}
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Checkbox
                        required={true}>{t`I am of the minimum age required to consent to these conditions`}</Checkbox>
                </Form.Item>
                <Form.Item
                    valuePropName="checked"
                    label={t`Withdrawal period`}
                    name={['authData', 'waiveRetractationPeriod']}
                    rules={[{required: true, message: t`Required`}]}
                >
                    <Checkbox
                        required={true}>{t`I waive my right of withdrawal regarding the purchase of domain names via the Provider's API`}</Checkbox>
                </Form.Item>
            </>
        }

        <Form.Item style={{marginTop: 10}}>
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