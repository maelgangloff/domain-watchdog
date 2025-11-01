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
    CheckOutlined,
    DeleteOutlined,
    ExceptionOutlined,
    ExclamationCircleOutlined,
    ExclamationOutlined,
    FieldTimeOutlined,
    KeyOutlined,
    MonitorOutlined,
    SafetyCertificateOutlined
} from '@ant-design/icons'
import {DomainToTag} from '../../../utils/functions/DomainToTag'
import {isDomainLocked} from "../../../utils/functions/isDomainLocked"
import useBreakpoint from "../../../hooks/useBreakpoint"

export function TrackedDomainTable() {
    const REDEMPTION_NOTICE = (
        <Tooltip
            title={t`At least one domain name is in redemption period and will potentially be deleted soon`}
            key="redeptionNotice"
        >
            <Tag color={eppStatusCodeToColor('redemption period')}>redemption period</Tag>
        </Tooltip>
    )

    const PENDING_DELETE_NOTICE = (
        <Tooltip
            title={t`At least one domain name is pending deletion and will soon become available for registration again`}
            key="pendingDeleteNotice"
        >
            <Tag color={eppStatusCodeToColor('pending delete')}>pending delete</Tag>
        </Tooltip>
    )

    interface TableRow {
        key: string
        ldhName: ReactElement
        expirationDate: string
        status: ReactElement[]
        state: ReactElement
        updatedAt: string
        rawDomain: Domain
    }

    const [dataTable, setDataTable] = useState<TableRow[]>([])
    const [total, setTotal] = useState<number>()
    const [specialNotice, setSpecialNotice] = useState<ReactElement[]>([])
    const sm = useBreakpoint('sm')

    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()

    const fetchData = (params: { page: number, itemsPerPage: number }) => {
        getTrackedDomainList(params).then(data => {
            setTotal(data['hydra:totalItems'])

            const notices: ReactElement[] = []
            setDataTable(data['hydra:member'].map((d: Domain) => {
                const expirationDate = d.events.find(e => e.action === 'expiration' && !e.deleted)?.date
                const expiresInDays = d.expiresInDays !== undefined && d.expiresInDays > 0 ? -d.expiresInDays : undefined

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
                    options: <Flex wrap justify='space-evenly' align='center' gap='4px 0'>
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
                    </Flex>,
                    state: <Flex wrap justify='space-evenly' align='center' gap='4px 0'>
                        {
                            d.status.includes('auto renew period') ?
                                <Tooltip title={t`Auto-Renew Grace Period`}>
                                    <Tag
                                        bordered={false}
                                        color='palevioletred'
                                        icon={<FieldTimeOutlined/>}
                                    />
                                </Tooltip> :
                                d.status.includes('redemption period') ?
                                    <Tooltip title={t`Redemption Grace Period`}>
                                        <Tag
                                            bordered={false}
                                            color='magenta'
                                            icon={<ExclamationCircleOutlined/>}
                                        />
                                    </Tooltip> :
                                    !d.status.includes('redemption period') && d.status.includes('pending delete') ?
                                        <Tooltip title={t`Pending Delete`}>
                                            <Tag
                                                bordered={false}
                                                color='orangered'
                                                icon={<DeleteOutlined/>}
                                            />
                                        </Tooltip> : <Tooltip title={t`Active`}>
                                            <Tag
                                                bordered={false}
                                                color='green'
                                                icon={<CheckOutlined/>}
                                            />
                                        </Tooltip>
                        }
                        {
                            expiresInDays !== undefined ?
                                <Tooltip title={t`Estimated number of days until WHOIS removal`}>
                                    <Tag bordered={false}
                                         color={expiresInDays >= -5 ? 'red' : expiresInDays >= -35 ? 'orange' : 'default'}>
                                        {t`J ${expiresInDays}`}
                                    </Tag>
                                </Tooltip> : undefined
                        }
                        {
                            d.expiresInDays !== undefined && d.expiresInDays <= 0 ?
                                <Tooltip title={t`Deletion is imminent`}>
                                    <Tag bordered={false} color='red'>
                                        <ExclamationOutlined/>
                                    </Tag>
                                </Tooltip> : undefined
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
            width: '20%',
            align: 'left'
        },
        {
            title: t`Status`,
            dataIndex: 'state',
            width: '10%',
            align: 'center'
        },
        {
            title: t`Options`,
            dataIndex: 'options',
            width: '10%',
            align: 'center',
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
            width: '15%',
            align: 'center'
        },

        {
            title: t`Updated at`,
            dataIndex: 'updatedAt',
            responsive: ['md'],
            sorter: (a: RecordType, b: RecordType) => new Date(a.rawDomain.updatedAt).getTime() - new Date(b.rawDomain.updatedAt).getTime(),
            width: '15%',
            align: 'center'
        },
        {
            title: t`EPP Status Codes`,
            dataIndex: 'status',
            responsive: ['md'],
            showSorterTooltip: {target: 'full-header'},
            filters: [...new Set(dataTable.map((d: RecordType) => d.rawDomain.status).flat())].map(s => ({
                text: <Tooltip
                    placement='bottomLeft'
                    title={rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] || undefined}
                    key={s}
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
                <Button type='primary'>{t`Create now`}</Button>
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
                scroll={sm ? {} : {y: '50vh'}}
                size={sm ? 'small' : 'large'}
            />
        </Skeleton>
}
