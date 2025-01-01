import {Tag, Tooltip} from 'antd'
import {eppStatusCodeToColor} from './eppStatusCodeToColor'
import React from 'react'
import {rdapStatusCodeDetailTranslation} from './rdapTranslation'

export function statusToTag(s: string) {
    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()

    return (
        <Tooltip
            placement='bottomLeft'
            title={rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] || undefined}
        >
            <Tag color={eppStatusCodeToColor(s)}>{s}</Tag>
        </Tooltip>
    )
}
