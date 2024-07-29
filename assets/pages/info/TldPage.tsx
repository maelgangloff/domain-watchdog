import React, {useEffect, useState} from "react";
import {Collapse, Divider, Table, Typography} from "antd";
import {getTldList, Tld} from "../../utils/api";
import {t} from 'ttag'
import {regionNames} from "../../i18n";

const {Text, Paragraph} = Typography

type TldType = 'iTLD' | 'sTLD' | 'gTLD' | 'ccTLD'
type FiltersType = { type: TldType, contractTerminated?: boolean, specification13?: boolean }


const toEmoji = (tld: string) => String.fromCodePoint(
    ...getCountryCode(tld)
        .toUpperCase()
        .split('')
        .map((char) => 127397 + char.charCodeAt(0)
        )
)

const getCountryCode = (tld: string): string => {
    const exceptions = {uk: 'gb', su: 'ru', tp: 'tl'}
    if (tld in exceptions) return exceptions[tld as keyof typeof exceptions]
    return tld
}

function TldTable(filters: FiltersType) {
    const [dataTable, setDataTable] = useState<Tld[]>([])
    const [total, setTotal] = useState(0)

    const fetchData = (params: FiltersType & { page: number }) => {
        getTldList(params).then((data) => {
            setTotal(data['hydra:totalItems'])
            setDataTable(data['hydra:member'].map((tld: Tld) => {
                switch (filters.type) {
                    case 'ccTLD':
                        return {
                            TLD: tld.tld,
                            Flag: toEmoji(tld.tld),
                            Country: regionNames.of(getCountryCode(tld.tld)) ?? '-'
                        }
                    case 'gTLD':
                        return {
                            TLD: tld.tld,
                            Operator: tld.registryOperator
                        }
                    default:
                        return {
                            TLD: tld.tld
                        }
                }
            }))
        })
    }

    useEffect(() => {
        fetchData({...filters, page: 1})
    }, [])

    let columns = [
        {
            title: t`TLD`,
            dataIndex: "TLD",
            width: 20
        }
    ]

    if (filters.type === 'ccTLD') columns = [...columns, {
        title: t`Flag`,
        dataIndex: "Flag",
        width: 20
    }, {
        title: t`Country`,
        dataIndex: "Country",
        width: 200
    }]

    if (filters.type === 'gTLD') columns = [...columns, {
        title: t`Registry Operator`,
        dataIndex: "Operator",
        width: 50
    }]


    return <Table
        columns={columns}
        dataSource={dataTable}
        pagination={{
            total,
            pageSize: 30,
            hideOnSinglePage: true,
            onChange: (page, pageSize) => {
                fetchData({...filters, page})
            }
        }}
        scroll={{y: 240}}
    />
}


export default function TldPage() {
    return <>
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
        <Collapse
            size="large"
            items={[
                {
                    key: 'sTLD',
                    label: t`Sponsored Top-Level-Domains`,
                    children: <>
                        <Text>{t`Top-level domains sponsored by specific organizations that set rules for registration and use, often related to particular interest groups or industries.`}</Text>
                        <Divider/>
                        <TldTable type='sTLD'/>
                    </>
                },
                {
                    key: 'gTLD',
                    label: t`Generic Top-Level-Domains`,
                    children: <>
                        <Text>{t`Generic top-level domains open to everyone, not restricted by specific criteria, representing various themes or industries.`}</Text>
                        <Divider/>
                        <TldTable type='gTLD' contractTerminated={false} specification13={false}/>
                    </>
                },
                {
                    key: 'ngTLD',
                    label: t`Brand Generic Top-Level-Domains`,
                    children: <>
                        <Text>{t`Generic top-level domains associated with specific brands, allowing companies to use their own brand names as domains.`}</Text>
                        <Divider/>
                        <TldTable type='gTLD' contractTerminated={false} specification13={true}/>
                    </>
                },
                {
                    key: 'ccTLD',
                    label: t`Country-Code Top-Level-Domains`,
                    children: <>
                        <Text>{t`Top-level domains based on country codes, identifying websites according to their country of origin.`}</Text>
                        <Divider/><TldTable type='ccTLD'/>
                    </>
                }
            ]}
        />
    </>
}