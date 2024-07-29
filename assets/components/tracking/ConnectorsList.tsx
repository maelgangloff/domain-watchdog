import {Card, Divider, Popconfirm, Typography} from "antd";
import {t} from "ttag";
import {DeleteFilled} from "@ant-design/icons";
import React from "react";
import {Connector, deleteConnector} from "../../utils/api/connectors";

type ConnectorElement = Connector & { id: string }

export function ConnectorsList({connectors, onDelete}: { connectors: ConnectorElement[], onDelete: () => void }) {
    return <>
        {connectors.map(connector =>
            <>
                <Card title={t`Connector ${connector.id}`} extra={<Popconfirm
                    title={t`Delete the Connector`}
                    description={t`Are you sure to delete this Connector?`}
                    onConfirm={() => deleteConnector(connector.id).then(onDelete)}
                    okText={t`Yes`}
                    cancelText={t`No`}
                ><DeleteFilled/> </Popconfirm>}>
                    <Typography.Paragraph>
                        {t`Provider`} : {connector.provider}
                    </Typography.Paragraph>
                </Card>
                <Divider/>
            </>
        )}
    </>
}