import React, {useEffect, useState} from "react";
import {getStatistics, Statistics} from "../utils/api";
import {Card, Col, Divider, Row, Statistic, Tooltip} from "antd";
import {t} from "ttag";
import {
    AimOutlined,
    CompassOutlined,
    DatabaseOutlined,
    FieldTimeOutlined,
    NotificationOutlined
} from "@ant-design/icons";

export default function StatisticsPage() {

    const [stats, setStats] = useState<Statistics>()

    useEffect(() => {
        getStatistics().then(setStats)
    }, [])

    const totalDomainPurchase = (stats?.domainPurchased ?? 0) + (stats?.domainPurchaseFailed ?? 0)

    const successRate = stats !== undefined ?
        (totalDomainPurchase === 0 ? undefined : stats.domainPurchased / totalDomainPurchase)
        : undefined

    return <>
        <Row gutter={16}>
            <Col span={12}>
                <Card bordered={false}>
                    <Statistic
                        loading={stats === undefined}
                        prefix={<CompassOutlined/>}
                        title={t`RDAP queries`}
                        value={stats?.rdapQueries}
                    />
                </Card>
            </Col>
            <Col span={12}>
                <Card bordered={false}>
                    <Statistic
                        loading={stats === undefined}
                        title={t`Alerts sent`}
                        prefix={<NotificationOutlined/>}
                        value={stats?.alertSent}
                        valueStyle={{color: 'blueviolet'}}
                    />
                </Card>
            </Col>
        </Row>
        <Divider/>
        <Row gutter={16}>
            <Col span={12}>
                <Card bordered={false}>
                    <Statistic
                        loading={stats === undefined}
                        title={t`Domain names in database`}
                        prefix={<DatabaseOutlined/>}
                        value={stats?.domainCountTotal}
                        valueStyle={{color: 'darkblue'}}
                    />
                </Card>
            </Col>
            <Col span={12}>
                <Card bordered={false}>
                    <Statistic
                        loading={stats === undefined}
                        title={t`Tracked domain names`}
                        prefix={<AimOutlined/>}
                        value={stats?.domainTracked}
                        valueStyle={{color: 'darkviolet'}}
                    />
                </Card>
            </Col>
        </Row>
        <Divider/>
        <Row gutter={16}>
            <Col span={12}>
                <Card bordered={false}>
                    <Statistic
                        loading={stats === undefined}
                        title={t`Purchased domain names`}
                        prefix={<FieldTimeOutlined/>}
                        value={stats?.domainPurchased}
                        valueStyle={{color: '#3f8600'}}
                    />
                </Card>
            </Col>
            <Col span={12}>
                <Card bordered={false}>
                    <Tooltip
                        title={t`This value is based on the status code of the HTTP response from the providers following the domain order.`}>
                        <Statistic
                            loading={stats === undefined}
                            title={t`Success rate`}
                            value={successRate === undefined ? '-' : successRate * 100}
                            suffix='%'
                            valueStyle={{color: successRate === undefined ? 'grey' : successRate >= 0.5 ? 'darkgreen' : 'orange'}}
                        />
                    </Tooltip>
                </Card>
            </Col>
        </Row>
        <Divider/>
        <Row gutter={16} justify='center' align='middle'>
            {stats?.domainCount
                .sort((a, b) => b.domain - a.domain)
                .map(({domain, tld}) => <Col span={4}>
                    <Card bordered={false}>
                        <Statistic
                            loading={stats === undefined}
                            title={`.${tld}`}
                            value={domain}
                            valueStyle={{color: 'darkblue'}}
                        />
                    </Card>
                </Col>)}
        </Row>
    </>
}