import {Button, Drawer, Form, Typography} from 'antd'
import {t} from 'ttag'
import {WatchlistForm} from './WatchlistForm'
import React, {useState} from 'react'
import {EditOutlined} from '@ant-design/icons'
import type {Connector} from '../../../utils/api/connectors'
import type {Watchlist} from '../../../utils/api'

export function UpdateWatchlistButton({watchlist, onUpdateWatchlist, connectors}: {
    watchlist: Watchlist
    onUpdateWatchlist: (values: { domains: string[], triggers: string[], token: string }) => Promise<void>
    connectors: Array<Connector & { id: string }>
}) {
    const [form] = Form.useForm()
    const [open, setOpen] = useState(false)
    const [loading, setLoading] = useState(false)

    const showDrawer = () => {
        setOpen(true)
    }

    const onClose = () => {
        setOpen(false)
        setLoading(false)
    }

    return (
        <>
            <Typography.Link>
                <EditOutlined
                    title={t`Edit the Watchlist`} onClick={() => {
                    showDrawer()
                    form.setFields([
                        {name: 'token', value: watchlist.token},
                        {name: 'name', value: watchlist.name},
                        {name: 'connector', value: watchlist.connector?.id},
                        {name: 'domains', value: watchlist.domains.map(d => d.ldhName)},
                        {name: 'triggers', value: [...new Set(watchlist.triggers?.map(t => t.event))]},
                        {name: 'dsn', value: watchlist.dsn}
                    ])
                }}
                />
            </Typography.Link>
            <Drawer
                title={t`Update a Watchlist`}
                width='80%'
                onClose={onClose}
                open={open}
                loading={loading}
                styles={{
                    body: {
                        paddingBottom: 80
                    }
                }}
                extra={<Button onClick={onClose}>{t`Cancel`}</Button>}
            >
                <WatchlistForm
                    form={form}
                    onFinish={values => {
                        setLoading(true)
                        onUpdateWatchlist(values).then(onClose).catch(() => setLoading(false))
                    }}
                    connectors={connectors}
                    isCreation={false}
                    watchList={watchlist}
                />
            </Drawer>
        </>
    )
}
