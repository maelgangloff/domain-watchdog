import {ItemType} from "antd/lib/menu/interface";
import {t} from "ttag";
import {
    AimOutlined,
    ApiOutlined,
    BankOutlined,
    CompassOutlined,
    FileSearchOutlined,
    HomeOutlined,
    LineChartOutlined,
    LoginOutlined,
    LogoutOutlined,
    SearchOutlined,
    TableOutlined,
    UserOutlined
} from "@ant-design/icons";
import {Menu} from "antd";
import React from "react";
import {useLocation, useNavigate} from "react-router-dom";

export function Sider({isAuthenticated}: { isAuthenticated: boolean }) {
    const navigate = useNavigate()
    const location = useLocation()

    const menuItems: ItemType[] = [
        {
            key: '/home',
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
                    key: '/search/domain',
                    icon: <CompassOutlined/>,
                    label: t`Domain`,
                    title: t`Domain Finder`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/domain')
                },
                {
                    key: '/search/tld',
                    icon: <BankOutlined/>,
                    label: t`TLD`,
                    title: t`TLD list`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/tld')
                },
                /*
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
                */
            ]
        },
        {
            key: 'tracking',
            label: t`Tracking`,
            icon: <AimOutlined/>,
            children: [
                {
                    key: '/tracking/watchlist',
                    icon: <FileSearchOutlined/>,
                    label: t`My Watchlists`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/watchlist')
                },
                {
                    key: '/tracking/domains',
                    icon: <TableOutlined/>,
                    label: t`Tracked domain names`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/domains')
                },
                {
                    key: '/tracking/connectors',
                    icon: <ApiOutlined/>,
                    label: t`My Connectors`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/connectors')
                }
            ]
        },
        {
            key: '/stats',
            icon: <LineChartOutlined/>,
            label: t`Statistics`,
            disabled: !isAuthenticated,
            onClick: () => navigate('/stats')
        }
    ]

    if (isAuthenticated) {
        menuItems.push(...[{
            key: '/user',
            icon: <UserOutlined/>,
            label: t`My Account`,
            onClick: () => navigate('/user')
        }, {
            key: '/logout',
            icon: <LogoutOutlined/>,
            label: t`Log out`,
            danger: true,
            onClick: () => window.location.replace("/logout")
        }])
    } else {
        menuItems.push({
            key: '/login',
            icon: <LoginOutlined/>,
            label: t`Log in`,
            onClick: () => navigate('/login')
        })
    }

    return <Menu
        defaultOpenKeys={['search', 'info', 'tracking', 'doc']}
        selectedKeys={[location.pathname.includes('/search/domain') ? '/search/domain' : location.pathname]}
        mode="inline"
        theme="dark"
        items={menuItems}
    />

}