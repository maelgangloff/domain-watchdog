import {CalendarFilled} from '@ant-design/icons'
import {t} from 'ttag'
import {Popover, QRCode, Typography} from 'antd'
import React from 'react'
import {Watchlist} from '../../../utils/api'

export function CalendarWatchlistButton({watchlist}: { watchlist: Watchlist }) {
    const icsResourceLink = `${window.location.origin}/api/watchlists/${watchlist.token}/calendar`

    return (
        <Typography.Link href={icsResourceLink}>
            <Popover content={<QRCode
                value={icsResourceLink}
                bordered={false}
                title={t`QR Code for iCalendar export`}
                type='svg'
            />}
            >
                <CalendarFilled
                    title={t`Export events to iCalendar format`}
                    style={{color: 'limegreen'}}
                />
            </Popover>
        </Typography.Link>
    )
}
