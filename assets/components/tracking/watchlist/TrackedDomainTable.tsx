import type {ReactElement} from 'react'
import React, {useEffect, useState} from 'react'
import type {Domain} from '../../../utils/api'
import {getTrackedDomainList} from '../../../utils/api'
import {Button, Empty, Flex, Result, Skeleton, Table, Tag, Tooltip} from 'antd'
import {t} from 'ttag'
import type {ColumnType} from 'antd/es/table'
import {rdapStatusCodeDetailTranslation} from '../../../utils/functions/rdapTranslation'
import {eppStatusCodeToColor} from '../../../utils/functions/eppStatusCodeToColor'
import {Link} from 'react-router-dom'
import {
    BankOutlined,
    ExceptionOutlined,
    KeyOutlined,
    MonitorOutlined,
    SafetyCertificateOutlined
} from '@ant-design/icons'
import {DomainToTag} from '../../../utils/functions/DomainToTag'
import {isDomainLocked} from "../../../utils/functions/isDomainLocked"

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
        rawDomain: Domain
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
                    rawDomain: d,
                    options: <Flex gap='4px 0' wrap>
                        <Tooltip title={t`Registry Lock`}>
                            <Tag
                                bordered={false} color={isDomainLocked(d.status, 'server') ? 'green' : 'default'}
                                icon={<SafetyCertificateOutlined/>}
                            />
                        </Tooltip>
                        <Tooltip title={t`Registrar Lock`}>
                            <Tag
                                bordered={false} color={isDomainLocked(d.status, 'client') ? 'green' : 'default'}
                                icon={<BankOutlined/>}
                            />
                        </Tooltip>
                        <Tooltip title={t`DNSSEC`}>
                            <Tag
                                bordered={false} color={d.delegationSigned ? 'green' : 'default'}
                                icon={<KeyOutlined/>}
                            />
                        </Tooltip>
                        {
                            d.expiresInDays && <Tooltip title={t`Estimated number of days until release`}>
                                <Tag bordered={false}
                                     color={d.expiresInDays <= 5 ? 'red' : d.expiresInDays <= 35 ? 'orange' : 'default'}>
                                    {t`J ${d.expiresInDays}`}
                                </Tag>
                            </Tooltip>
                        }
                    </Flex>
                }
            }))
            setSpecialNotice(notices)
        })
    }

    useEffect(() => {
        fetchData({page: 1, itemsPerPage: 30})
    }, [])

    interface RecordType {
        rawDomain: Domain
    }

    const columns: Array<ColumnType<RecordType>> = [
        {
            title: t`Domain`,
            dataIndex: 'ldhName',
            width: '25%',
            align: 'left'
        },
        {
            title: t`Options`,
            dataIndex: 'options',
            width: '15%',
        },
        {
            title: t`Expiration date`,
            dataIndex: 'expirationDate',
            sorter: (a: RecordType, b: RecordType) => {
                const expirationDate1 = a.rawDomain.events.find(e => e.action === 'expiration' && !e.deleted)?.date
                const expirationDate2 = b.rawDomain.events.find(e => e.action === 'expiration' && !e.deleted)?.date

                if (expirationDate1 === undefined || expirationDate2 === undefined) return 0
                return new Date(expirationDate1).getTime() - new Date(expirationDate2).getTime()
            },
            width: '15%'
        },

        {
            title: t`Updated at`,
            dataIndex: 'updatedAt',
            responsive: ['md'],
            sorter: (a: RecordType, b: RecordType) => new Date(a.rawDomain.updatedAt).getTime() - new Date(b.rawDomain.updatedAt).getTime(),
            width: '15%'
        },
        {
            title: t`Status`,
            dataIndex: 'status',
            responsive: ['md'],
            showSorterTooltip: {target: 'full-header'},
            filters: [...new Set(dataTable.map((d: RecordType) => d.rawDomain.status).flat())].map(s => ({
                text: <Tooltip
                    placement='bottomLeft'
                    title={rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] || undefined}
                >
                    <Tag color={eppStatusCodeToColor(s)}>{s}</Tag>
                </Tooltip>,
                value: s
            })),
            onFilter: (value, record: RecordType) => record.rawDomain.status.includes(value as string),
            width: '30%'
        }
    ]

    return total === 0
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
