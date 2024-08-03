import {ItemType, MenuItemType} from "antd/lib/menu/interface";
import {t} from "ttag";
import {
    ApiOutlined,
    BankOutlined,
    CloudServerOutlined,
    CompassOutlined,
    FileProtectOutlined,
    FileSearchOutlined,
    HomeOutlined,
    InfoCircleOutlined,
    LineChartOutlined,
    LoginOutlined,
    LogoutOutlined,
    QuestionCircleOutlined,
    SearchOutlined,
    TeamOutlined,
    UserOutlined
} from "@ant-design/icons";
import {Badge, Menu} from "antd";
import React from "react";
import {useNavigate} from "react-router-dom";

export function Sider({isAuthenticated}: { isAuthenticated: boolean }) {
    const navigate = useNavigate()


    const menuItems: ItemType<MenuItemType>[] = [
        {
            key: 'home',
            label: t`Home`,
            icon: <HomeOutlined/>,
            onClick: () => navigate('/home')
        },
        {
            key: 'search',
            label: t`Search`,
            icon: <SearchOutlined/>,
            children: [
                {
                    key: 'domain-finder',
                    icon: <CompassOutlined/>,
                    label: t`Domain`,
                    title: t`Domain Finder`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/domain')
                },
                {
                    key: 'entity-finder',
                    icon: <TeamOutlined/>,
                    label: t`Entity`,
                    title: t`Entity Finder`,
                    disabled: true,
                    onClick: () => navigate('/search/entity')
                },
                {
                    key: 'ns-finder',
                    icon: <CloudServerOutlined/>,
                    label: t`Nameserver`,
                    title: t`Nameserver Finder`,
                    disabled: true,
                    onClick: () => navigate('/search/nameserver')
                }
            ]
        },
        {
            key: 'info',
            label: t`Information`,
            icon: <InfoCircleOutlined/>,
            children: [
                {
                    key: 'tld-list',
                    icon: <BankOutlined/>,
                    label: t`TLD`,
                    title: t`TLD list`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/info/tld')
                },
                {
                    key: 'stats',
                    icon: <LineChartOutlined/>,
                    label: t`Statistics`,
                    disabled: true,
                    onClick: () => navigate('/info/stats')
                }
            ]
        },
        {
            key: 'tracking',
            label: t`Tracking`,
            icon: <FileSearchOutlined/>,
            children: [
                {
                    key: 'watchlist',
                    icon: <Badge count={0} size="small"><FileSearchOutlined/></Badge>,
                    label: t`My Watchlists`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/watchlist')
                },
                {
                    key: 'connectors',
                    icon: <ApiOutlined/>,
                    label: t`My Connectors`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/connectors')
                }
            ]
        },
        {
            key: 'watchdog',
            label: t`My Watchdog`,
            icon: <UserOutlined/>,
            children: [
                {
                    key: 'account',
                    icon: <UserOutlined/>,
                    label: t`My Account`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/user')
                },
                {
                    key: 'tos',
                    icon: <InfoCircleOutlined/>,
                    label: t`TOS`,
                    onClick: () => navigate('/tos')
                },
                {
                    key: 'privacy',
                    icon: <FileProtectOutlined/>,
                    label: t`Privacy Policy`,
                    onClick: () => navigate('/privacy')
                }
            ]
        },
        {
            key: '5',
            icon: <QuestionCircleOutlined/>,
            label: t`FAQ`,
            onClick: () => navigate('/faq')
        },

    ]

    return <Menu
        defaultSelectedKeys={['home']}
        defaultOpenKeys={['search', 'info', 'tracking', 'watchdog']}
        mode="inline"
        theme="dark"
        items={[...menuItems, isAuthenticated ? {
            key: '8',
            icon: <LogoutOutlined/>,
            label: t`Log out`,
            danger: true,
            onClick: () => window.location.replace("/logout")
        } : {
            key: '8',
            icon: <LoginOutlined/>,
            label: t`Log in`,
            onClick: () => navigate('/login')
        }]}
    />

}