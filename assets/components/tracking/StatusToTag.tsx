import {Tag, Tooltip} from 'antd'
import {eppStatusCodeToColor} from '../../utils/functions/eppStatusCodeToColor'
import React from 'react'
import {rdapStatusCodeDetailTranslation} from '../../utils/functions/rdapTranslation'

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
