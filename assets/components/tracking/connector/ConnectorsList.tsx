import {Card, Divider, message, Popconfirm, Space, theme, Typography} from 'antd'
import {jt, t} from 'ttag'
import {DeleteFilled} from '@ant-design/icons'
import React from 'react'
import type {Connector} from '../../../utils/api/connectors'
import {ConnectorProvider, deleteConnector} from '../../../utils/api/connectors'
import {providersConfig} from "../../../utils/providers"

const {useToken} = theme

export type ConnectorElement = Connector & { id: string, createdAt: string, watchlistCount: number }

export function ConnectorsList({connectors, onDelete}: { connectors: ConnectorElement[], onDelete: () => void }) {
    const {token} = useToken()
    const [messageApi, contextHolder] = message.useMessage()

    const onConnectorDelete = async (connector: ConnectorElement) => await deleteConnector(connector.id)
        .then(onDelete)
        .catch(() => messageApi.error(t`An error occurred while deleting the Connector. Make sure it is not used in any Watchlist`))

    return (
        <>
            <Divider/>
            {connectors.map(connector => {
                    const createdAt = <Typography.Text strong>
                        {new Date(connector.createdAt).toLocaleString()}
                    </Typography.Text>
                    const {watchlistCount} = connector
                    const connectorName = Object.keys(ConnectorProvider).find(p => ConnectorProvider[p as keyof typeof ConnectorProvider] === connector.provider)

                    return <>
                        {contextHolder}
                        <Card
                            hoverable title={<Space>
                            {t`Connector ${connectorName}`}<Typography.Text code>{connector.id}</Typography.Text>
                        </Space>}
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
                            <Typography.Paragraph>{jt`Creation date: ${createdAt}`}</Typography.Paragraph>
                            <Typography.Paragraph>{t`Used in: ${watchlistCount} Watchlist`}</Typography.Paragraph>
                            <Card.Meta description={
                                <>
                                    {t`You can stop using a connector at any time. To delete a connector, you must remove it from each linked Watchlist.
The creation date corresponds to the date on which you consented to the creation of the connector and on which you declared in particular that you fulfilled the conditions of use of the supplier's API, waived the right of withdrawal and were of the minimum age to consent to these conditions.`}
                                    &nbsp;
                                    {providersConfig[connector.provider].tosLink && <Typography.Link href={providersConfig[connector.provider].tosLink}>
                                {t`The Provider’s conditions are accessible by following this hyperlink.`}
                                </Typography.Link>}
                                </>
                            }/>
                        </Card>
                    </>
                }
            )}
        </>
    )
}
