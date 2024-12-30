import {Popconfirm, theme, Typography} from 'antd'
import {t} from 'ttag'
import {deleteWatchlist, Watchlist} from '../../../utils/api'
import {DeleteFilled} from '@ant-design/icons'
import React from 'react'

export function DeleteWatchlistButton({watchlist, onDelete}: { watchlist: Watchlist, onDelete: () => void }) {
    const {token} = theme.useToken()

    return (
        <Popconfirm
            title={t`Delete the Watchlist`}
            description={t`Are you sure to delete this Watchlist?`}
            onConfirm={async () => await deleteWatchlist(watchlist.token).then(onDelete)}
            okText={t`Yes`}
            cancelText={t`No`}
            okButtonProps={{danger: true}}
        >
            <Typography.Link>
                <DeleteFilled style={{color: token.colorError}} title={t`Delete the Watchlist`}/>
            </Typography.Link>
        </Popconfirm>
    )
}
