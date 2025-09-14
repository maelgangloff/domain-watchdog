import type {ReactElement} from 'react'
import React, {useEffect, useState} from 'react'
import {Card, Divider, Table, Typography} from 'antd'
import type {Tld} from '../../utils/api'
import {getTldList} from '../../utils/api'
import {t} from 'ttag'
import {regionNames} from '../../i18n'
import type {ColumnType} from 'antd/es/table'
import punycode from 'punycode/punycode'
import {getCountryCode} from '../../utils/functions/getCountryCode'
import {tldToEmoji} from '../../utils/functions/tldToEmoji'
import {BankOutlined, FlagOutlined, GlobalOutlined, TrademarkOutlined} from "@ant-design/icons"
import {Link} from "react-router-dom"

const {Text, Paragraph} = Typography

type TldType = 'iTLD' | 'sTLD' | 'gTLD' | 'ccTLD'

interface FiltersType {
    type: TldType,
    contractTerminated?: boolean,
    specification13?: boolean
}

function TldTable(filters: FiltersType) {
    interface TableRow {
        key: string
        TLD: ReactElement
        Flag?: string
        Country?: string
    }

    const [dataTable, setDataTable] = useState<TableRow[]>([])
    const [total, setTotal] = useState(0)

    const fetchData = (params: FiltersType & { page: number, itemsPerPage: number }) => {
        getTldList(params).then((data) => {
            setTotal(data['hydra:totalItems'])
            setDataTable(data['hydra:member'].map((tld: Tld) => {
                const rowData = {
                    key: tld.tld,
                    TLD: <Link to={'/search/domain/' + tld.tld}><Typography.Text code>{punycode.toUnicode(tld.tld)}</Typography.Text></Link>
                }
                const type = filters.type
                let countryName

                switch (type) {
                    case 'ccTLD':

                        try {
                            countryName = regionNames.of(getCountryCode(tld.tld))
                        } catch {
                            countryName = '-'
                        }

                        return {
                            ...rowData,
                            Flag: tldToEmoji(tld.tld),
                            Country: countryName
                        }
                    case 'gTLD':
                        return {
                            ...rowData,
                            Operator: tld.registryOperator
                        }
                    default:
                        return rowData
                }
            }))
        })
    }

    useEffect(() => {
        fetchData({...filters, page: 1, itemsPerPage: 30})
    }, [])

    let columns: Array<ColumnType<TableRow>> = [
        {
            title: t`TLD`,
            dataIndex: 'TLD'
        }
    ]

    if (filters.type === 'ccTLD') {
        columns = [...columns, {
            title: t`Flag`,
            dataIndex: 'Flag'
        }, {
            title: t`Country`,
            dataIndex: 'Country'
        }]
    }

    if (filters.type === 'gTLD') {
        columns = [...columns, {
            title: t`Registry Operator`,
            dataIndex: 'Operator'
        }]
    }

    return (
        <Table
            columns={columns}
            dataSource={dataTable}
            pagination={{
                total,
                hideOnSinglePage: true,
                defaultPageSize: 30,
                onChange: (page, itemsPerPage) => {
                    fetchData({...filters, page, itemsPerPage})
                }
            }}

            scroll={{y: '50vh'}}
        />
    )
}

export default function TldPage() {
    const [activeTabKey, setActiveTabKey] = useState<string>('gTLD')

    const contentList: Record<string, React.ReactNode> = {
        sTLD: <>
            <Text>{t`Top-level domains sponsored by specific organizations that set rules for registration and use, often related to particular interest groups or industries.`}</Text>
            <Divider/>
            <TldTable type='sTLD'/>
        </>,
        gTLD: <>
            <Text>{t`Generic top-level domains open to everyone, not restricted by specific criteria, representing various themes or industries.`}</Text>
            <Divider/>
            <TldTable type='gTLD' contractTerminated={false} specification13={false}/>
        </>,
        ngTLD: <>
            <Text>{t`Generic top-level domains associated with specific brands, allowing companies to use their own brand names as domains.`}</Text>
            <Divider/>
            <TldTable type='gTLD' contractTerminated={false} specification13/>
        </>,
        ccTLD: <>
            <Text>{t`Top-level domains based on country codes, identifying websites according to their country of origin.`}</Text>
            <Divider/>
            <TldTable type='ccTLD'/>
        </>
    }

    return (
        <>
            <Paragraph>
                {t`This page presents all active TLDs in the root zone database.`}
            </Paragraph>
            <Paragraph>
                {t`IANA provides the list of currently active TLDs, regardless of their type, and ICANN provides the list of gTLDs.
            In most cases, the two-letter ccTLD assigned to a country is made in accordance with the ISO 3166-1 standard.
            This data is updated every month. Three HTTP requests are needed for the complete update of TLDs in Domain Watchdog (two requests to IANA and one to ICANN).
            At the same time, the list of root RDAP servers is updated.`}
            </Paragraph>
            <Divider/>

            <Card
                style={{width: '100%'}}
                tabProps={{
                    size: 'middle',
                }}
                tabList={[
                    {
                        key: 'gTLD',
                        label: t`Generic Top-Level-Domains`,
                        icon: <GlobalOutlined/>
                    },
                    {
                        key: 'ccTLD',
                        label: t`Country-Code Top-Level-Domains`,
                        icon: <FlagOutlined/>
                    },
                    {
                        key: 'ngTLD',
                        label: t`Brand Generic Top-Level-Domains`,
                        icon: <TrademarkOutlined/>
                    },
                    {
                        key: 'sTLD',
                        label: t`Sponsored Top-Level-Domains`,
                        icon: <BankOutlined/>
                    },
                ]}
                activeTabKey={activeTabKey}
                key={activeTabKey}
                onTabChange={(k: string) => setActiveTabKey(k)}
            >
                {contentList[activeTabKey]}

            </Card>
        </>
    )
}
