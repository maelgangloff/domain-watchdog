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

    const successRatio = stats !== undefined ?
        (stats.domainPurchaseFailed === 0 ? undefined : stats.domainPurchased / stats.domainPurchaseFailed)
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
                        title={t`Alert sent`}
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
                        title={t`Domain name in database`}
                        prefix={<DatabaseOutlined/>}
                        value={stats?.rdapQueries}
                        valueStyle={{color: 'darkblue'}}
                    />
                </Card>
            </Col>
            <Col span={12}>
                <Card bordered={false}>
                    <Statistic
                        loading={stats === undefined}
                        title={t`Domain name tracked`}
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
                        title={t`Domain name purchased`}
                        prefix={<FieldTimeOutlined/>}
                        value={stats?.domainPurchased}
                        valueStyle={{color: '#3f8600'}}
                    />
                </Card>
            </Col>
            <Col span={12}>
                <Card bordered={false}>
                    <Tooltip
                        title={t`This value is based on the status code of the HTTP response from the providers following the order.`}>
                        <Statistic
                            loading={stats === undefined}
                            title={t`Success ratio`}
                            value={successRatio === undefined ? '-' : successRatio * 100}
                            suffix='%'
                            precision={2}
                            valueStyle={{color: successRatio === undefined ? 'black' : successRatio >= 0.5 ? 'darkgreen' : 'orange'}}
                        />
                    </Tooltip>
                </Card>
            </Col>
        </Row>
        <Divider/>
        <Row gutter={16}>
            {stats?.domainCount.map(({domain, tld}) => <Col span={4}>
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