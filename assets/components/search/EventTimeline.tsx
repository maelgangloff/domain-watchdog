import {Progress, Timeline, Tooltip, Typography} from "antd";
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
                    const sameEvents = events.filter(se => se.action === e.action)
                    const isRelevant = !(sameEvents.length > 1 && sameEvents.indexOf(e) !== 0)

                    const eventName = <Typography.Text style={{color: isRelevant ? 'default' : 'grey'}}>
                        {e.action in rdapEventNameTranslated ? rdapEventNameTranslated[e.action as keyof typeof rdapEventNameTranslated] : e.action}
                    </Typography.Text>

                    const dateStr = <Typography.Text
                        style={{color: isRelevant ? 'default' : 'grey'}}>{new Date(e.date).toLocaleString(locale)}
                    </Typography.Text>

                    const eventDetail = e.action in rdapEventDetailTranslated ? rdapEventDetailTranslated[e.action as keyof typeof rdapEventDetailTranslated] : undefined

                    const text = sm ? {
                        children: <Tooltip placement='bottom' title={eventDetail}>
                            {eventName}&emsp;{dateStr}
                        </Tooltip>
                    } : {
                        label: dateStr,
                        children: <Tooltip placement='left' title={eventDetail}>{eventName}</Tooltip>,
                    }

                    return {
                        color: isRelevant ? actionToColor(e.action) : 'grey',
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