import {Badge, Tag} from 'antd'
import {DeleteOutlined, ExclamationCircleOutlined} from '@ant-design/icons'
import punycode from 'punycode/punycode'
import {Link} from 'react-router-dom'
import React from 'react'
import type {Event} from "../api"
import {t} from "ttag"

export function DomainToTag({domain, link}: { domain: { ldhName: string, deleted: boolean, status: string[], events?: Event[] }, link?: boolean }) {
    const tag = <Badge dot={domain.events?.find(e =>
        e.action === 'last changed' &&
        !e.deleted &&
        ((new Date().getTime() - new Date(e.date).getTime()) < 7*24*60*60*1e3)
    ) !== undefined} color='blue' title={t`The domain name was updated less than a week ago.`}>
        <Tag
            color={
                domain.deleted
                    ? 'magenta'
                    : domain.status.includes('redemption period')
                        ? 'yellow'
                        : domain.status.includes('pending delete') ? 'volcano' : 'default'
            }
            icon={
                domain.deleted
                    ? <DeleteOutlined/>
                    : domain.status.includes('redemption period')
                        ? <ExclamationCircleOutlined/>
                        : domain.status.includes('pending delete') ? <DeleteOutlined/> : null
            }
        >{punycode.toUnicode(domain.ldhName)}
        </Tag>
    </Badge>

    if (link ?? true) {
        return (
            <Link to={'/search/domain/' + domain.ldhName}>
                {tag}
            </Link>
        )
    } else {
        return tag
    }
}
