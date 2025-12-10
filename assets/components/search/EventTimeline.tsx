import {Timeline, Tooltip, Typography} from 'antd'
import React from 'react'
import type {Event} from '../../utils/api'
import useBreakpoint from '../../hooks/useBreakpoint'
import {rdapEventDetailTranslation, rdapEventNameTranslation} from '../../utils/functions/rdapTranslation'
import {actionToColor} from '../../utils/functions/actionToColor'
import {actionToIcon} from '../../utils/functions/actionToIcon'
import {ThunderboltOutlined} from "@ant-design/icons"
import {t} from "ttag"

function getWhoisRemoveTimelineEvent(expiresInDays: number) {
    const locale = navigator.language.split('-')[0]
    const sm = useBreakpoint('sm')
    const eventName = t`Estimated removal`
    const eventDetail = t`Estimated WHOIS removal date. This is the earliest date this record would be deleted, according to ICANN's standard lifecycle. Note that some registries have their own lifecycles.`

    const dateStr =
        <Typography.Text>
            {new Date(new Date().getTime() + expiresInDays * 24 * 60 * 60 * 1e3).toLocaleDateString(locale)}
        </Typography.Text>

    const text = sm
        ? {
            children: <Tooltip placement='bottom' title={eventDetail}>
                {eventName}&emsp;{dateStr}
            </Tooltip>
        }
        : {
            label: dateStr,
            children: <Tooltip placement='left' title={eventDetail}>{eventName}</Tooltip>
        }

    return {
        color: 'yellow',
        dot: <ThunderboltOutlined style={{fontSize: '16px'}}/>,
        pending: true,
        ...text
    }
}


export function EventTimeline({events, expiresInDays}: { events: Event[], expiresInDays?: number }) {
    const sm = useBreakpoint('sm')

    const locale = navigator.language.split('-')[0]
    const rdapEventNameTranslated = rdapEventNameTranslation()
    const rdapEventDetailTranslated = rdapEventDetailTranslation()

    const items = []
    if (expiresInDays !== undefined) {
        items.push(getWhoisRemoveTimelineEvent(expiresInDays))
    }

    items.push(
        ...events
            .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
            .map(e => {
                    const eventName = (
                        <Typography.Text style={{color: e.deleted ? 'grey' : 'default'}}>
                            {rdapEventNameTranslated[e.action as keyof typeof rdapEventNameTranslated] || e.action}
                        </Typography.Text>
                    )

                    const dateStr = (
                        <Typography.Text
                            style={{color: e.deleted ? 'grey' : 'default'}}
                        >{new Date(e.date).toLocaleString(locale)}
                        </Typography.Text>
                    )

                    const eventDetail = rdapEventDetailTranslated[e.action as keyof typeof rdapEventDetailTranslated] || undefined

                    const text = sm
                        ? {
                            children: <Tooltip placement='bottom' title={eventDetail}>
                                {eventName}&emsp;{dateStr}
                            </Tooltip>
                        }
                        : {
                            label: dateStr,
                            children: <Tooltip placement='left' title={eventDetail}>{eventName}</Tooltip>
                        }

                    return {
                        color: e.deleted ? 'grey' : actionToColor(e.action),
                        dot: actionToIcon(e.action),
                        pending: new Date(e.date).getTime() > new Date().getTime(),
                        ...text
                    }
                }
            )
    )

    return <Timeline
        mode={sm ? 'left' : 'right'}
        items={items}
    />
}
