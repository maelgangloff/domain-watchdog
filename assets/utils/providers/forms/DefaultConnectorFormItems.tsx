import {Button, Checkbox, Form, Input, Typography} from "antd"
import {t} from "ttag"
import React from "react"

export default function DefaultConnectorFormItems({tosLink}: { tosLink: string }) {
    return <>
        <Form.Item name="provider" noStyle>
            <Input type="hidden" />
        </Form.Item>

        <Form.Item
            valuePropName='checked'
            label={t`API Terms of Service`}
            name={['authData', 'acceptConditions']}
            rules={[{required: true, message: t`Required`}]}
            style={{marginTop: '3em'}}
        >
            <Checkbox
                required
            >
                <Typography.Link target='_blank' href={tosLink}>
                    {t`I have read and accepted the conditions of use of the Provider API, accessible from this hyperlink`}
                </Typography.Link>
            </Checkbox>
        </Form.Item>
        <Form.Item
            valuePropName='checked'
            label={t`Legal age`}
            name={['authData', 'ownerLegalAge']}
            rules={[{required: true, message: t`Required`}]}
        >
            <Checkbox
                required
            >{t`I am of the minimum age required to consent to these conditions`}
            </Checkbox>
        </Form.Item>
        <Form.Item
            valuePropName='checked'
            label={t`Withdrawal period`}
            name={['authData', 'waiveRetractationPeriod']}
            rules={[{required: true, message: t`Required`}]}
        >
            <Checkbox
                required
            >{t`I waive my right of withdrawal regarding the purchase of domain names via the Provider's API`}
            </Checkbox>
        </Form.Item>


        <Form.Item style={{marginTop: '2em', textAlign: 'center'}}>
            <Button type='primary' htmlType='submit'>
                {t`Create`}
            </Button>
        </Form.Item>
    </>
}