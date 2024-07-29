import {Card, Divider, Popconfirm, Typography} from "antd";
import {t} from "ttag";
import {deleteWatchlist, EventAction} from "../../utils/api";
import {DeleteFilled} from "@ant-design/icons";
import React from "react";

type Watchlist = { token: string, domains: { ldhName: string }[], triggers?: { event: EventAction, action: string }[] }


export function WatchlistsList({watchlists, onDelete}: { watchlists: Watchlist[], onDelete: () => void }) {
    return <>
        {watchlists.map(watchlist =>
            <>
                <Card title={t`Watchlist ${watchlist.token}`} extra={<Popconfirm
                    title={t`Delete the Watchlist`}
                    description={t`Are you sure to delete this Watchlist?`}
                    onConfirm={() => deleteWatchlist(watchlist.token).then(onDelete)}
                    okText={t`Yes`}
                    cancelText={t`No`}
                ><DeleteFilled/></Popconfirm>}>
                    <Typography.Paragraph>
                        {t`Domain name`} : {watchlist?.domains.map(d => d.ldhName).join(',')}
                    </Typography.Paragraph>
                    {
                        watchlist.triggers && <Typography.Paragraph>
                            {t`Domain triggers`} : {watchlist.triggers.map(t => `${t.event} => ${t.action}`).join(',')}
                        </Typography.Paragraph>
                    }
                </Card>
                <Divider/>
            </>
        )}
    </>
}