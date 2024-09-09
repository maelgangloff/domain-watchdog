import React, {useEffect, useState} from "react";
import {Domain, getTrackedDomainList} from "../../../utils/api";
import {Table, Tag, Tooltip} from "antd";
import {t} from "ttag";
import useBreakpoint from "../../../hooks/useBreakpoint";
import {ColumnType} from "antd/es/table";
import {rdapStatusCodeDetailTranslation} from "../../../utils/functions/rdapTranslation";
import {eppStatusCodeToColor} from "../../../utils/functions/eppStatusCodeToColor";

export function TrackedDomainTable() {
    const sm = useBreakpoint('sm')
    const [dataTable, setDataTable] = useState<Domain[]>([])
    const [total, setTotal] = useState()


    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()

    const fetchData = (params: { page: number, itemsPerPage: number }) => {
        getTrackedDomainList(params).then(data => {
            setTotal(data['hydra:totalItems'])
            setDataTable(data['hydra:member'].map((d: Domain) => {
                const expirationDate = d.events.find(e => e.action === 'expiration' && !e.deleted)?.date

                return {
                    key: d.ldhName,
                    ldhName: d.ldhName,
                    expirationDate: expirationDate ? new Date(expirationDate).toLocaleString() : '-',
                    status: d.status.map(s => <Tooltip
                            placement='bottomLeft'
                            title={s in rdapStatusCodeDetailTranslated ? rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] : undefined}>
                            <Tag color={eppStatusCodeToColor(s)}>{s}</Tag>
                        </Tooltip>
                    ),
                    updatedAt: new Date(d.updatedAt).toLocaleString()
                }
            }))
        })
    }

    useEffect(() => {
        fetchData({page: 1, itemsPerPage: 30})
    }, [])

    const columns: ColumnType<any>[] = [
        {
            title: t`Domain`,
            dataIndex: "ldhName"
        },
        {
            title: t`Expiration date`,
            dataIndex: 'expirationDate'
        },
        {
            title: t`Status`,
            dataIndex: 'status'
        },
        {
            title: t`Updated at`,
            dataIndex: 'updatedAt'
        }
    ]


    return <Table
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

        {...(sm ? {scroll: {y: 'max-content'}} : {scroll: {y: 240}})}
    />
}