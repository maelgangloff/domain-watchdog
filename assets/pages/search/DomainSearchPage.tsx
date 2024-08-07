import React, {useState} from "react";
import {Badge, Card, Divider, Empty, Flex, FormProps, message, Skeleton, Space, Tag, Typography} from "antd";
import {Domain, getDomain} from "../../utils/api";
import {AxiosError} from "axios"
import {t} from 'ttag'
import {DomainSearchBar, FieldType} from "../../components/search/DomainSearchBar";
import {EventTimeline} from "../../components/search/EventTimeline";
import {EntitiesList} from "../../components/search/EntitiesList";
import {showErrorAPI} from "../../utils";

const {Text} = Typography;

export default function DomainSearchPage() {
    const [domain, setDomain] = useState<Domain | null>()
    const [messageApi, contextHolder] = message.useMessage()

    const onFinish: FormProps<FieldType>['onFinish'] = (values) => {
        setDomain(null)
        getDomain(values.ldhName).then(d => {
            setDomain(d)
            messageApi.success(t`Found !`)
        }).catch((e: AxiosError) => {
            setDomain(undefined)
            showErrorAPI(e, messageApi)
        })
    }

    return <Flex gap="middle" align="center" justify="center" vertical>
        <Card title={t`Domain finder`} style={{width: '100%'}}>
            {contextHolder}
            <DomainSearchBar onFinish={onFinish}/>

            <Skeleton loading={domain === null} active>
                {
                    domain &&
                    (!domain.deleted ? <Space direction="vertical" size="middle" style={{width: '100%'}}>
                            <Badge.Ribbon text={`.${domain.tld.tld.toUpperCase()} (${domain.tld.type})`}
                                          color={
                                              domain.tld.type === 'ccTLD' ? 'purple' :
                                                  (domain.tld.type === 'gTLD' && domain.tld.specification13) ? "volcano" :
                                                      domain.tld.type === 'gTLD' ? "green"
                                                          : "cyan"
                                          }>
                                <Card title={<>
                                    {domain.ldhName}{domain.handle && <Text code>{domain.handle}</Text>}
                                </>}
                                      size="small">
                                    {domain.status.length > 0 &&
                                        <>
                                            <Divider orientation="left">{t`EPP Status Codes`}</Divider>
                                            <Flex gap="4px 0" wrap>
                                                {
                                                    domain.status.map(s =>
                                                        <Tag color={s === 'active' ? 'green' : 'blue'}>{s}</Tag>
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
                        </Space>
                        : <Empty
                            description={t`Although the domain exists in my database, it has been deleted from the WHOIS by its registrar.`}/>)
                }
            </Skeleton>
        </Card>
    </Flex>
}