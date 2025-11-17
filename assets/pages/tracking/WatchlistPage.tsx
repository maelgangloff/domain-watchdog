import React, {useEffect, useState} from 'react'
import {Divider, Flex, Form, message} from 'antd'
import type {Watchlist} from '../../utils/api'
import {getWatchlists, postWatchlist, putWatchlist} from '../../utils/api'
import type {AxiosError} from 'axios'
import {t} from 'ttag'
import {WatchlistsList} from '../../components/tracking/watchlist/WatchlistsList'
import type {Connector} from '../../utils/api/connectors'
import { getConnectors} from '../../utils/api/connectors'

import {showErrorAPI} from '../../utils/functions/showErrorAPI'
import {CreateWatchlistButton} from "../../components/tracking/watchlist/CreateWatchlistButton"

interface FormValuesType {
    name?: string
    domains: string[]
    trackedEvents: string[]
    trackedEppStatus: string[]
    connector?: string
    dsn?: string[]
}

const getRequestDataFromFormCreation = (values: FormValuesType) =>
    ({        name: values.name,
        domains: values.domains.map(d => '/api/domains/' + d.toLowerCase()),
        trackedEvents: values.trackedEvents,
        trackedEppStatus: values.trackedEppStatus,
        connector: values.connector !== undefined ? ('/api/connectors/' + values.connector) : undefined,
        dsn: values.dsn,
        enabled: true
    })

export default function WatchlistPage() {
    const [form] = Form.useForm()
    const [messageApi, contextHolder] = message.useMessage()
    const [watchlists, setWatchlists] = useState<Watchlist[]>()
    const [connectors, setConnectors] = useState<Array<Connector & { id: string }>>()

    const onCreateWatchlist = async (values: FormValuesType) => await postWatchlist(getRequestDataFromFormCreation(values)).then(() => {
            form.resetFields()
            refreshWatchlists()
            messageApi.success(t`Watchlist created !`)
        }).catch((e: AxiosError) => {
            showErrorAPI(e, messageApi)
        })

    const onUpdateWatchlist = async (values: FormValuesType & { token: string }) => await putWatchlist({
            token: values.token,
            ...getRequestDataFromFormCreation(values)
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
            {(connectors !== undefined) && (watchlists !== undefined) &&
                <>
                    <CreateWatchlistButton onUpdateWatchlist={onCreateWatchlist} connectors={connectors} />
                    <Divider/>
                    <WatchlistsList
                        watchlists={watchlists}
                        onChange={refreshWatchlists}
                        connectors={connectors}
                        onUpdateWatchlist={onUpdateWatchlist}
                    />
                </>}
        </Flex>
    )
}
