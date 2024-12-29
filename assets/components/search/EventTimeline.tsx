import {Timeline, Tooltip, Typography} from "antd";
import React from "react";
import {Event} from "../../utils/api";
import useBreakpoint from "../../hooks/useBreakpoint";
import {rdapEventDetailTranslation, rdapEventNameTranslation} from "../../utils/functions/rdapTranslation";
import {actionToColor} from "../../utils/functions/actionToColor";
import {actionToIcon} from "../../utils/functions/actionToIcon";

export function EventTimeline({events}: { events: Event[] }) {
    const sm = useBreakpoint('sm')

    const locale = navigator.language.split('-')[0]
    const rdapEventNameTranslated = rdapEventNameTranslation()
    const rdapEventDetailTranslated = rdapEventDetailTranslation()

    return <>
        <Timeline
            mode={sm ? "left" : "right"}
            items={events.map(e => {
                    const eventName = <Typography.Text style={{color: e.deleted ? 'grey' : 'default'}}>
                        {rdapEventNameTranslated[e.action as keyof typeof rdapEventNameTranslated] || e.action}
                    </Typography.Text>

                    const dateStr = <Typography.Text
                        style={{color: e.deleted ? 'grey' : 'default'}}>{new Date(e.date).toLocaleString(locale)}
                    </Typography.Text>

                    const eventDetail = rdapEventDetailTranslated[e.action as keyof typeof rdapEventDetailTranslated] || undefined

                    const text = sm ? {
                        children: <Tooltip placement='bottom' title={eventDetail}>
                            {eventName}&emsp;{dateStr}
                        </Tooltip>
                    } : {
                        label: dateStr,
                        children: <Tooltip placement='left' title={eventDetail}>{eventName}</Tooltip>,
                    }

                    return {
                        color: e.deleted ? 'grey' : actionToColor(e.action),
                        dot: actionToIcon(e.action),
                        pending: new Date(e.date).getTime() > new Date().getTime(),
                        ...text
                    }
                }
            )
            }
        />
    </>
}