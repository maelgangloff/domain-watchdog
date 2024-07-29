import React, {useEffect, useState} from "react";
import {Card, Flex, Form, message, Skeleton} from "antd";
import {EventAction, getWatchlists, postWatchlist} from "../../utils/api";
import {AxiosError} from "axios";
import {t} from 'ttag'
import {WatchlistForm} from "../../components/tracking/WatchlistForm";
import {WatchlistsList} from "../../components/tracking/WatchlistsList";


type Watchlist = { token: string, domains: { ldhName: string }[], triggers?: { event: EventAction, action: string }[] }

export default function WatchlistPage() {

    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [watchlists, setWatchlists] = useState<Watchlist[] | null>()

    const onCreateWatchlist = (values: { domains: string[], triggers: { event: string, action: string }[] }) => {
        const domainsURI = values.domains.map(d => '/api/domains/' + d)
        postWatchlist(domainsURI, values.triggers).then((w) => {
            form.resetFields()
            refreshWatchlists()
            messageApi.success(t`Watchlist created !`)
        }).catch((e: AxiosError) => {
            const data = e?.response?.data as { detail: string }
            messageApi.error(data.detail ?? t`An error occurred`)
        })
    }

    const refreshWatchlists = () => getWatchlists().then(w => {
        setWatchlists(w['hydra:member'])
    }).catch(() => setWatchlists(undefined))

    useEffect(() => {
        refreshWatchlists()
    }, [])

    return <Flex gap="middle" align="center" justify="center" vertical>
        <Card title={t`Create a watchlist`} style={{width: '100%'}}>
            {contextHolder}
            <WatchlistForm form={form} onCreateWatchlist={onCreateWatchlist}/>
        </Card>


        <Skeleton loading={watchlists === undefined} active>
            {watchlists && watchlists.length > 0 && <Card title={t`My Watchlists`} style={{width: '100%'}}>
                <WatchlistsList watchlists={watchlists} onDelete={refreshWatchlists}/>
            </Card>
            }
        </Skeleton>
    </Flex>
}