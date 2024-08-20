import {Badge, Card, Divider, Flex, Space, Tag, Tooltip, Typography} from "antd";
import {t} from "ttag";
import {EventTimeline} from "./EventTimeline";
import {EntitiesList} from "./EntitiesList";
import {DomainDiagram} from "./DomainDiagram";
import React from "react";
import {Domain} from "../../utils/api";
import {rdapStatusCodeDetailTranslation} from "./rdapTranslation";

export function DomainResult({domain}: { domain: Domain }) {

    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()

    return <Space direction="vertical" size="middle" style={{width: '100%'}}>
        <Badge.Ribbon text={`.${domain.tld.tld.toUpperCase()} (${domain.tld.type})`}
                      color={
                          domain.tld.type === 'ccTLD' ? 'purple' :
                              (domain.tld.type === 'gTLD' && domain.tld.specification13) ? "volcano" :
                                  domain.tld.type === 'gTLD' ? "green"
                                      : "cyan"
                      }>
            <Card title={<>
                {domain.ldhName}{domain.handle && <Typography.Text code>{domain.handle}</Typography.Text>}
            </>}
                  size="small">
                {domain.status.length > 0 &&
                    <>
                        <Divider orientation="left">{t`EPP Status Codes`}</Divider>
                        <Flex gap="4px 0" wrap>
                            {
                                domain.status.map(s =>
                                    <Tooltip
                                        placement='bottomLeft'
                                        title={s in rdapStatusCodeDetailTranslated ? rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] : undefined}>
                                        <Tag color={s === 'active' ? 'green' : 'blue'}>{s}</Tag>
                                    </Tooltip>
                                )
                            }
                        </Flex>
                    </>
                }
                <Divider orientation="left">{t`Timeline`}</Divider>
                <EventTimeline domain={domain}/>
                {
                    domain.entities.length > 0 &&
                    <>
                        <Divider orientation="left">{t`Entities`}</Divider>
                        <EntitiesList domain={domain}/>
                    </>
                }
            </Card>
        </Badge.Ribbon>
        <DomainDiagram domain={domain}/>
    </Space>
}