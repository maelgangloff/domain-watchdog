import {Avatar} from 'antd'
import {
    BankOutlined,
    DollarOutlined,
    IdcardOutlined,
    SignatureOutlined,
    ToolOutlined,
    UserOutlined
} from '@ant-design/icons'
import React from 'react'

import {rolesToColor} from './rolesToColor'

export const roleToAvatar = (e: { roles: string[] }) => <Avatar
    style={{backgroundColor: rolesToColor(e.roles)}}
    icon={e.roles.includes('registrant')
        ? <SignatureOutlined/>
        : e.roles.includes('registrar')
            ? <BankOutlined/>
            : e.roles.includes('administrative')
                ? <IdcardOutlined/>
                : e.roles.includes('technical')
                    ? <ToolOutlined/>
                    : e.roles.includes('billing')
                        ? <DollarOutlined/>
                        : <UserOutlined/>}
/>
