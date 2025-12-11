import {Timeline, Tooltip, Typography} from 'antd'
import React from 'react'
import type {Event} from '../../utils/api'
import useBreakpoint from '../../hooks/useBreakpoint'
import {rdapEventDetailTranslation, rdapEventNameTranslation} from '../../utils/functions/rdapTranslation'
import {actionToColor} from '../../utils/functions/actionToColor'
import {actionToIcon} from '../../utils/functions/actionToIcon'
import {ThunderboltOutlined} from "@ant-design/icons"
import {t} from "ttag"
import type {TimeLineItemProps} from "antd/lib/timeline/TimelineItem"

function getWhoisRemoveTimelineEvent(whoisRemoveDateEstimate: Date, withRenewalPeriod?: boolean) {
    const locale = navigator.language.split('-')[0]
    const sm = useBreakpoint('sm')
    const eventName = withRenewalPeriod === undefined ? t`Estimated removal` : withRenewalPeriod ? t`Estimated removal (incl. renewal)` : t`Estimated removal (excl. renewal)`
    const eventDetail = t`Estimated WHOIS removal date. This is the latest date this record would be deleted, according to ICANN's standard lifecycle. Note that some registries have their own lifecycles.`

    const dateStr =
        <Typography.Text>
            {whoisRemoveDateEstimate.toLocaleDateString(locale)}
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
        date: whoisRemoveDateEstimate,
        color: (withRenewalPeriod === undefined || withRenewalPeriod) ? 'yellow' : 'grey',
        dot: <ThunderboltOutlined style={{fontSize: '16px'}}/>,
        pending: true,
        ...text
    }
}


export function EventTimeline({events, expiresInDays, isRenewalPeriod}: {
    events: Event[],
    expiresInDays?: number,
    isRenewalPeriod: boolean
}) {
    const sm = useBreakpoint('sm')
    const sortedEvents = events.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())

    const locale = navigator.language.split('-')[0]
    const rdapEventNameTranslated = rdapEventNameTranslation()
    const rdapEventDetailTranslated = rdapEventDetailTranslation()
    const items: (TimeLineItemProps & { date: Date })[] = []

    if (expiresInDays !== undefined) {
        const whoisRemoveDateEstimate = new Date(new Date().getTime() + expiresInDays * 24 * 60 * 60 * 1e3)

        const expirationEvent = sortedEvents.find(e => !e.deleted && e.action === 'expiration')
        const lastExpirationEvent = sortedEvents.find(e => e.deleted && e.action === 'expiration')

        if (expirationEvent && lastExpirationEvent && isRenewalPeriod) {
            items.push(getWhoisRemoveTimelineEvent(whoisRemoveDateEstimate, true))

            const date = new Date(whoisRemoveDateEstimate.getTime() - (new Date(expirationEvent.date).getTime() - new Date(lastExpirationEvent.date).getTime()))
            items.push(getWhoisRemoveTimelineEvent(date, false))
        } else {
            items.push(getWhoisRemoveTimelineEvent(whoisRemoveDateEstimate))
        }
    }

    items.push(
        ...sortedEvents
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
                        date: new Date(e.date),
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
        items={items.sort((a, b) => b.date.getTime() - a.date.getTime())}
    />
}
