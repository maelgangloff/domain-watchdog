import {Drawer, Form, Typography} from 'antd'
import {t} from 'ttag'
import {WatchlistForm} from './WatchlistForm'
import React, {useState} from 'react'
import {EditOutlined} from '@ant-design/icons'
import type {Connector} from '../../../utils/api/connectors'
import type {Watchlist} from '../../../utils/api'
import useBreakpoint from "../../../hooks/useBreakpoint"

export function UpdateWatchlistButton({watchlist, onUpdateWatchlist, connectors}: {
    watchlist: Watchlist
    onUpdateWatchlist: (values: { domains: string[], trackedEvents: string[], trackedEppStatus: string[], token: string }) => Promise<void>
    connectors: Array<Connector & { id: string }>
}) {
    const [form] = Form.useForm()
    const [open, setOpen] = useState(false)
    const [loading, setLoading] = useState(false)
    const sm = useBreakpoint('sm')

    const showDrawer = () => setOpen(true)

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
                        {name: 'trackedEvents', value: watchlist.trackedEvents},
                        {name: 'trackedEppStatus', value: watchlist.trackedEppStatus},
                        {name: 'dsn', value: watchlist.dsn}
                    ])
                }}
                />
            </Typography.Link>
            <Drawer
                title={t`Update a Watchlist`}
                width={sm ? '100%' : '80%'}
                onClose={onClose}
                open={open}
                loading={loading}
                styles={{
                    body: {
                        paddingBottom: 80
                    }
                }}
            >
                <WatchlistForm
                    form={form}
                    onFinish={values => {
                        setLoading(true)
                        onUpdateWatchlist(values).then(onClose).catch(() => setLoading(false))
                    }}
                    connectors={connectors}
                    isCreation={false}
                    watchlist={watchlist}
                />
            </Drawer>
        </>
    )
}
