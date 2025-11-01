import React, {useEffect, useState} from "react"
import {Flex, Modal, ModalProps, Select, Tag, Typography} from "antd"
import {getWatchlists, Watchlist} from "../../../utils/api"
import {t} from 'ttag'
import {DomainToTag} from "../../../utils/functions/DomainToTag"

function WatchlistOption({watchlist}: {watchlist: Watchlist}) {
    return <Flex vertical>
        <Typography.Text strong>{watchlist.name}</Typography.Text>
        <Flex wrap>
            {watchlist.domains.map(d => <DomainToTag link={false} domain={d} key={d.ldhName} />)}
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
        getWatchlists().then(list => setWatchlists(list["hydra:member"]))
    }, [])

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
