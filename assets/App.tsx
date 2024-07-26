import {Badge, Layout, Menu, theme} from "antd";
import {
    ApiOutlined,
    BankOutlined,
    CloudServerOutlined,
    FileProtectOutlined,
    FileSearchOutlined,
    InfoCircleOutlined,
    LineChartOutlined,
    LoginOutlined,
    LogoutOutlined,
    QuestionCircleOutlined,
    SearchOutlined,
    TeamOutlined,
    UserOutlined
} from "@ant-design/icons";
import {Navigate, Route, Routes, useLocation, useNavigate} from "react-router-dom";
import TextPage from "./pages/TextPage";
import tos from "./content/tos.md";
import privacy from "./content/privacy.md";
import DomainSearchPage from "./pages/search/DomainSearchPage";
import EntitySearchPage from "./pages/search/EntitySearchPage";
import NameserverSearchPage from "./pages/search/NameserverSearchPage";
import TldPage from "./pages/info/TldPage";
import StatisticsPage from "./pages/info/StatisticsPage";
import WatchlistsPage from "./pages/tracking/WatchlistsPage";
import UserPage from "./pages/UserPage";
import React, {useCallback, useEffect, useMemo, useState} from "react";
import {getUser} from "./utils/api";
import FAQPage from "./pages/FAQPage";
import LoginPage, {AuthenticatedContext} from "./pages/LoginPage";
import ConnectorsPage from "./pages/tracking/ConnectorsPage";
import NotFoundPage from "./pages/NotFoundPage";

export default function App() {
    const {
        token: {colorBgContainer, borderRadiusLG},
    } = theme.useToken()

    const navigate = useNavigate()
    const [isAuthenticated, setIsAuthenticated] = useState(false)
    const location = useLocation()

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
            if (location.pathname !== '/login') navigate('/login')
        })
    }, []);


    const menuItems = [
        {
            key: '1',
            label: 'Search',
            children: [
                {
                    key: '1-1',
                    icon: <SearchOutlined/>,
                    label: 'Domain',
                    title: 'Domain Finder',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/domain')
                },
                {
                    key: '1-2',
                    icon: <TeamOutlined/>,
                    label: 'Entity',
                    title: 'Entity Finder',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/entity')
                },
                {
                    key: '1-3',
                    icon: <CloudServerOutlined/>,
                    label: 'Nameserver',
                    title: 'Nameserver Finder',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/search/nameserver')
                }
            ]
        },
        {
            key: '2',
            label: 'Information',
            children: [
                {
                    key: '2-1',
                    icon: <BankOutlined/>,
                    label: 'TLD',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/info/tld')
                },
                {
                    key: '2-2',
                    icon: <LineChartOutlined/>,
                    label: 'Statistics',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/info/stats')
                }
            ]
        },
        {
            key: '3',
            label: 'Tracking',
            children: [
                {
                    key: '3-1',
                    icon: <Badge count={0} size="small"><FileSearchOutlined
                        shape="square"/></Badge>,
                    label: 'My Watchlists',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/watchlist')
                },
                {
                    key: '3-2',
                    icon: <ApiOutlined/>,
                    label: 'My connectors',
                    disabled: !isAuthenticated,
                    onClick: () => navigate('/tracking/connectors')
                }
            ]
        },
        {
            key: '4',
            icon: <UserOutlined/>,
            label: 'My Account',
            disabled: !isAuthenticated,
            onClick: () => navigate('/user')
        },
        {
            key: '5',
            icon: <QuestionCircleOutlined/>,
            label: 'FAQ',
            onClick: () => navigate('/faq')
        },
        {
            key: '6',
            icon: <InfoCircleOutlined/>,
            label: 'TOS',
            onClick: () => navigate('/tos')
        },
        {
            key: '7',
            icon: <FileProtectOutlined/>,
            label: 'Privacy Policy',
            onClick: () => navigate('/privacy')
        }
    ]


    return <AuthenticatedContext.Provider value={contextValue}>
        <Layout hasSider style={{minHeight: '100vh'}}>
            <Layout.Sider>
                <Menu
                    defaultSelectedKeys={['1-1']}
                    defaultOpenKeys={['1', '2', '3']}
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
                            <Route path="/" element={<Navigate to="/search/domain"/>}/>

                            <Route path="/search/domain" element={<DomainSearchPage/>}/>
                            <Route path="/search/entity" element={<EntitySearchPage/>}/>
                            <Route path="/search/nameserver" element={<NameserverSearchPage/>}/>

                            <Route path="/info/tld" element={<TldPage/>}/>
                            <Route path="/info/stats" element={<StatisticsPage/>}/>

                            <Route path="/tracking/watchlist" element={<WatchlistsPage/>}/>
                            <Route path="/tracking/connectors" element={<ConnectorsPage/>}/>

                            <Route path="/user" element={<UserPage/>}/>

                            <Route path="/faq" element={<FAQPage/>}/>
                            <Route path="/tos" element={<TextPage markdown={tos}/>}/>
                            <Route path="/privacy" element={<TextPage markdown={privacy}/>}/>

                            <Route path="/login" element={<LoginPage/>}/>

                            <Route path="*" element={<NotFoundPage/>}/>
                        </Routes>
                    </div>
                </Layout.Content>
                <Layout.Footer style={{textAlign: 'center'}}>
                    Domain Watchdog ©{new Date().getFullYear()} Created by Maël Gangloff
                </Layout.Footer>
            </Layout>
        </Layout>
    </AuthenticatedContext.Provider>
}