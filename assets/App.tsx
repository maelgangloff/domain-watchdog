import {Button, ConfigProvider, FloatButton, Layout, Space, theme, Tooltip, Typography} from 'antd'
import {Link, Navigate, Route, Routes, useLocation, useNavigate} from 'react-router-dom'
import TextPage from './pages/TextPage'
import DomainSearchPage from './pages/search/DomainSearchPage'
import EntitySearchPage from './pages/search/EntitySearchPage'
import TldPage from './pages/infrastructure/TldPage'
import StatisticsPage from './pages/StatisticsPage'
import WatchlistPage from './pages/tracking/WatchlistPage'
import UserPage from './pages/UserPage'
import React, {useCallback, useEffect, useMemo, useState} from 'react'
import {getUser} from './utils/api'
import LoginPage, {AuthenticatedContext} from './pages/LoginPage'
import ConnectorPage from './pages/tracking/ConnectorPage'
import NotFoundPage from './pages/NotFoundPage'
import useBreakpoint from './hooks/useBreakpoint'
import {Sider} from './components/Sider'
import {jt, t} from 'ttag'
import {BugOutlined, InfoCircleOutlined, MergeOutlined} from '@ant-design/icons'
import TrackedDomainPage from './pages/tracking/TrackedDomainPage'
import IcannRegistrarPage from "./pages/infrastructure/IcannRegistrarPage"

const PROJECT_LINK = 'https://github.com/maelgangloff/domain-watchdog'
const LICENSE_LINK = 'https://www.gnu.org/licenses/agpl-3.0.txt'

const ProjectLink = <Typography.Link target='_blank' href={PROJECT_LINK}>Domain Watchdog</Typography.Link>
const LicenseLink = <Typography.Link target='_blank' href={LICENSE_LINK}>AGPL-3.0-or-later</Typography.Link>

export default function App(): React.ReactElement {
    const navigate = useNavigate()
    const location = useLocation()
    const sm = useBreakpoint('sm')

    const [isAuthenticated, setIsAuthenticated] = useState(false)

    const authenticated = useCallback((authenticated: boolean) => {
        setIsAuthenticated(authenticated)
    }, [])

    const contextValue = useMemo(() => ({
        authenticated,
        setIsAuthenticated
    }), [authenticated, setIsAuthenticated])

    const [darkMode, setDarkMode] = useState(false)

    const windowQuery = window.matchMedia('(prefers-color-scheme:dark)')
    const darkModeChange = useCallback((event: MediaQueryListEvent) => {
        setDarkMode(event.matches)
    }, [])

    useEffect(() => {
        windowQuery.addEventListener('change', darkModeChange)
        return () => {
            windowQuery.removeEventListener('change', darkModeChange)
        }
    }, [windowQuery, darkModeChange])

    useEffect(() => {
        setDarkMode(windowQuery.matches)
        getUser().then(() => {
            setIsAuthenticated(true)
            if (location.pathname === '/login') navigate('/home')
        }).catch(() => {
            setIsAuthenticated(false)
            const pathname = location.pathname
            if (!['/login', '/tos', '/faq', '/privacy'].includes(pathname)) navigate('/home')
        })
    }, [])

    return (
        <ConfigProvider
            theme={{
                algorithm: darkMode ? theme.darkAlgorithm : undefined
            }}
        >
            <AuthenticatedContext.Provider value={contextValue}>
                <Layout hasSider style={{minHeight: '100vh'}}>
                    {/* Ant will use a break-off tab to toggle the collapse of the sider when collapseWidth = 0 */}
                    <Layout.Sider collapsible breakpoint='sm' width={220} {...(sm ? {collapsedWidth: 0} : {})}>
                        <Sider isAuthenticated={isAuthenticated}/>
                    </Layout.Sider>
                    <Layout>
                        <Layout.Header style={{padding: 0}}/>
                        <Layout.Content style={sm ? {margin: '24px 0'} : {margin: '24px 16px 0'}}>
                            <div style={{
                                padding: 24,
                                minHeight: 360
                            }}
                            >
                                <Routes>
                                    <Route path='/' element={<Navigate to='/login'/>}/>
                                    <Route path='/home' element={<TextPage resource='home.md'/>}/>

                                    <Route path='/search/domain' element={<DomainSearchPage/>}/>
                                    <Route path='/search/domain/:query' element={<DomainSearchPage/>}/>
                                    <Route path='/search/entity' element={<EntitySearchPage/>}/>

                                    <Route path='/infrastructure/tld' element={<TldPage/>}/>
                                    <Route path='/infrastructure/icann' element={<IcannRegistrarPage/>}/>

                                    <Route path='/tracking/watchlist' element={<WatchlistPage/>}/>
                                    <Route path='/tracking/domains' element={<TrackedDomainPage/>}/>
                                    <Route path='/tracking/connectors' element={<ConnectorPage/>}/>

                                    <Route path='/stats' element={<StatisticsPage/>}/>
                                    <Route path='/user' element={<UserPage/>}/>

                                    <Route path='/faq' element={<TextPage resource='faq.md'/>}/>
                                    <Route path='/tos' element={<TextPage resource='tos.md'/>}/>
                                    <Route path='/privacy' element={<TextPage resource='privacy.md'/>}/>

                                    <Route path='/login' element={<LoginPage/>}/>

                                    <Route path='*' element={<NotFoundPage/>}/>
                                </Routes>
                            </div>
                        </Layout.Content>
                        <Layout.Footer style={{textAlign: 'center'}}>
                            <Space size='middle' wrap align='center'>
                                <Link to='/tos'><Button type='text'>{t`TOS`}</Button></Link>
                                <Link to='/privacy'><Button type='text'>{t`Privacy Policy`}</Button></Link>
                                <Link to='/faq'><Button type='text'>{t`FAQ`}</Button></Link>
                                <Typography.Link
                                    target='_blank'
                                    href='https://github.com/maelgangloff/domain-watchdog/wiki'
                                >
                                    <Button
                                        type='text'
                                    >{t`Documentation`}
                                    </Button>
                                </Typography.Link>
                            </Space>
                            <Typography.Paragraph style={{marginTop: '1em'}}>
                                {jt`${ProjectLink} is an open source project distributed under the ${LicenseLink} license.`}
                            </Typography.Paragraph>
                        </Layout.Footer>
                    </Layout>
                    <FloatButton.Group
                        trigger='hover'
                        style={{
                            position: 'fixed',
                            insetInlineEnd: (100 - 40) / 2,
                            bottom: 100 - 40 / 2
                        }}
                        icon={<InfoCircleOutlined/>}
                    >
                        <Tooltip title={t`Official git repository`} placement='left'>
                            <FloatButton icon={<MergeOutlined/>} target='_blank' href={PROJECT_LINK}/>
                        </Tooltip>
                        <Tooltip title={t`Submit an issue`} placement='left'>
                            <FloatButton icon={<BugOutlined/>} target='_blank' href={PROJECT_LINK + '/issues'}/>
                        </Tooltip>
                    </FloatButton.Group>
                </Layout>
            </AuthenticatedContext.Provider>
        </ConfigProvider>
    )
}
