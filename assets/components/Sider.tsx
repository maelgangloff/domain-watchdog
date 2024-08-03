import {ItemType, MenuItemType} from "antd/lib/menu/interface";
import {t} from "ttag";
import {
    AimOutlined,
    ApiOutlined,
    BankOutlined,
    CloudServerOutlined,
    CompassOutlined,
    FileSearchOutlined,
    HomeOutlined,
    LineChartOutlined,
    LoginOutlined,
    LogoutOutlined,
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
                    key: 'tld-list',
                    icon: <BankOutlined/>,
                    label: t`TLD`,
                    title: t`TLD list`,
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/info/tld')
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
            key: 'tracking',
            label: t`Tracking`,
            icon: <AimOutlined/>,
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
            key: 'stats',
            icon: <LineChartOutlined/>,
            label: t`Statistics`,
            disabled: true,
            onClick: () => navigate('/info/stats')
        }
    ]

    if (isAuthenticated) {
        menuItems.push(...[{
            key: 'account',
            icon: <UserOutlined/>,
            label: t`My Account`,
            disabled: !isAuthenticated,
            onClick: () => navigate('/user')
        }, {
            key: 'logout',
            icon: <LogoutOutlined/>,
            label: t`Log out`,
            danger: true,
            onClick: () => window.location.replace("/logout")
        }])
    } else {
        menuItems.push({
            key: 'login',
            icon: <LoginOutlined/>,
            label: t`Log in`,
            onClick: () => navigate('/login')
        })
    }

    return <Menu
        defaultSelectedKeys={['home']}
        defaultOpenKeys={['search', 'info', 'tracking', 'doc']}
        mode="inline"
        theme="dark"
        items={menuItems}
    />

}