import {
    ClockCircleOutlined,
    DeleteOutlined,
    ReloadOutlined,
    ShareAltOutlined,
    SignatureOutlined,
    SyncOutlined
} from "@ant-design/icons";
import {Timeline, Tooltip} from "antd";
import React from "react";
import {Domain, EventAction} from "../../utils/api";
import useBreakpoint from "../../hooks/useBreakpoint";
import {rdapEventDetailTranslation, rdapEventNameTranslation} from "./rdapTranslation";

export function actionToColor(a: EventAction) {
    return a === 'registration' ? 'green' :
        a === 'reregistration' ? 'cyan' :
            a === 'expiration' ? 'red' :
                a === 'deletion' ? 'magenta' :
                    a === 'transfer' ? 'orange' :
                        a === 'last changed' ? 'blue' : 'default'
}


export function EventTimeline({domain}: { domain: Domain }) {
    const sm = useBreakpoint('sm')

    const locale = navigator.language.split('-')[0]
    const rdapEventNameTranslated = rdapEventNameTranslation()
    const rdapEventDetailTranslated = rdapEventDetailTranslation()

    const domainEvents = domain.events.sort((e1, e2) => new Date(e2.date).getTime() - new Date(e1.date).getTime())
    const expirationEvents = domainEvents.filter(e => e.action === 'expiration')

    return <Timeline
        mode={sm ? "left" : "right"}
        items={domainEvents.map(({action, date}) => {
                let dot
                if (action === 'registration') {
                    dot = <SignatureOutlined style={{fontSize: '16px'}}/>
                } else if (action === 'expiration') {
                    dot = <ClockCircleOutlined style={{fontSize: '16px'}}/>
                } else if (action === 'transfer') {
                    dot = <ShareAltOutlined style={{fontSize: '16px'}}/>
                } else if (action === 'last changed') {
                    dot = <SyncOutlined style={{fontSize: '16px'}}/>
                } else if (action === 'deletion') {
                    dot = <DeleteOutlined style={{fontSize: '16px'}}/>
                } else if (action === 'reregistration') {
                    dot = <ReloadOutlined style={{fontSize: '16px'}}/>
                }

                const eventName = action in rdapEventNameTranslated ? rdapEventNameTranslated[action as keyof typeof rdapEventNameTranslated] : action
                const dateStr = new Date(date).toLocaleString(locale)
                const eventDetail = action in rdapEventDetailTranslated ? rdapEventDetailTranslated[action as keyof typeof rdapEventDetailTranslated] : undefined

                const text = sm ? {
                    children: <Tooltip placement='bottom' title={eventDetail}>
                        {eventName}&emsp;{dateStr}
                    </Tooltip>
                } : {
                    label: dateStr,
                    children: <Tooltip placement='left' title={eventDetail}>{eventName}</Tooltip>,
                }

                return {
                    color: (action === 'expiration' ? (expirationEvents.length > 0 && domainEvents[0].date === date) : true) ? actionToColor(action) : 'grey',
                    dot,
                    pending: new Date(date).getTime() > new Date().getTime(),
                    ...text
                }
            }
        )
        }
    />
}