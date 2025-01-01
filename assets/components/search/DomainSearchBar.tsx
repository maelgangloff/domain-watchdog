import {Form, Input} from 'antd'
import {t} from 'ttag'
import {SearchOutlined} from '@ant-design/icons'
import React, {useState} from 'react'

export interface FieldType {
    ldhName: string
    isRefreshForced: boolean
}

export function DomainSearchBar({onFinish, initialValue}: {
    onFinish: (values: FieldType) => void,
    initialValue?: string
}) {
    const [isRefreshForced, setRefreshForced] = useState(false)

    return (
        <Form
            onFinish={({ldhName}: FieldType) => onFinish({ldhName, isRefreshForced})}
            autoComplete='off'
            style={{width: '100%'}}
        >
            <Form.Item<FieldType>
                name='ldhName'
                initialValue={initialValue}
                rules={[{
                    required: true,
                    message: t`Required`
                }, {
                    pattern: /^(?=.*\.)?\S*[^.\s]$/,
                    message: t`This domain name does not appear to be valid`,
                    max: 63,
                    min: 2
                }]}
            >
                <Input
                    style={{textAlign: 'center'}}
                    size='large'
                    onKeyDown={e => setRefreshForced(e.shiftKey)}
                    onKeyUp={e => setRefreshForced(e.shiftKey)}
                    prefix={<SearchOutlined/>}
                    placeholder='example.com'
                    autoComplete='off'
                    autoFocus
                />
            </Form.Item>
        </Form>
    )
}
