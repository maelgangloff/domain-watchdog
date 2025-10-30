import {Button, Drawer, Form} from 'antd'
import {t} from 'ttag'
import {WatchlistForm} from './WatchlistForm'
import React, {useState} from 'react'
import type {Connector} from '../../../utils/api/connectors'
import useBreakpoint from "../../../hooks/useBreakpoint"

export function CreateWatchlistButton({onUpdateWatchlist, connectors}: {
    onUpdateWatchlist: (values: {
        domains: string[],
        trackedEvents: string[],
        trackedEppStatus: string[],
        token: string
    }) => Promise<void>
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
            <Button type='default' block onClick={() => {
                showDrawer()
            }}>{t`Create a Watchlist`}</Button>
            <Drawer
                title={t`Create a Watchlist`}
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
                    isCreation
                />
            </Drawer>
        </>
    )
}
