import {Card, Col, Divider, Row, Space, Tag, Tooltip} from "antd";
import {DisconnectOutlined, LinkOutlined} from "@ant-design/icons";
import {t} from "ttag";
import {ViewDiagramWatchlistButton} from "./diagram/ViewDiagramWatchlistButton";
import {UpdateWatchlistButton} from "./UpdateWatchlistButton";
import {DeleteWatchlistButton} from "./DeleteWatchlistButton";
import React from "react";
import {Watchlist} from "../../../pages/tracking/WatchlistPage";
import {Connector} from "../../../utils/api/connectors";
import useBreakpoint from "../../../hooks/useBreakpoint";
import {CalendarWatchlistButton} from "./CalendarWatchlistButton";
import {rdapEventDetailTranslation, rdapEventNameTranslation} from "../../../utils/functions/rdapTranslation";

import {actionToColor} from "../../../utils/functions/actionToColor";
import {DomainToTag} from "../DomainToTag";

export function WatchlistCard({watchlist, onUpdateWatchlist, connectors, onDelete}: {
    watchlist: Watchlist,
    onUpdateWatchlist: (values: { domains: string[], triggers: string[], token: string }) => Promise<void>,
    connectors: (Connector & { id: string })[],
    onDelete: () => void
}) {
    const rdapEventNameTranslated = rdapEventNameTranslation()
    const rdapEventDetailTranslated = rdapEventDetailTranslation()

    return <>
        <Card
            type='inner'
            title={<>
                {
                    watchlist.connector ?
                        <Tooltip title={watchlist.connector.id}>
                            <Tag icon={<LinkOutlined/>} color="lime-inverse"/>
                        </Tooltip> :
                        <Tooltip title={t`This Watchlist is not linked to a Connector.`}>
                            <Tag icon={<DisconnectOutlined/>} color="default"/>
                        </Tooltip>
                }
                <Tooltip title={new Date(watchlist.createdAt).toLocaleString()}>
                    {t`Watchlist` + (watchlist.name ? ` (${watchlist.name})` : '')}
                </Tooltip>
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
            <Row gutter={16}>
                <Col span={16}>
                    {watchlist.domains.map(d => <DomainToTag domain={d}/>)}
                </Col>
                <Col span={8}>
                    {watchlist.triggers?.filter(t => t.action === 'email')
                        .map(t => <Tooltip
                                title={rdapEventDetailTranslated[t.event as keyof typeof rdapEventDetailTranslated] || undefined}>
                                <Tag color={actionToColor(t.event)}>
                                    {rdapEventNameTranslated[t.event as keyof typeof rdapEventNameTranslated]}
                                </Tag>
                            </Tooltip>
                        )}
                </Col>
            </Row>
        </Card>
        <Divider/>
    </>
}