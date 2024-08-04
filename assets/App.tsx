import {Button, Layout, Space, theme, Typography} from "antd";
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
import useBreakpoint from "./hooks/useBreakpoint";
import {Sider} from "./components/Sider";
import {jt, t} from "ttag";

const ProjectLink = <Link to='https://github.com/maelgangloff/domain-watchdog'>Domain Watchdog</Link>
const LicenseLink = <Link to='https://www.gnu.org/licenses/agpl-3.0.txt'>AGPL-3.0-or-later</Link>

export default function App() {
    const {
        token: {colorBgContainer, borderRadiusLG},
    } = theme.useToken()

    const navigate = useNavigate()
    const location = useLocation()
    const sm = useBreakpoint('sm')


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
            if (location.pathname === '/login') navigate('/home')
        }).catch(() => {
            setIsAuthenticated(false)
            if (location.pathname !== '/login') navigate('/home')
        })
    }, []);


    return <AuthenticatedContext.Provider value={contextValue}>
        <Layout hasSider style={{minHeight: '100vh'}}>
            {/* Ant will use a break-off tab to toggle the collapse of the sider when collapseWidth = 0*/}
            <Layout.Sider collapsible breakpoint="sm" {...(sm ? {collapsedWidth: 0} : {})}>
                <Sider isAuthenticated={isAuthenticated}/>
            </Layout.Sider>
            <Layout>
                <Layout.Header style={{padding: 0, background: colorBgContainer}}/>
                <Layout.Content style={sm ? {margin: '24px 0'} : {margin: '24px 16px 0'}}>
                    <div style={{
                        padding: 24,
                        minHeight: 360,
                        background: colorBgContainer,
                        borderRadius: borderRadiusLG,
                    }}>

                        <Routes>
                            <Route path="/" element={<Navigate to="/login"/>}/>
                            <Route path="/home" element={<TextPage resource='home.md'/>}/>

                            <Route path="/search/domain" element={<DomainSearchPage/>}/>
                            <Route path="/search/entity" element={<EntitySearchPage/>}/>
                            <Route path="/search/nameserver" element={<NameserverSearchPage/>}/>

                            <Route path="/info/tld" element={<TldPage/>}/>
                            <Route path="/info/stats" element={<StatisticsPage/>}/>

                            <Route path="/tracking/watchlist" element={<WatchlistPage/>}/>
                            <Route path="/tracking/connectors" element={<ConnectorsPage/>}/>

                            <Route path="/user" element={<UserPage/>}/>

                            <Route path="/faq" element={<TextPage resource='faq.md'/>}/>
                            <Route path="/tos" element={<TextPage resource='tos.md'/>}/>
                            <Route path="/privacy" element={<TextPage resource='privacy.md'/>}/>

                            <Route path="/login" element={<LoginPage/>}/>

                            <Route path="*" element={<NotFoundPage/>}/>
                        </Routes>
                    </div>
                </Layout.Content>
                <Layout.Footer style={{textAlign: 'center'}}>
                    <Space size='middle'>
                        <Link to='/tos'><Button type='text'>{t`TOS`}</Button></Link>
                        <Link to='/privacy'><Button type='text'>{t`Privacy Policy`}</Button></Link>
                        <Link to='/faq'><Button type='text'>{t`FAQ`}</Button></Link>
                    </Space>
                    <Typography.Paragraph>{jt`${ProjectLink} is an open source project distributed under the ${LicenseLink} license.`}</Typography.Paragraph>
                </Layout.Footer>
            </Layout>
        </Layout>
    </AuthenticatedContext.Provider>
}