import {Card, Divider, Space, Table, Tag, Typography} from "antd";
import {DisconnectOutlined, LinkOutlined} from "@ant-design/icons";
import {t} from "ttag";
import {ViewDiagramWatchlistButton} from "./diagram/ViewDiagramWatchlistButton";
import {UpdateWatchlistButton} from "./UpdateWatchlistButton";
import {DeleteWatchlistButton} from "./DeleteWatchlistButton";
import punycode from "punycode/punycode";
import {actionToColor} from "../../search/EventTimeline";
import React from "react";
import {Watchlist} from "../../../pages/tracking/WatchlistPage";
import {Connector} from "../../../utils/api/connectors";
import useBreakpoint from "../../../hooks/useBreakpoint";
import {CalendarWatchlistButton} from "./CalendarWatchlistButton";
import {rdapEventNameTranslation} from "../../search/rdapTranslation";

export function WatchlistCard({watchlist, onUpdateWatchlist, connectors, onDelete}: {
    watchlist: Watchlist,
    onUpdateWatchlist: (values: { domains: string[], triggers: string[], token: string }) => Promise<void>,
    connectors: (Connector & { id: string })[],
    onDelete: () => void
}) {
    const sm = useBreakpoint('sm')
    const rdapEventNameTranslated = rdapEventNameTranslation()

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
        <Card
            type='inner'
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
            extra={
                <Space size='middle'>
                    <ViewDiagramWatchlistButton token={watchlist.token}/>

                    <CalendarWatchlistButton watchlist={watchlist}/>

                    <UpdateWatchlistButton
                        watchlist={watchlist}
                        onUpdateWatchlist={onUpdateWatchlist}
                        connectors={connectors}
                    />

                    <DeleteWatchlistButton watchlist={watchlist} onDelete={onDelete}/>
                </Space>
            }
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
                                {rdapEventNameTranslated[t.event as keyof typeof rdapEventNameTranslated]}
                            </Tag>
                        )
                }]}
                {...(sm ? {scroll: {y: 'max-content'}} : {scroll: {y: 240}})}
            />
        </Card>
        <Divider/>
    </>
}