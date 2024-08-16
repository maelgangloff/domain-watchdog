import {Card, Divider, Space, Table, Tag, Typography} from "antd";
import {t} from "ttag";
import {CalendarFilled, DisconnectOutlined, LinkOutlined} from "@ant-design/icons";
import React from "react";
import useBreakpoint from "../../hooks/useBreakpoint";
import {actionToColor, domainEvent} from "../search/EventTimeline";
import {Watchlist} from "../../pages/tracking/WatchlistPage";
import punycode from "punycode/punycode";
import {Connector} from "../../utils/api/connectors";
import {UpdateWatchlistButton} from "./UpdateWatchlistButton";
import {DeleteWatchlistButton} from "./DeleteWatchlistButton";
import {ViewDiagramWatchlistButton} from "./ViewDiagramWatchlistButton";

export function WatchlistsList({watchlists, onDelete, onUpdateWatchlist, connectors}: {
    watchlists: Watchlist[],
    onDelete: () => void,
    onUpdateWatchlist: (values: { domains: string[], emailTriggers: string[], token: string }) => Promise<void>,
    connectors: (Connector & { id: string })[]
}) {
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

                        <ViewDiagramWatchlistButton token={watchlist.token}/>

                        <Typography.Link href={`/api/watchlists/${watchlist.token}/calendar`}>
                            <CalendarFilled title={t`Export events to iCalendar format`}
                                            style={{color: 'limegreen'}}/>
                        </Typography.Link>

                        <UpdateWatchlistButton
                            watchlist={watchlist}
                            onUpdateWatchlist={onUpdateWatchlist}
                            connectors={connectors}
                        />

                        <DeleteWatchlistButton watchlist={watchlist} onDelete={onDelete}/>

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