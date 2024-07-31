import {Card, Divider, Popconfirm, theme, Typography} from "antd";
import {t} from "ttag";
import {deleteWatchlist, EventAction} from "../../utils/api";
import {DeleteFilled} from "@ant-design/icons";
import React from "react";

const {useToken} = theme;

type Watchlist = { token: string, domains: { ldhName: string }[], triggers?: { event: EventAction, action: string }[] }


export function WatchlistsList({watchlists, onDelete}: { watchlists: Watchlist[], onDelete: () => void }) {
    const {token} = useToken()

    return <>
        {watchlists.map(watchlist =>
            <>
                <Card title={t`Watchlist`} type="inner" extra={<Popconfirm
                    title={t`Delete the Watchlist`}
                    description={t`Are you sure to delete this Watchlist?`}
                    onConfirm={() => deleteWatchlist(watchlist.token).then(onDelete)}
                    okText={t`Yes`}
                    cancelText={t`No`}
                    okButtonProps={{danger: true}}
                ><DeleteFilled style={{color: token.colorError}}/></Popconfirm>}>
                    <Card.Meta description={watchlist.token} style={{marginBottom: '1em'}}/>
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