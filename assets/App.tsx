import {Alert, Button, ConfigProvider, Drawer, Flex, Layout, message, theme, Typography} from 'antd'
import {Link, Navigate, Route, Routes, useLocation, useNavigate} from 'react-router-dom'
import TextPage from './pages/TextPage'
import DomainSearchPage from './pages/search/DomainSearchPage'
import EntitySearchPage from './pages/search/EntitySearchPage'
import TldPage from './pages/infrastructure/TldPage'
import StatisticsPage from './pages/StatisticsPage'
import WatchlistPage from './pages/tracking/WatchlistPage'
import UserPage from './pages/UserPage'
import type {PropsWithChildren} from 'react'
import React, {useCallback, useEffect, useMemo, useState} from 'react'
import {getConfiguration, getUser, type InstanceConfig} from './utils/api'
import LoginPage from './pages/LoginPage'
import ConnectorPage from './pages/tracking/ConnectorPage'
import NotFoundPage from './pages/NotFoundPage'
import useBreakpoint from './hooks/useBreakpoint'
import {Sider} from './components/Sider'
import {jt, t} from 'ttag'
import {MenuOutlined} from '@ant-design/icons'
import TrackedDomainPage from './pages/tracking/TrackedDomainPage'
import IcannRegistrarPage from "./pages/infrastructure/IcannRegistrarPage"
import type {AuthContextType} from "./contexts"
import {AuthenticatedContext, ConfigurationContext} from "./contexts"

const PROJECT_LINK = 'https://github.com/maelgangloff/domain-watchdog'
const LICENSE_LINK = 'https://www.gnu.org/licenses/agpl-3.0.txt'

const ProjectLink = <Typography.Link key="projectLink" target='_blank' href={PROJECT_LINK}>Domain
    Watchdog</Typography.Link>
const LicenseLink = <Typography.Link key="licenceLink" target='_blank' rel='license'
                                     href={LICENSE_LINK}>AGPL-3.0-or-later</Typography.Link>

function SiderWrapper(props: PropsWithChildren<{
    sidebarCollapsed: boolean,
    setSidebarCollapsed: (collapsed: boolean) => void
}>): React.ReactElement {
    const {sidebarCollapsed, setSidebarCollapsed, children} = props
    const sm = useBreakpoint('sm')
    const location = useLocation()

    useEffect(() => {
        if (sm) {
            setSidebarCollapsed(false)
        }
    }, [location])

    if (sm) {
        return <Drawer
            placement="left"
            open={sidebarCollapsed}
            onClose={() => setSidebarCollapsed(false)}
            closeIcon={null}
            styles={{body: {padding: 0, height: '100%', background: '#001529'}}}
            width='200px'>
            {children}
        </Drawer>
    } else {
        return <Layout.Sider
            collapsible
            breakpoint='sm'
            width={220}
            trigger={null}
            collapsed={sidebarCollapsed && sm}
            {...(sm ? {collapsedWidth: 0} : {})}>
            {children}
        </Layout.Sider>
    }
}

export default function App(): React.ReactElement {
    const navigate = useNavigate()
    const location = useLocation()
    const sm = useBreakpoint('sm')

    const [sidebarCollapsed, setSidebarCollapsed] = useState(false)
    const [isAuthenticated, setIsAuthenticated] = useState<boolean | undefined>(undefined)
    const [configuration, setConfiguration] = useState<InstanceConfig | undefined>(undefined)

    const [darkMode, setDarkMode] = useState(false)
    const windowQuery = window.matchMedia('(prefers-color-scheme:dark)')
    const [messageApi, contextHolder] = message.useMessage()


    const authContextValue: AuthContextType = useMemo(() => ({
        isAuthenticated,
        setIsAuthenticated
    }), [isAuthenticated])

    const configContextValue = useMemo(() => ({
        configuration,
    }), [configuration])

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
        getConfiguration().then(configuration => {
            setConfiguration(configuration)

            getUser().then(() => {
                setIsAuthenticated(true)
                if (location.pathname === '/login') navigate('/home')
            }).catch(() => {
                setIsAuthenticated(false)
                const pathname = location.pathname
                if (configuration.publicRdapLookupEnabled) return navigate('/search/domain')
                if (!['/login', '/tos', '/faq', '/privacy'].includes(pathname)) return navigate('/home')
            })
        }).catch(() => messageApi.error(t`Unable to contact the server, please reload the page.`))
    }, [])

    return (
        <ConfigProvider
            theme={{
                algorithm: darkMode ? theme.darkAlgorithm : undefined
            }}
        >
            <ConfigurationContext.Provider value={configContextValue}>
                <AuthenticatedContext.Provider value={authContextValue}>
                    {(configuration?.registerEnabled || configuration?.ssoLogin) && isAuthenticated === false && !['/login', '/home'].includes(location.pathname) &&
                        <Alert
                            type="warning"
                            message={t`Please log in to access all features, monitor domains, and manage your Connectors.`}
                            action={<Link to='/login'><Button>{t`Log in`}</Button></Link>}
                            banner closable/>
                    }
                    <Layout hasSider style={{minHeight: '100vh'}}>
                        <SiderWrapper sidebarCollapsed={sidebarCollapsed} setSidebarCollapsed={setSidebarCollapsed}>
                            <Sider/>
                        </SiderWrapper>
                        <Layout>
                            <Layout.Header style={{padding: 0}}>
                                {sm &&
                                    <Button type="text" style={{marginLeft: 8}}
                                            onClick={() => setSidebarCollapsed(!sidebarCollapsed)}>
                                        <MenuOutlined/>
                                    </Button>
                                }
                            </Layout.Header>
                            <Layout.Content style={sm ? {margin: '24px 0'} : {margin: '24px 16px 0'}}>
                                <div style={{
                                    padding: sm ? 8 : 24,
                                    minHeight: 360
                                }}
                                >
                                    {contextHolder}
                                    <Routes>
                                        <Route path='/' element={<Navigate
                                            to={configuration?.publicRdapLookupEnabled ? '/search/domain' : '/home'}/>}/>
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
                                <Flex gap='middle' wrap justify='center'>
                                    <Link to='/tos' rel='terms-of-service'><Button type='text'>{t`TOS`}</Button></Link>
                                    <Link to='/privacy' rel='privacy-policy'><Button
                                        type='text'>{t`Privacy Policy`}</Button></Link>
                                    <Link to='/faq'><Button type='text'>{t`FAQ`}</Button></Link>
                                    <Button target='_blank'
                                            href='https://domainwatchdog.eu'
                                            type='text'>
                                        {t`Documentation`}
                                    </Button>
                                    <Button target='_blank'
                                            href={PROJECT_LINK}
                                            type='text'>
                                        {t`Source code`}
                                    </Button>
                                    <Button target='_blank'
                                            href={PROJECT_LINK + '/issues'}
                                            type='text'>
                                        {t`Submit an issue`}
                                    </Button>
                                </Flex>
                                <Typography.Paragraph style={{marginTop: '1em'}}>
                                    {jt`${ProjectLink} is an open source project distributed under the ${LicenseLink} license.`}
                                </Typography.Paragraph>
                            </Layout.Footer>
                        </Layout>
                    </Layout>
                </AuthenticatedContext.Provider>
            </ConfigurationContext.Provider>
        </ConfigProvider>
    )
}
