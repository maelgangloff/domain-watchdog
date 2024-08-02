import {Card, Divider, Popconfirm, Table, theme} from "antd";
import {t} from "ttag";
import {DeleteFilled} from "@ant-design/icons";
import React from "react";
import {Connector, deleteConnector} from "../../utils/api/connectors";
import useBreakpoint from "../../hooks/useBreakpoint";

const {useToken} = theme;


type ConnectorElement = Connector & { id: string }

export function ConnectorsList({connectors, onDelete}: { connectors: ConnectorElement[], onDelete: () => void }) {
    const {token} = useToken()
    const sm = useBreakpoint('sm')


    const columns = [
        {
            title: t`Provider`,
            dataIndex: 'provider'
        }
    ]

    return <>
        {connectors.map(connector =>
            <>
                <Card title={t`Connector`}
                      size='small'
                      extra={<Popconfirm title={t`Delete the Connector`}
                                         description={t`Are you sure to delete this Connector?`}
                                         onConfirm={() => deleteConnector(connector.id).then(onDelete)}
                                         okText={t`Yes`}
                                         cancelText={t`No`}
                      ><DeleteFilled style={{color: token.colorError}}/></Popconfirm>}>
                    <Card.Meta description={connector.id} style={{marginBottom: '1em'}}/>
                    <Table
                        columns={columns}
                        pagination={false}
                        dataSource={[
                            {
                                provider: connector.provider
                            }
                        ]}
                        {...(sm ? {scroll: {y: 'max-content'}} : {scroll: {y: 240}})}
                    />
                </Card>
                <Divider/>
            </>
        )}
    </>
}