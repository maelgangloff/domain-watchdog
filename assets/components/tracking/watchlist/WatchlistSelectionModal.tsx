import React, {useEffect, useState} from "react"
import type {ModalProps} from "antd"
import {Tag, Tooltip} from "antd"
import {Flex, Modal, Select, Typography} from "antd"
import type {Domain, Watchlist} from "../../../utils/api"
import {getWatchlists} from "../../../utils/api"
import {t} from 'ttag'
import {DomainToTag} from "../../../utils/functions/DomainToTag"
import {EllipsisOutlined} from '@ant-design/icons'

const MAX_DOMAIN_TAGS = 25

function WatchlistOption({watchlist}: {watchlist: Watchlist}) {
    let domains = watchlist.domains
    let rest: Domain[]|undefined = undefined

    if (domains.length > MAX_DOMAIN_TAGS) {
        rest = domains.slice(MAX_DOMAIN_TAGS)
        domains = domains.slice(0, MAX_DOMAIN_TAGS)
    }

    return <Flex vertical>
        <Typography.Text strong>{watchlist.name}</Typography.Text>
        <Flex wrap gap='4px'>
            {domains.map(d => <DomainToTag link={false} domain={d} key={d.ldhName} />)}
            {rest
                && <Tooltip title={rest.map(d => <DomainToTag link={false} domain={d} key={d.ldhName} />)}>
                    <Tag icon={<EllipsisOutlined/>} color='processing'>
                        {t`${rest.length} more`}
                    </Tag>
                </Tooltip>
            }
        </Flex>
    </Flex>
}

interface WatchlistSelectionModalProps {
    onFinish: (watchlist: Watchlist) => Promise<void>|void
    description?: string
    open?: boolean
    modalProps?: Partial<ModalProps>
}

export default function WatchlistSelectionModal(props: WatchlistSelectionModalProps) {
    const [watchlists, setWatchlists] = useState<Watchlist[] | undefined>()
    const [selectedWatchlist, setSelectedWatchlist] = useState<Watchlist | undefined>()
    const [validationLoading, setValidationLoading] = useState(false)

    useEffect(() => {
        if (props.open && !watchlists) {
            getWatchlists().then(list => setWatchlists(list["hydra:member"]))
        }
    }, [props.open])

    const onFinish = () => {
        const promise = props.onFinish(selectedWatchlist as Watchlist)

        if (promise) {
            setValidationLoading(true)
            promise.finally(() => {
                setSelectedWatchlist(undefined)
                setValidationLoading(false)
            })
        } else {
            setSelectedWatchlist(undefined)
        }
    }

    return <Modal
        open={props.open}
        onOk={onFinish}
        okButtonProps={{
            disabled: !selectedWatchlist,
            loading: validationLoading,
        }}
        {...props.modalProps ?? {}}
    >
        <Flex vertical>
            <Typography.Paragraph>
                {
                    props.description
                    || t`Select one of your available watchlists`
                }
            </Typography.Paragraph>
            <Select
                placeholder={t`Watchlist`}
                style={{width: '100%'}}
                onChange={(_, option) => setSelectedWatchlist(option as Watchlist)}
                options={watchlists}
                value={selectedWatchlist?.token}
                fieldNames={{
                    label: 'name',
                    value: 'token',
                }}
                loading={!watchlists}
                status={selectedWatchlist ? '' : 'error'}
                optionRender={(watchlist) => <WatchlistOption watchlist={watchlist.data}/>}
            />
        </Flex>
    </Modal>
}
