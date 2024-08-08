import {Card, Divider, Popconfirm, Space, Table, Tag, theme, Typography} from "antd";
import {t} from "ttag";
import {deleteWatchlist} from "../../utils/api";
import {CalendarFilled, DeleteFilled, DisconnectOutlined, LinkOutlined} from "@ant-design/icons";
import React from "react";
import useBreakpoint from "../../hooks/useBreakpoint";
import {actionToColor, domainEvent} from "../search/EventTimeline";
import {Watchlist} from "../../pages/tracking/WatchlistPage";
import punycode from "punycode/punycode";

const {useToken} = theme;

export function WatchlistsList({watchlists, onDelete}: { watchlists: Watchlist[], onDelete: () => void }) {
    const {token} = useToken()
    const sm = useBreakpoint('sm')

    const domainEventTranslated = domainEvent()

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
                    hoverable
                    title={<>
                        {
                            watchlist.connector ?
                                <Tag icon={<LinkOutlined/>} color="lime-inverse" title={watchlist.connector.id}/> :
                                <Tag icon={<DisconnectOutlined/>} color="default"
                                     title={t`This Watchlist is not linked to a Connector.`}/>
                        }
                        <Typography.Text title={new Date(watchlist.createdAt).toLocaleString()}>
                            {t`Watchlist` + (watchlist.name ? ` (${watchlist.name})` : '')}
                        </Typography.Text>
                    </>
                    }
                    size='small'
                    style={{width: '100%'}}
                    extra={<Space size='middle'>
                        <Typography.Link href={`/api/watchlists/${watchlist.token}/calendar`}>
                            <CalendarFilled title={t`Export events to iCalendar format`}/>
                        </Typography.Link>
                        <Popconfirm
                            title={t`Delete the Watchlist`}
                            description={t`Are you sure to delete this Watchlist?`}
                            onConfirm={() => deleteWatchlist(watchlist.token).then(onDelete)}
                            okText={t`Yes`}
                            cancelText={t`No`}
                            okButtonProps={{danger: true}}>
                            <DeleteFilled style={{color: token.colorError}} title={t`Delete the Watchlist`}/>
                        </Popconfirm>
                    </Space>}
                >
                    <Card.Meta description={watchlist.token} style={{marginBottom: '1em'}}/>
                    <Table
                        size='small'
                        columns={columns}
                        pagination={false}
                        style={{width: '100%'}}
                        dataSource={[{
                            domains: watchlist.domains.map(d => <Tag>{punycode.toUnicode(d.ldhName)}</Tag>),
                            events: watchlist.triggers?.filter(t => t.action === 'email')
                                .map(t => <Tag color={actionToColor(t.event)}>
                                        {domainEventTranslated[t.event as keyof typeof domainEventTranslated]}
                                    </Tag>
                                )
                        }]}
                        {...(sm ? {scroll: {y: 'max-content'}} : {scroll: {y: 240}})}
                    />
                </Card>
                <Divider/>
            </>
        )}
    </>
}