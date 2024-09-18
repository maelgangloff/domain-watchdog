import React, {useEffect, useState} from "react";
import {Collapse, Divider, Table, Typography} from "antd";
import {getTldList, Tld} from "../../utils/api";
import {t} from 'ttag'
import {regionNames} from "../../i18n";
import useBreakpoint from "../../hooks/useBreakpoint";
import {ColumnType} from "antd/es/table";
import punycode from "punycode/punycode";
import {getCountryCode} from "../../utils/functions/getCountryCode";
import {tldToEmoji} from "../../utils/functions/tldToEmoji";

const {Text, Paragraph} = Typography

type TldType = 'iTLD' | 'sTLD' | 'gTLD' | 'ccTLD'
type FiltersType = { type: TldType, contractTerminated?: boolean, specification13?: boolean }


function TldTable(filters: FiltersType) {
    const sm = useBreakpoint('sm')
    const [dataTable, setDataTable] = useState<Tld[]>([])
    const [total, setTotal] = useState(0)

    const fetchData = (params: FiltersType & { page: number, itemsPerPage: number }) => {
        getTldList(params).then((data) => {
            setTotal(data['hydra:totalItems'])
            setDataTable(data['hydra:member'].map((tld: Tld) => {

                const rowData = {
                    key: tld.tld,
                    TLD: <Typography.Text code>{punycode.toUnicode(tld.tld)}</Typography.Text>
                }
                switch (filters.type) {
                    case 'ccTLD':
                        let countryName

                        try {
                            countryName = regionNames.of(getCountryCode(tld.tld))
                        } catch (e) {
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

    let columns: ColumnType<any>[] = [
        {
            title: t`TLD`,
            dataIndex: "TLD"
        }
    ]

    if (filters.type === 'ccTLD') columns = [...columns, {
        title: t`Flag`,
        dataIndex: "Flag",
    }, {
        title: t`Country`,
        dataIndex: "Country"
    }]

    if (filters.type === 'gTLD') columns = [...columns, {
        title: t`Registry Operator`,
        dataIndex: "Operator"
    }]


    return <Table
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

        {...(sm ? {scroll: {y: 'max-content'}} : {scroll: {y: 240}})}
    />
}


export default function TldPage() {
    const sm = useBreakpoint('sm')

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
            accordion
            size={sm ? 'small' : 'large'}
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