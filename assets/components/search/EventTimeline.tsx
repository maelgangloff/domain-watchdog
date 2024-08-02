import {
    ClockCircleOutlined,
    DeleteOutlined,
    ReloadOutlined,
    ShareAltOutlined,
    SignatureOutlined,
    SyncOutlined
} from "@ant-design/icons";
import {Timeline} from "antd";
import React from "react";
import {Domain, EventAction} from "../../utils/api";
import {t} from "ttag";
import useBreakpoint from "../../hooks/useBreakpoint";

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

    const domainEvent = {
        registration: t`Registration`,
        reregistration: t`Reregistration`,
        'last changed': t`Last changed`,
        expiration: t`Expiration`,
        deletion: t`Deletion`,
        reinstantiation: t`Reinstantiation`,
        transfer: t`Transfer`,
        locked: t`Locked`,
        unlocked: t`Unlocked`,
        'registrar expiration': t`Registrar expiration`,
        'enum validation expiration': t`ENUM validation expiration`
    }

    const locale = navigator.language.split('-')[0]

    return <Timeline
        mode={sm ? "left" : "right"}
        items={domain.events
            .sort((e1, e2) => new Date(e2.date).getTime() - new Date(e1.date).getTime())
            .map(({action, date}) => {

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

                    const eventName = Object.keys(domainEvent).includes(action) ? domainEvent[action as keyof typeof domainEvent] : action
                    const dateStr = new Date(date).toLocaleString(locale)

                    const text = sm ? {
                        children: <>{eventName}&emsp;{dateStr}</>
                    } : {
                        label: dateStr,
                        children: eventName,
                    }

                    return {
                        color: actionToColor(action),
                        dot,
                        pending: new Date(date).getTime() > new Date().getTime(),
                        ...text
                    }
                }
            )
        }
    />
}