import {Badge, Layout, Menu, theme} from "antd";
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
import {Link, Navigate, Route, Routes, useLocation, useNavigate} from "react-router-dom";
import TextPage from "./pages/TextPage";
import DomainSearchPage from "./pages/search/DomainSearchPage";
import EntitySearchPage from "./pages/search/EntitySearchPage";
import NameserverSearchPage from "./pages/search/NameserverSearchPage";
import TldPage from "./pages/info/TldPage";
import StatisticsPage from "./pages/info/StatisticsPage";
import WatchlistPage from "./pages/tracking/WatchlistPage";
import UserPage from "./pages/watchdog/UserPage";
import React, {useCallback, useEffect, useMemo, useState} from "react";
import {getUser} from "./utils/api";
import LoginPage, {AuthenticatedContext} from "./pages/LoginPage";
import ConnectorsPage from "./pages/tracking/ConnectorsPage";
import NotFoundPage from "./pages/NotFoundPage";
import {ItemType, MenuItemType} from "antd/lib/menu/interface";

import tos from "./content/tos.md";
import privacy from "./content/privacy.md";
import home from './content/home.md'
import faq from './content/faq.md'


export default function App() {
    const {
        token: {colorBgContainer, borderRadiusLG},
    } = theme.useToken()

    const navigate = useNavigate()
    const location = useLocation()


    const [isAuthenticated, setIsAuthenticated] = useState(false)


    const authenticated = useCallback((authenticated: boolean) => {
        setIsAuthenticated(authenticated)
    }, []);

    const contextValue = useMemo(() => ({
        authenticated,
        setIsAuthenticated
    }), [authenticated, setIsAuthenticated]);

    useEffect(() => {
        getUser().then(() => {
            setIsAuthenticated(true)
            if (location.pathname === '/login') navigate('/search/domain')
        }).catch(() => {
            setIsAuthenticated(false)
            navigate('/home')
        })
    }, []);


    const menuItems: ItemType<MenuItemType>[] = [
        {
            key: 'home',
            label: 'Home',
            icon: <HomeOutlined/>,
            onClick: () => navigate('/home')
        },
        {
            key: 'search',
            label: 'Search',
            icon: <SearchOutlined/>,
            children: [
                {
                    key: 'domain-finder',
                    icon: <CompassOutlined/>,
                    label: 'Domain',
                    title: 'Domain Finder',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/domain')
                },
                {
                    key: 'entity-finder',
                    icon: <TeamOutlined/>,
                    label: 'Entity',
                    title: 'Entity Finder',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/entity')
                },
                {
                    key: 'ns-finder',
                    icon: <CloudServerOutlined/>,
                    label: 'Nameserver',
                    title: 'Nameserver Finder',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/nameserver')
                }
            ]
        },
        {
            key: 'info',
            label: 'Information',
            icon: <InfoCircleOutlined/>,
            children: [
                {
                    key: 'tld-list',
                    icon: <BankOutlined/>,
                    label: 'TLD',
                    title: 'TLD list',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/info/tld')
                },
                {
                    key: 'stats',
                    icon: <LineChartOutlined/>,
                    label: 'Statistics',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/info/stats')
                }
            ]
        },
        {
            key: 'tracking',
            label: 'Tracking',
            icon: <FileSearchOutlined/>,
            children: [
                {
                    key: 'watchlist',
                    icon: <Badge count={0} size="small"><FileSearchOutlined/></Badge>,
                    label: 'My Watchlists',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/watchlist')
                },
                {
                    key: 'connectors',
                    icon: <ApiOutlined/>,
                    label: 'My connectors',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/connectors')
                }
            ]
        },
        {
            key: 'watchdog',
            label: 'My Watchdog',
            icon: <UserOutlined/>,
            children: [
                {
                    key: 'account',
                    icon: <UserOutlined/>,
                    label: 'My Account',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/user')
                },
                {
                    key: 'tos',
                    icon: <InfoCircleOutlined/>,
                    label: 'TOS',
                    onClick: () => navigate('/tos')
                },
                {
                    key: 'privacy',
                    icon: <FileProtectOutlined/>,
                    label: 'Privacy Policy',
                    onClick: () => navigate('/privacy')
                }
            ]
        },
        {
            key: '5',
            icon: <QuestionCircleOutlined/>,
            label: 'FAQ',
            onClick: () => navigate('/faq')
        },

    ]


    return <AuthenticatedContext.Provider value={contextValue}>
        <Layout hasSider style={{minHeight: '100vh'}}>
            <Layout.Sider collapsible>
                <Menu
                    defaultSelectedKeys={['home']}
                    defaultOpenKeys={['search', 'info', 'tracking', 'watchdog']}
                    mode="inline"
                    theme="dark"
                    items={[...menuItems, isAuthenticated ? {
                        key: '8',
                        icon: <LogoutOutlined/>,
                        label: 'Log out',
                        danger: true,
                        onClick: () => window.location.replace("/logout")
                    } : {
                        key: '8',
                        icon: <LoginOutlined/>,
                        label: 'Log in',
                        onClick: () => navigate('/login')
                    }]}
                />
                <div className="demo-logo-vertical"></div>
            </Layout.Sider>
            <Layout>
                <Layout.Header style={{padding: 0, background: colorBgContainer}}/>
                <Layout.Content style={{margin: '24px 16px 0'}}>
                    <div style={{
                        padding: 24,
                        minHeight: 360,
                        background: colorBgContainer,
                        borderRadius: borderRadiusLG,
                    }}>

                        <Routes>
                            <Route path="/" element={<Navigate to="/login"/>}/>
                            <Route path="/home" element={<TextPage markdown={home}/>}/>

                            <Route path="/search/domain" element={<DomainSearchPage/>}/>
                            <Route path="/search/entity" element={<EntitySearchPage/>}/>
                            <Route path="/search/nameserver" element={<NameserverSearchPage/>}/>

                            <Route path="/info/tld" element={<TldPage/>}/>
                            <Route path="/info/stats" element={<StatisticsPage/>}/>

                            <Route path="/tracking/watchlist" element={<WatchlistPage/>}/>
                            <Route path="/tracking/connectors" element={<ConnectorsPage/>}/>

                            <Route path="/user" element={<UserPage/>}/>

                            <Route path="/faq" element={<TextPage markdown={faq}/>}/>
                            <Route path="/tos" element={<TextPage markdown={tos}/>}/>
                            <Route path="/privacy" element={<TextPage markdown={privacy}/>}/>

                            <Route path="/login" element={<LoginPage/>}/>

                            <Route path="*" element={<NotFoundPage/>}/>
                        </Routes>
                    </div>
                </Layout.Content>
                <Layout.Footer style={{textAlign: 'center'}}>
                    <Link to='https://github.com/maelgangloff/domain-watchdog'>Domain
                        Watchdog</Link> ©{new Date().getFullYear()} Created by Maël Gangloff
                </Layout.Footer>
            </Layout>
        </Layout>
    </AuthenticatedContext.Provider>
}