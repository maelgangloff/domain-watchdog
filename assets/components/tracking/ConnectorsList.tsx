import {Card, Divider, Popconfirm, theme, Typography} from "antd";
import {t} from "ttag";
import {DeleteFilled} from "@ant-design/icons";
import React from "react";
import {Connector, deleteConnector} from "../../utils/api/connectors";

const {useToken} = theme;


export type ConnectorElement = Connector & { id: string, createdAt: string }

export function ConnectorsList({connectors, onDelete}: { connectors: ConnectorElement[], onDelete: () => void }) {
    const {token} = useToken()

    return <>
        {connectors.map(connector =>
            <>
                <Card title={<Typography.Text
                    title={new Date(connector.createdAt).toLocaleString()}>{t`Connector ${connector.provider}`}</Typography.Text>}
                      size='small'
                      style={{width: '100%'}}
                      extra={<Popconfirm title={t`Delete the Connector`}
                                         description={t`Are you sure to delete this Connector?`}
                                         onConfirm={() => deleteConnector(connector.id).then(onDelete)}
                                         okText={t`Yes`}
                                         cancelText={t`No`}
                      ><DeleteFilled style={{color: token.colorError}}/></Popconfirm>}>
                    <Card.Meta description={connector.id} style={{marginBottom: '1em'}}/>
                </Card>
                <Divider/>
            </>
        )}
    </>
}