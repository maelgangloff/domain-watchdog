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
import useBreakpoint from "../../hooks/useBreakpoint";

export function DomainResult({domain}: { domain: Domain }) {

    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()
    const {tld, events} = domain
    const domainEvents = events.sort((e1, e2) => new Date(e2.date).getTime() - new Date(e1.date).getTime())
    const xxl = useBreakpoint('xxl')

    return <Space direction="vertical" size="middle" style={{width: '100%'}}>

        <Badge.Ribbon text={
            <Tooltip
                title={tld.type === 'ccTLD' ? regionNames.of(getCountryCode(tld.tld)) : tld.type === 'gTLD' ? tld?.registryOperator : undefined}>
                {`${(domain.tld.tld === '.' ? '' : '.') + domain.tld.tld.toUpperCase()} (${tld.type})`}
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
                    <Col span={xxl ? 24 : 12}>
                        {domain.status.length > 0 &&
                            <>
                                <Divider orientation="left">{t`EPP Status Codes`}</Divider>
                                <Flex gap="4px 0" wrap>
                                    {
                                        domain.status.map(s =>
                                            <Tooltip
                                                placement='bottomLeft'
                                                title={s in rdapStatusCodeDetailTranslated ? rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] : undefined}>
                                                <Tag color={eppStatusCodeToColor(s)}>{s}</Tag>
                                            </Tooltip>
                                        )
                                    }
                                </Flex>
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
                    {!xxl &&
                        <Col span={12}>
                            <DomainDiagram domain={domain}/>
                        </Col>
                    }
                </Row>
            </Card>
        </Badge.Ribbon>
        {xxl && <DomainDiagram domain={domain}/>}
    </Space>
}