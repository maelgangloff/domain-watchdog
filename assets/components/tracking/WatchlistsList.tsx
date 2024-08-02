import {Card, Divider, Popconfirm, Table, Tag, theme, Typography} from "antd";
import {t} from "ttag";
import {deleteWatchlist} from "../../utils/api";
import {DeleteFilled} from "@ant-design/icons";
import React from "react";
import useBreakpoint from "../../hooks/useBreakpoint";
import {actionToColor} from "../search/EventTimeline";
import {Watchlist} from "../../pages/tracking/WatchlistPage";

const {useToken} = theme;


export function WatchlistsList({watchlists, onDelete}: { watchlists: Watchlist[], onDelete: () => void }) {
    const {token} = useToken()
    const sm = useBreakpoint('sm')

    const columns = [
        {
            title: t`Domain names`,
            dataIndex: 'domains'
        },
        {
            title: t`Tracked events`,
            dataIndex: 'events'
        }
    ]

    return <>
        {watchlists.map(watchlist =>
            <>
                <Card
                    title={t`Watchlist`}
                    size='small'
                    extra={<Popconfirm
                        title={t`Delete the Watchlist`}
                        description={t`Are you sure to delete this Watchlist?`}
                        onConfirm={() => deleteWatchlist(watchlist.token).then(onDelete)}
                        okText={t`Yes`}
                        cancelText={t`No`}
                        okButtonProps={{danger: true}}>
                        <DeleteFilled style={{color: token.colorError}}/>
                    </Popconfirm>}
                >
                    <Card.Meta description={watchlist.token} style={{marginBottom: '1em'}}/>
                    <Table
                        columns={columns}
                        pagination={false}
                        dataSource={[{
                            domains: watchlist.domains.map(d =>
                                <><Typography.Text code>{d.ldhName}</Typography.Text><br/></>),
                            events: watchlist.triggers?.filter(t => t.action === 'email')
                                .map(t => <Tag color={actionToColor(t.event)}>{t.event}</Tag>)
                        }]}
                        {...(sm ? {scroll: {y: 'max-content'}} : {scroll: {y: 240}})}
                    />
                </Card>
                <Divider/>
            </>
        )}
    </>
}