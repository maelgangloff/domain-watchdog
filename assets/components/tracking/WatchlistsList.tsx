import {Button, Card, Divider, Drawer, Form, Popconfirm, Space, Table, Tag, theme, Typography} from "antd";
import {t} from "ttag";
import {deleteWatchlist} from "../../utils/api";
import {CalendarFilled, DeleteFilled, DisconnectOutlined, EditOutlined, LinkOutlined} from "@ant-design/icons";
import React, {useState} from "react";
import useBreakpoint from "../../hooks/useBreakpoint";
import {actionToColor, domainEvent} from "../search/EventTimeline";
import {Watchlist} from "../../pages/tracking/WatchlistPage";
import punycode from "punycode/punycode";
import {WatchlistForm} from "./WatchlistForm";
import {Connector} from "../../utils/api/connectors";

const {useToken} = theme;

export function WatchlistsList({watchlists, onDelete, onUpdateWatchlist, connectors}: {
    watchlists: Watchlist[],
    onDelete: () => void,
    onUpdateWatchlist: (values: { domains: string[], emailTriggers: string[], token: string }) => void,
    connectors: (Connector & { id: string })[]
}) {
    const {token} = useToken()
    const sm = useBreakpoint('sm')
    const domainEventTranslated = domainEvent()
    const [form] = Form.useForm()

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

    const [open, setOpen] = useState(false);

    const showDrawer = () => {
        setOpen(true)
    };

    const onClose = () => {
        setOpen(false)
    };

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
                        <Typography.Link>
                            <CalendarFilled title={t`Export events to iCalendar format`}/>
                        </Typography.Link>
                        <Typography.Link>
                            <EditOutlined title={t`Edit the Watchlist`} onClick={() => {
                                showDrawer()
                                form.setFields([
                                    {name: 'token', value: watchlist.token},
                                    {name: 'name', value: watchlist.name},
                                    {name: 'connector', value: watchlist.connector?.id},
                                    {name: 'domains', value: watchlist.domains.map(d => d.ldhName)},
                                    {name: 'emailTriggers', value: watchlist.triggers?.map(t => t.event)},
                                ])
                            }}/>
                        </Typography.Link>

                        <Drawer
                            title={t`Update a Watchlist`}
                            width={800}
                            onClose={onClose}
                            open={open}
                            styles={{
                                body: {
                                    paddingBottom: 80,
                                }
                            }}
                            extra={<Button onClick={onClose}>Cancel</Button>}
                        >
                            <WatchlistForm
                                form={form}
                                onFinish={values => {
                                    onUpdateWatchlist(values);
                                    onClose()
                                }}
                                connectors={connectors}
                                isCreation={false}
                            />
                        </Drawer>

                        <Popconfirm
                            title={t`Delete the Watchlist`}
                            description={t`Are you sure to delete this Watchlist?`}
                            onConfirm={() => deleteWatchlist(watchlist.token).then(onDelete)}
                            okText={t`Yes`}
                            cancelText={t`No`}
                            okButtonProps={{danger: true}}>
                            <Typography.Link>
                                <DeleteFilled style={{color: token.colorError}} title={t`Delete the Watchlist`}/>
                            </Typography.Link>
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