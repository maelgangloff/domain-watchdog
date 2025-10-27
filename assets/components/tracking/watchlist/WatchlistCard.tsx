import {Card, Col, Divider, Row, Space, Tag, Tooltip} from 'antd'
import {DisconnectOutlined, LinkOutlined} from '@ant-design/icons'
import {t} from 'ttag'
import {ViewDiagramWatchlistButton} from './diagram/ViewDiagramWatchlistButton'
import {UpdateWatchlistButton} from './UpdateWatchlistButton'
import {DeleteWatchlistButton} from './DeleteWatchlistButton'
import React from 'react'
import type {Connector} from '../../../utils/api/connectors'
import {CalendarWatchlistButton} from './CalendarWatchlistButton'
import {
    rdapDomainStatusCodeDetailTranslation,
    rdapEventDetailTranslation,
    rdapEventNameTranslation
} from '../../../utils/functions/rdapTranslation'

import {actionToColor} from '../../../utils/functions/actionToColor'
import {DomainToTag} from '../../../utils/functions/DomainToTag'
import type {Watchlist} from '../../../utils/api'
import {eppStatusCodeToColor} from "../../../utils/functions/eppStatusCodeToColor"
import {DisableWatchlistButton} from "./DisableWatchlistButton"

export function WatchlistCard({watchlist, onUpdateWatchlist, connectors, onChange}: {
    watchlist: Watchlist
    onUpdateWatchlist: (values: {
        domains: string[],
        trackedEvents: string[],
        trackedEppStatus: string[],
        token: string
    }) => Promise<void>
    connectors: Array<Connector & { id: string }>
    onChange: () => void
}) {
    const rdapEventNameTranslated = rdapEventNameTranslation()
    const rdapEventDetailTranslated = rdapEventDetailTranslation()
    const rdapDomainStatusCodeDetailTranslated = rdapDomainStatusCodeDetailTranslation()

    return (
        <>
            <Card
                aria-disabled={true}
                type='inner'
                style={{
                    width: '100%',
                    opacity: watchlist.enabled ? 1 : 0.5,
                    filter: watchlist.enabled ? 'none' : 'grayscale(0.7)',
                    transition: 'all 0.3s ease',
                }}
                title={<>
                    {
                        (watchlist.connector != null)
                            ? <Tooltip title={watchlist.connector.id}>
                                <Tag icon={<LinkOutlined/>} color='lime-inverse'/>
                            </Tooltip>
                            : <Tooltip title={t`This Watchlist is not linked to a Connector.`}>
                                <Tag icon={<DisconnectOutlined/>} color='default'/>
                            </Tooltip>
                    }
                    <Tooltip title={new Date(watchlist.createdAt).toLocaleString()}>
                        {t`Watchlist` + (watchlist.name ? ` (${watchlist.name})` : '')}
                    </Tooltip>
                </>}
                size='small'
                extra={
                    <Space size='middle'>
                        <ViewDiagramWatchlistButton token={watchlist.token}/>

                        <CalendarWatchlistButton watchlist={watchlist}/>

                        <UpdateWatchlistButton
                            watchlist={watchlist}
                            onUpdateWatchlist={onUpdateWatchlist}
                            connectors={connectors}
                        />

                        <DisableWatchlistButton watchlist={watchlist} onChange={onChange}
                                                enabled={watchlist.enabled}/>
                        <DeleteWatchlistButton watchlist={watchlist} onDelete={onChange}/>
                    </Space>
                }
            >
                <Card.Meta description={watchlist.token} style={{marginBottom: '1em'}}/>
                <Row gutter={16}>
                    <Col span={16}>
                        {watchlist.domains.map(d => (
                            <DomainToTag key={d.ldhName} domain={d}/>
                        ))}
                    </Col>

                    <Col span={8}>
                        <>
                            <div style={{
                                fontWeight: 500,
                                marginBottom: '0.5em',
                                color: '#555',
                                fontSize: '0.9em'
                            }}>
                                {t`Tracked events`}
                            </div>
                            <div style={{marginBottom: '1em'}}>
                                {watchlist.trackedEvents?.map(t => (
                                    <Tooltip
                                        key={t}
                                        title={rdapEventDetailTranslated[t as keyof typeof rdapEventDetailTranslated]}
                                    >
                                        <Tag color={actionToColor(t)} style={{marginBottom: 4}}>
                                            {rdapEventNameTranslated[t as keyof typeof rdapEventNameTranslated]}
                                        </Tag>
                                    </Tooltip>
                                ))}
                            </div>
                        </>
                        <>
                            <div style={{
                                fontWeight: 500,
                                marginBottom: '0.5em',
                                color: '#555',
                                fontSize: '0.9em'
                            }}>
                                {t`Tracked EPP status`}
                            </div>
                            <div>
                                {watchlist.trackedEppStatus?.map(t => (
                                    <Tooltip
                                        key={t}
                                        title={rdapDomainStatusCodeDetailTranslated[t as keyof typeof rdapDomainStatusCodeDetailTranslated]}
                                    >
                                        <Tag color={eppStatusCodeToColor(t)} style={{marginBottom: 4}}>
                                            {t}
                                        </Tag>
                                    </Tooltip>
                                ))}
                            </div>
                        </>
                    </Col>
                </Row>
            </Card>
            <Divider/>
        </>
    )
}
