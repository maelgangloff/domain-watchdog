import React, {useEffect, useState} from 'react'
import {Card, Divider, Table, Typography} from 'antd'
import type {IcannAccreditation} from '../../utils/api'
import {t} from 'ttag'
import type {ColumnType} from 'antd/es/table'
import {CheckCircleOutlined, SettingOutlined, CloseCircleOutlined} from "@ant-design/icons"
import {getIcannAccreditations} from "../../utils/api/icann-accreditations"

const {Text, Paragraph} = Typography

interface FiltersType {
    status: 'Accredited' | 'Reserved' | 'Terminated',
}

function RegistrarListTable(filters: FiltersType) {
    interface TableRow {
        key: number
        handle: number
        name: string
    }

    const [dataTable, setDataTable] = useState<TableRow[]>([])
    const [total, setTotal] = useState(0)

    const fetchData = (params: FiltersType & { page: number, itemsPerPage: number }) => {
        getIcannAccreditations(params).then((data) => {
            setTotal(data['hydra:totalItems'])
            setDataTable(data['hydra:member'].map((accreditation: IcannAccreditation) => ({
                    key: accreditation.id,
                    handle: accreditation.id,
                    name: accreditation.registrarName
                })
            ).sort((a, b) => a.handle - b.handle))
        })
    }

    useEffect(() => {
        fetchData({...filters, page: 1, itemsPerPage: 30})
    }, [])

    const columns: Array<ColumnType<TableRow>> = [
        {
            title: t`ID`,
            dataIndex: 'handle',
            width: '10vh',
            align: 'center'
        },
        {
            title: t`Registrar`,
            dataIndex: 'name'
        }
    ]

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

export default function IcannRegistrarPage() {
    const [activeTabKey, setActiveTabKey] = useState<string>('Accredited')

    const contentList: Record<string, React.ReactNode> = {
        Accredited: <>
            <Text>{t`An accredited number means that ICANN's contract with the registrar is ongoing.`}</Text>
            <Divider/>
            <RegistrarListTable status='Accredited' />
        </>,
        Reserved: <>
            <Text>{t`A reserved number can be used by TLD registries for specific operations.`}</Text>
            <Divider/>
            <RegistrarListTable status='Reserved' />
        </>,
        Terminated: <>
            <Text>{t`A terminated number means that ICANN's contract with the registrar has been terminated.`}</Text>
            <Divider/>
            <RegistrarListTable status='Terminated' />
        </>
    }

    return (
        <>
            <Paragraph>
                {t`This page lists ICANN-accredited registrars.`}
            </Paragraph>
            <Paragraph>
                {t`The list is officially published and maintained by the Internet Assigned Numbers Authority (IANA), the organization responsible for managing the Internet's unique identifiers (including numbers, IP addresses, and domain name extensions).`}
            </Paragraph>
            <Divider/>

            <Card
                style={{width: '100%'}}
                tabProps={{
                    size: 'middle',
                }}
                tabList={[
                    {
                        key: 'Accredited',
                        label: t`Accredited`,
                        icon: <CheckCircleOutlined/>
                    },
                    {
                        key: 'Reserved',
                        label: t`Reserved`,
                        icon: <SettingOutlined/>
                    },
                    {
                        key: 'Terminated',
                        label: t`Terminated`,
                        icon: <CloseCircleOutlined />
                    }
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
