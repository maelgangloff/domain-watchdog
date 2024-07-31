import React, {useEffect, useState} from "react";
import {Card, Divider, Flex, Form, message} from "antd";
import {EventAction, getWatchlists, postWatchlist} from "../../utils/api";
import {AxiosError} from "axios";
import {t} from 'ttag'
import {WatchlistForm} from "../../components/tracking/WatchlistForm";
import {WatchlistsList} from "../../components/tracking/WatchlistsList";
import {Connector, getConnectors} from "../../utils/api/connectors";


type Watchlist = { token: string, domains: { ldhName: string }[], triggers?: { event: EventAction, action: string }[] }

export default function WatchlistPage() {

    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [watchlists, setWatchlists] = useState<Watchlist[] | null>()
    const [connectors, setConnectors] = useState<(Connector & { id: string })[] | null>()

    const onCreateWatchlist = (values: {
        domains: string[],
        triggers: { event: string, action: string, connector?: string }[]
        connector?: string
    }) => {
        const domainsURI = values.domains.map(d => '/api/domains/' + d)
        postWatchlist({
            domains: domainsURI,
            triggers: values.triggers,
            connector: values.connector !== undefined ? '/api/connectors/' + values.connector : undefined
        }).then((w) => {
            form.resetFields()
            refreshWatchlists()
            messageApi.success(t`Watchlist created !`)
        }).catch((e: AxiosError) => {
            const data = e?.response?.data as { detail: string }
            messageApi.error(data?.detail ?? t`An error occurred`)
        })
    }

    const refreshWatchlists = () => getWatchlists().then(w => {
        setWatchlists(w['hydra:member'])
    }).catch((e: AxiosError) => {
        const data = e?.response?.data as { detail: string }
        messageApi.error(data?.detail ?? t`An error occurred`)
        setWatchlists(undefined)
    })

    useEffect(() => {
        refreshWatchlists()
        getConnectors()
            .then(c => setConnectors(c['hydra:member']))
            .catch((e: AxiosError) => {
                const data = e?.response?.data as { detail: string }
                messageApi.error(data?.detail ?? t`An error occurred`)
            })
    }, [])

    return <Flex gap="middle" align="center" justify="center" vertical>
        <Card title={t`Create a Watchlist`} style={{width: '100%'}}>
            {contextHolder}
            {
                connectors &&
                <WatchlistForm form={form} onCreateWatchlist={onCreateWatchlist} connectors={connectors}/>
            }
        </Card>

        <Divider/>

        <Card size="small" loading={!watchlists} title={t`My Watchlists`} style={{width: '100%'}}>
            {watchlists && watchlists.length > 0 &&
                <WatchlistsList watchlists={watchlists} onDelete={refreshWatchlists}/>}
        </Card>
    </Flex>
}