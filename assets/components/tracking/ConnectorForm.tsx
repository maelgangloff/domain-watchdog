import {Button, Form, FormInstance, Input, Select, Space, Typography} from "antd";
import React, {useState} from "react";
import {Connector, ConnectorProvider} from "../../utils/api/connectors";
import {t} from "ttag";
import {BankOutlined} from "@ant-design/icons";
import {regionNames} from "../../i18n";

const formItemLayoutWithOutLabel = {
    wrapperCol: {
        xs: {span: 24, offset: 0},
        sm: {span: 20, offset: 4},
    },
};

export function ConnectorForm({form, onCreate}: { form: FormInstance, onCreate: (values: Connector) => void }) {
    const [provider, setProvider] = useState<string>()

    const ovhFields = {
        appKey: t`Application key`,
        appSecret: t`Application secret`,
        consumerKey: t`Consumer key`
    }

    const ovhEndpointList = [
        {
            label: t`European Region`,
            value: 'ovh-eu'
        }
    ]

    const ovhSubsidiaryList = [{value: 'EU', label: t`Europa`}, ...[
        'CZ', 'DE', 'ES', 'FI', 'FR', 'GB', 'IE', 'IT', 'LT', 'MA', 'NL', 'PL', 'PT', 'SN', 'TN'
    ].map(c => ({value: c, label: regionNames.of(c) ?? c}))]

    const ovhPricingMode = [
        {value: 'create-default', label: t`The domain is free and at the standard price`},
        {
            value: 'create-premium',
            label: t`The domain is free but is a premium. Its price varies from one domain to another`
        }
    ]

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
            rules={[{required: true, message: t`Required`}]}
        >
            <Select
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
            />
        </Form.Item>

        {
            provider === ConnectorProvider.OVH && <>
                <Typography.Link target='_blank'
                                 href="https://api.ovh.com/createToken/index.cgi?GET=/*&PUT=/*&POST=/*&DELETE=/*">
                    Retrieve a token set from the OVH API
                </Typography.Link>
                {
                    Object.keys(ovhFields).map(fieldName => <Form.Item
                        label={ovhFields[fieldName as keyof typeof ovhFields]}
                        name={['authData', fieldName]}
                        rules={[{required: true, message: t`Required`}]}
                    >
                        <Input/>
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
                    <Select options={ovhPricingMode} optionFilterProp="label"/>
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