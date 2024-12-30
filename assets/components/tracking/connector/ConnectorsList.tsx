import {Card, Divider, message, Popconfirm, theme, Typography} from 'antd'
import {t} from 'ttag'
import {DeleteFilled} from '@ant-design/icons'
import React from 'react'
import {Connector, deleteConnector} from '../../../utils/api/connectors'

const {useToken} = theme

export type ConnectorElement = Connector & { id: string, createdAt: string }

export function ConnectorsList({connectors, onDelete}: { connectors: ConnectorElement[], onDelete: () => void }) {
    const {token} = useToken()
    const [messageApi, contextHolder] = message.useMessage()

    const onConnectorDelete = async (connector: ConnectorElement) => await deleteConnector(connector.id)
        .then(onDelete)
        .catch(() => messageApi.error(t`An error occurred while deleting the Connector. Make sure it is not used in any Watchlist`))

    return (
        <>
            {connectors.map(connector =>
                <>
                    {contextHolder}
                    <Card
                        hoverable title={<Typography.Text
                        title={new Date(connector.createdAt).toLocaleString()}
                    >{t`Connector ${connector.provider}`}
                    </Typography.Text>}
                        size='small'
                        style={{width: '100%'}}
                        extra={<Popconfirm
                            title={t`Delete the Connector`}
                            description={t`Are you sure to delete this Connector?`}
                            onConfirm={async () => await onConnectorDelete(connector)}
                            okText={t`Yes`}
                            cancelText={t`No`}
                        ><DeleteFilled style={{color: token.colorError}}/>
                        </Popconfirm>}
                    >
                        <Card.Meta description={connector.id} style={{marginBottom: '1em'}}/>
                    </Card>
                    <Divider/>
                </>
            )}
        </>
    )
}
