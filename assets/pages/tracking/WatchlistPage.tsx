import React, {useEffect, useState} from 'react'
import {Card, Divider, Flex, Form, message} from 'antd'
import type {Watchlist, WatchlistTrigger} from '../../utils/api'
import {getWatchlists, postWatchlist, putWatchlist} from '../../utils/api'
import type {AxiosError} from 'axios'
import {t} from 'ttag'
import {WatchlistForm} from '../../components/tracking/watchlist/WatchlistForm'
import {WatchlistsList} from '../../components/tracking/watchlist/WatchlistsList'
import type {Connector} from '../../utils/api/connectors'
import { getConnectors} from '../../utils/api/connectors'

import {showErrorAPI} from '../../utils/functions/showErrorAPI'

interface FormValuesType {
    name?: string
    domains: string[]
    triggers: string[]
    connector?: string
    dsn?: string[]
}

const getRequestDataFromFormCreation = (values: FormValuesType) => {
    const domainsURI = values.domains.map(d => '/api/domains/' + d.toLowerCase())
    let triggers: WatchlistTrigger[] = values.triggers.map(t => ({event: t, action: 'email'}))

    if (values.dsn !== undefined) {
        triggers = [...triggers, ...values.triggers.map((t): WatchlistTrigger => ({
            event: t,
            action: 'chat'
        }))]
    }

    return {
        name: values.name,
        domains: domainsURI,
        triggers,
        connector: values.connector !== undefined ? ('/api/connectors/' + values.connector) : undefined,
        dsn: values.dsn
    }
}

const getRequestDataFromFormUpdate = (values: FormValuesType) => {
    const domainsURI = values.domains.map(d => '/api/domains/' + d.toLowerCase())

    return {
        name: values.name,
        domains: domainsURI,
        connector: values.connector !== undefined ? ('/api/connectors/' + values.connector) : undefined,
        dsn: values.dsn
    }
}

export default function WatchlistPage() {
    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [watchlists, setWatchlists] = useState<Watchlist[]>()
    const [connectors, setConnectors] = useState<Array<Connector & { id: string }>>()

    const onCreateWatchlist = (values: FormValuesType) => {
        postWatchlist(getRequestDataFromFormCreation(values)).then(() => {
            form.resetFields()
            refreshWatchlists()
            messageApi.success(t`Watchlist created !`)
        }).catch((e: AxiosError) => {
            showErrorAPI(e, messageApi)
        })
    }

    const onUpdateWatchlist = async (values: FormValuesType & { token: string }) => await putWatchlist({
            token: values.token,
            ...getRequestDataFromFormUpdate(values)
        }
    ).then(() => {
        refreshWatchlists()
        messageApi.success(t`Watchlist updated !`)
    }).catch((e: AxiosError) => {
        throw showErrorAPI(e, messageApi)
    })

    const refreshWatchlists = async () => await getWatchlists().then(w => {
        setWatchlists(w['hydra:member'])
    }).catch((e: AxiosError) => {
        setWatchlists(undefined)
        showErrorAPI(e, messageApi)
    })

    useEffect(() => {
        refreshWatchlists()
        getConnectors()
            .then(c => setConnectors(c['hydra:member']))
            .catch((e: AxiosError) => {
                showErrorAPI(e, messageApi)
            })
    }, [])

    return (
        <Flex gap='middle' align='center' justify='center' vertical>
            {contextHolder}
            <Card size='small' loading={connectors === undefined} title={t`Create a Watchlist`} style={{width: '100%'}}>
                {(connectors != null) &&
                    <WatchlistForm form={form} onFinish={onCreateWatchlist} connectors={connectors} isCreation/>}
            </Card>
            <Divider/>
            {(connectors != null) && (watchlists != null) && watchlists.length > 0 &&
                <WatchlistsList
                    watchlists={watchlists}
                    onDelete={refreshWatchlists}
                    connectors={connectors}
                    onUpdateWatchlist={onUpdateWatchlist}
                />}
        </Flex>
    )
}
