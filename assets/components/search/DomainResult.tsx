import {Badge, Card, Divider, Flex, Space, Tag, Tooltip, Typography} from "antd";
import {t} from "ttag";
import {EventTimeline} from "./EventTimeline";
import {EntitiesList} from "./EntitiesList";
import {DomainDiagram} from "./DomainDiagram";
import React from "react";
import {Domain} from "../../utils/api";
import {rdapStatusCodeDetailTranslation} from "./rdapTranslation";
import {regionNames} from "../../i18n";

import {getCountryCode} from "../../utils/functions/getCountryCode";

export function DomainResult({domain}: { domain: Domain }) {

    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()
    const {tld} = domain

    return <Space direction="vertical" size="middle" style={{width: '100%'}}>

        <Badge.Ribbon text={
            <Tooltip
                title={tld.type === 'ccTLD' ? regionNames.of(getCountryCode(tld.tld)) : tld.type === 'gTLD' ? tld?.registryOperator : undefined}>
                {`.${domain.tld.tld.toUpperCase()} (${tld.type})`}
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
                {domain.status.length > 0 &&
                    <>
                        <Divider orientation="left">{t`EPP Status Codes`}</Divider>
                        <Flex gap="4px 0" wrap>
                            {
                                domain.status.map(s =>
                                    <Tooltip
                                        placement='bottomLeft'
                                        title={s in rdapStatusCodeDetailTranslated ? rdapStatusCodeDetailTranslated[s as keyof typeof rdapStatusCodeDetailTranslated] : undefined}>
                                        <Tag color={['active', 'ok'].includes(s) ? 'green' : 'blue'}>{s}</Tag>
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