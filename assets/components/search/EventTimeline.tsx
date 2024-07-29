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
import {Domain} from "../../utils/api";
import {t} from "ttag";

export function EventTimeline({domain}: { domain: Domain }) {

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
        mode="right"
        items={domain.events
            .sort((e1, e2) => new Date(e2.date).getTime() - new Date(e1.date).getTime())
            .map(({action, date}) => {

                    let color, dot
                    if (action === 'registration') {
                        color = 'green'
                        dot = <SignatureOutlined style={{fontSize: '16px'}}/>
                    } else if (action === 'expiration') {
                        color = 'red'
                        dot = <ClockCircleOutlined style={{fontSize: '16px'}}/>
                    } else if (action === 'transfer') {
                        color = 'orange'
                        dot = <ShareAltOutlined style={{fontSize: '16px'}}/>
                    } else if (action === 'last changed') {
                        color = 'blue'
                        dot = <SyncOutlined style={{fontSize: '16px'}}/>
                    } else if (action === 'deletion') {
                        color = 'red'
                        dot = <DeleteOutlined style={{fontSize: '16px'}}/>
                    } else if (action === 'reregistration') {
                        color = 'green'
                        dot = <ReloadOutlined style={{fontSize: '16px'}}/>
                    }

                    return {
                        label: new Date(date).toLocaleString(locale),
                        children: Object.keys(domainEvent).includes(action) ? domainEvent[action as keyof typeof domainEvent] : action,
                        color,
                        dot,
                        pending: new Date(date).getTime() > new Date().getTime()
                    }
                }
            )
        }
    />
}