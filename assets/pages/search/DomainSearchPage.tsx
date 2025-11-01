import React, {useEffect, useState} from 'react'
import type {FormProps} from 'antd'
import {FloatButton} from 'antd'
import {Empty, Flex, message, Skeleton} from 'antd'
import type { Domain, Watchlist} from '../../utils/api'
import {addDomainToWatchlist} from '../../utils/api'
import {getDomain} from '../../utils/api'
import type {AxiosError} from 'axios'
import {t} from 'ttag'
import type { FieldType} from '../../components/search/DomainSearchBar'
import {DomainSearchBar} from '../../components/search/DomainSearchBar'
import {DomainResult} from '../../components/search/DomainResult'
import {showErrorAPI} from '../../utils/functions/showErrorAPI'
import {useNavigate, useParams} from 'react-router-dom'
import {PlusOutlined} from '@ant-design/icons'
import WatchlistSelectionModal from '../../components/tracking/watchlist/WatchlistSelectionModal'

export default function DomainSearchPage() {
    const {query} = useParams()
    const [domain, setDomain] = useState<Domain | null>()
    const [loading, setLoading] = useState(false)
    const [addToWatchlistModal, setAddToWatchlistModal] = useState(false)

    const [messageApi, contextHolder] = message.useMessage()
    const navigate = useNavigate()

    const onFinish: FormProps<FieldType>['onFinish'] = (values) => {
        navigate('/search/domain/' + values.ldhName)

        if (loading) return
        setLoading(true)
        setDomain(null)
        getDomain(values.ldhName, values.isRefreshForced).then(d => {
            setDomain(d)
            messageApi.success(t`Found !`)
        }).catch((e: AxiosError) => {
            setDomain(undefined)
            showErrorAPI(e, messageApi)
        }).finally(() => setLoading(false))
    }

    useEffect(() => {
        if (query === undefined) return
        onFinish({ldhName: query, isRefreshForced: false})
    }, [])

    const addToWatchlist = async (watchlist: Watchlist) => {
        await addDomainToWatchlist(watchlist, domain!.ldhName).then(() => {
            setAddToWatchlistModal(false)
            messageApi.success(t`${domain?.ldhName} added to ${watchlist.name}`)
        }).catch((e: AxiosError) => {
            showErrorAPI(e, messageApi)
        })
    }

    return <>
        <Flex gap='middle' align='center' justify='center' vertical>
            {contextHolder}
            <DomainSearchBar initialValue={query} onFinish={onFinish}/>

            <Skeleton loading={domain === null} active>
                {
                    (domain != null) &&
                    (!domain.deleted
                        ? <DomainResult domain={domain}/>
                        : <Empty
                            description={t`Although the domain exists in my database, it has been deleted from the WHOIS by its registrar.`}
                        />)
                }
            </Skeleton>
        </Flex>
        {domain
            && <FloatButton
                style={{
                    position: 'fixed',
                    insetInlineEnd: (100 - 40) / 2,
                    bottom: 100 - 40 / 2
                }}
                tooltip={t`Add to watchlist`}
                type="primary"
                icon={<PlusOutlined/>}
                onClick={() => setAddToWatchlistModal(true)}
            />
        }
        <WatchlistSelectionModal
            open={addToWatchlistModal}
            onFinish={addToWatchlist}
            modalProps={{
                title: t`Add ${domain?.ldhName} to a watchlist`,
                onCancel: () => setAddToWatchlistModal(false),
                onClose: () => setAddToWatchlistModal(false),
            }}
        />
    </>
}
