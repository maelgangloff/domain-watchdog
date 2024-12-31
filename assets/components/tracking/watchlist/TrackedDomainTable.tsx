import type {ReactElement} from 'react'
import React, { useEffect, useState} from 'react'
import type {Domain} from '../../../utils/api'
import { getTrackedDomainList} from '../../../utils/api'
import {Button, Empty, Result, Skeleton, Table, Tag, Tooltip} from 'antd'
import {t} from 'ttag'
import type {ColumnType} from 'antd/es/table'
import {rdapStatusCodeDetailTranslation} from '../../../utils/functions/rdapTranslation'
import {eppStatusCodeToColor} from '../../../utils/functions/eppStatusCodeToColor'
import {Link} from 'react-router-dom'
import {ExceptionOutlined, MonitorOutlined} from '@ant-design/icons'
import {DomainToTag} from '../DomainToTag'

export function TrackedDomainTable() {
    const REDEMPTION_NOTICE = (
        <Tooltip
            title={t`At least one domain name is in redemption period and will potentially be deleted soon`}
        >
            <Tag color={eppStatusCodeToColor('redemption period')}>redemption period</Tag>
        </Tooltip>
    )

    const PENDING_DELETE_NOTICE = (
        <Tooltip
            title={t`At least one domain name is pending deletion and will soon become available for registration again`}
        >
            <Tag color={eppStatusCodeToColor('pending delete')}>pending delete</Tag>
        </Tooltip>
    )

    interface TableRow {
        key: string
        ldhName: ReactElement
        expirationDate: string
        status: ReactElement[]
        updatedAt: string
        domain: Domain
    }

    const [dataTable, setDataTable] = useState<TableRow[]>([])
    const [total, setTotal] = useState<number>()
    const [specialNotice, setSpecialNotice] = useState<ReactElement[]>([])

    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()

    const fetchData = (params: { page: number, itemsPerPage: number }) => {
        getTrackedDomainList(params).then(data => {
            setTotal(data['hydra:totalItems'])

            const notices: ReactElement[] = []
            setDataTable(data['hydra:member'].map((d: Domain) => {
                const expirationDate = d.events.find(e => e.action === 'expiration' && !e.deleted)?.date

                if (d.status.includes('redemption period')) {
                    if (!notices.includes(REDEMPTION_NOTICE)) notices.push(REDEMPTION_NOTICE)
                } else if (d.status.includes('pending delete')) {
                    if (!notices.includes(PENDING_DELETE_NOTICE)) notices.push(PENDING_DELETE_NOTICE)
                }

                return {
                    key: d.ldhName,
                    ldhName: <DomainToTag domain={d}/>,
                    expirationDate: expirationDate ? new Date(expirationDate).toLocaleString() : '-',
                    status: d.status.map(s => <Tooltip
                            key={s}
                            placement='bottomLeft'
                            title={rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] || undefined}
                        >
                            <Tag color={eppStatusCodeToColor(s)}>{s}</Tag>
                        </Tooltip>
                    ),
                    updatedAt: new Date(d.updatedAt).toLocaleString(),
                    domain: d
                }
            }))
            setSpecialNotice(notices)
        })
    }

    useEffect(() => {
        fetchData({page: 1, itemsPerPage: 30})
    }, [])

    interface RecordType {
        domain: Domain
    }

    const columns: Array<ColumnType<RecordType>> = [
        {
            title: t`Domain`,
            dataIndex: 'ldhName'
        },
        {
            title: t`Expiration date`,
            dataIndex: 'expirationDate',
            sorter: (a: RecordType, b: RecordType) => {
                const expirationDate1 = a.domain.events.find(e => e.action === 'expiration' && !e.deleted)?.date
                const expirationDate2 = b.domain.events.find(e => e.action === 'expiration' && !e.deleted)?.date

                if (expirationDate1 === undefined || expirationDate2 === undefined) return 0
                return new Date(expirationDate1).getTime() - new Date(expirationDate2).getTime()
            }
        },

        {
            title: t`Updated at`,
            dataIndex: 'updatedAt',
            sorter: (a: RecordType, b: RecordType) => new Date(a.domain.updatedAt).getTime() - new Date(b.domain.updatedAt).getTime()
        },
        {
            title: t`Status`,
            dataIndex: 'status',
            showSorterTooltip: {target: 'full-header'},
            filters: [...new Set(dataTable.map((d: RecordType) => d.domain.status).flat())].map(s => ({
                text: <Tooltip
                    placement='bottomLeft'
                    title={rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] || undefined}
                >
                    <Tag color={eppStatusCodeToColor(s)}>{s}</Tag>
                </Tooltip>,
                value: s
            })),
            onFilter: (value, record: RecordType) => record.domain.status.includes(value as string)
        }
    ]

    return (
        <>
            {
                total === 0
                    ? <Empty
                        description={t`No tracked domain names were found, please create your first Watchlist`}
                    >
                        <Link to='/tracking/watchlist'>
                            <Button type='primary'>Create Now</Button>
                        </Link>
                    </Empty>
                    : <Skeleton loading={total === undefined}>
                        <Result
                            style={{paddingTop: 0}}
                            subTitle={t`Please note that this table does not include domain names marked as expired or those with an unknown expiration date`}
                            {...(specialNotice.length > 0
                                ? {
                                    icon: <ExceptionOutlined/>,
                                    status: 'warning',
                                    title: t`At least one domain name you are tracking requires special attention`,
                                    extra: specialNotice
                                }
                                : {
                                    icon: <MonitorOutlined/>,
                                    status: 'info',
                                    title: t`The domain names below are subject to special monitoring`
                                })}
                        />

                        <Table
                            loading={total === undefined}
                            columns={columns}
                            dataSource={dataTable}
                            pagination={{
                                total,
                                hideOnSinglePage: true,
                                defaultPageSize: 30,
                                onChange: (page, itemsPerPage) => {
                                    fetchData({page, itemsPerPage})
                                }
                            }}
                            scroll={{y: '50vh'}}
                        />
                    </Skeleton>
            }
        </>
    )
}
