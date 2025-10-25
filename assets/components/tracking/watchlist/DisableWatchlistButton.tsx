import {Popconfirm, theme, Typography} from 'antd'
import {t} from 'ttag'
import type { Watchlist} from '../../../utils/api'
import {patchWatchlist} from '../../../utils/api'
import {PauseCircleOutlined, PlayCircleOutlined} from '@ant-design/icons'
import React from 'react'

export function DisableWatchlistButton({watchlist, onChange, enabled}: {
    watchlist: Watchlist,
    onChange: () => void,
    enabled: boolean
}) {
    const {token} = theme.useToken()

    return (
        enabled ?
            <Popconfirm
                title={t`Disable the Watchlist`}
                description={t`Are you sure to disable this Watchlist?`}
                onConfirm={async () => await patchWatchlist(watchlist.token, {enabled: !enabled}).then(onChange)}
                okText={t`Yes`}
                cancelText={t`No`}
                okButtonProps={{danger: true}}
            >
                <Typography.Link>
                    <PauseCircleOutlined style={{color: token.colorText}} title={t`Disable the Watchlist`}/>
                </Typography.Link>
            </Popconfirm> : <Typography.Link>
                <PlayCircleOutlined style={{color: token.colorWarning}} title={t`Enable the Watchlist`}
                                     onClick={async () => await patchWatchlist(watchlist.token, {enabled: !enabled}).then(onChange)}/>
            </Typography.Link>
    )
}
