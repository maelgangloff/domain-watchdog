import {Badge, Card, Col, Divider, Flex, Row, Space, Tag, Tooltip, Typography} from "antd";
import {t} from "ttag";
import {EventTimeline} from "./EventTimeline";
import {EntitiesList} from "./EntitiesList";
import {DomainDiagram} from "./DomainDiagram";
import React from "react";
import {Domain} from "../../utils/api";
import {rdapStatusCodeDetailTranslation} from "../../utils/functions/rdapTranslation";
import {regionNames} from "../../i18n";

import {getCountryCode} from "../../utils/functions/getCountryCode";
import {eppStatusCodeToColor} from "../../utils/functions/eppStatusCodeToColor";
import {DomainLifecycleSteps} from "./DomainLifecycleSteps";
import {BankOutlined, SafetyCertificateOutlined} from '@ant-design/icons'

export function DomainResult({domain}: { domain: Domain }) {

    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()
    const {tld, events} = domain
    const domainEvents = events.sort((e1, e2) => new Date(e2.date).getTime() - new Date(e1.date).getTime())
    const clientStatus = domain.status.filter(s => s.startsWith('client'))
    const serverStatus = domain.status.filter(s => !clientStatus.includes(s))

    const isLocked = (type: 'client' | 'server'): boolean =>
        (domain.status.includes(type + ' update prohibited') && domain.status.includes(type + ' delete prohibited'))
        || domain.status.includes(type + ' transfer prohibited')

    const statusToTag = (s: string) => <Tooltip
        placement='bottomLeft'
        title={rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] || undefined}>
        <Tag color={eppStatusCodeToColor(s)}>{s}</Tag>
    </Tooltip>

    return <Space direction="vertical" size="middle" style={{width: '100%'}}>

        <Badge.Ribbon text={
            <Tooltip
                title={tld.type === 'ccTLD' ? regionNames.of(getCountryCode(tld.tld)) : tld.type === 'gTLD' ? tld?.registryOperator : undefined}>
                {`${domain.tld.tld.toUpperCase()} (${tld.type})`}
            </Tooltip>
        }
                      color={
                          tld.type === 'ccTLD' ? 'purple' :
                              (tld.type === 'gTLD' && tld.specification13) ? "volcano" :
                                  tld.type === 'gTLD' ? "green"
                                      : "cyan"
                      }>

            <Card title={<Space>
                {domain.ldhName}{domain.handle && <Typography.Text code>{domain.handle}</Typography.Text>}
            </Space>}
                  size="small">
                {
                    domain.events.length > 0 && <DomainLifecycleSteps status={domain.status}/>
                }
                <Row gutter={8}>
                    <Col span={24} xl={12} xxl={12}>
                        <Flex justify='center' align='center' style={{margin: 10}}>
                            <Tooltip
                                title={t`Registry-level protection, ensuring the highest level of security by preventing unauthorized, unwanted, or accidental changes to the domain name at the registry level`}>
                                <Tag bordered={false} color={isLocked('server') ? 'green' : 'default'}
                                     icon={<SafetyCertificateOutlined
                                         style={{fontSize: '16px'}}/>}>{t`Registry Lock`}</Tag>
                            </Tooltip>
                            <Tooltip
                                title={t`Registrar-level protection, safeguarding the domain from unauthorized, unwanted, or accidental changes through registrar controls`}>
                                <Tag bordered={false} color={isLocked('client') ? 'green' : 'default'}
                                     icon={<BankOutlined
                                         style={{fontSize: '16px'}}/>}>{t`Registrar Lock`}</Tag>
                            </Tooltip>
                        </Flex>
                        {domain.status.length > 0 &&
                            <>
                                <Divider orientation="left">{t`EPP Status Codes`}</Divider>
                                {
                                    serverStatus && <Flex gap="4px 0" wrap>
                                        {serverStatus.map(statusToTag)}
                                    </Flex>
                                }
                                {
                                    clientStatus && <Flex gap="4px 0" wrap>
                                        {clientStatus.map(statusToTag)}
                                    </Flex>
                                }
                            </>
                        }
                        {
                            domain.events.length > 0 && <>
                                <Divider orientation="left">{t`Timeline`}</Divider>
                                <EventTimeline events={domainEvents}/>
                            </>
                        }
                        {
                            domain.entities.length > 0 &&
                            <>
                                <Divider orientation="left">{t`Entities`}</Divider>
                                <EntitiesList domain={domain}/>
                            </>
                        }
                    </Col>
                    <Col span={24} xl={12} xxl={12}>
                        <DomainDiagram domain={domain}/>
                    </Col>
                </Row>
            </Card>
        </Badge.Ribbon>
    </Space>
}