import React, { useContext, useEffect, useState} from 'react'
import {Button, Card} from 'antd'
import {t} from 'ttag'
import TextPage from './TextPage'
import {LoginForm} from '../components/LoginForm'
import {RegisterForm} from '../components/RegisterForm'
import useBreakpoint from "../hooks/useBreakpoint"
import {ConfigurationContext} from "../contexts"

export default function LoginPage() {
    const [wantRegister, setWantRegister] = useState<boolean>(false)
    const { configuration } = useContext(ConfigurationContext)

    const md = useBreakpoint('md')

    const toggleWantRegister = () => {
        setWantRegister(!wantRegister)
    }

    useEffect(() => {
        if(!configuration?.registerEnabled && configuration?.ssoLogin && configuration?.ssoAutoRedirect) {
            window.location.href = '/login/oauth'
            return
        }
    }, [configuration])

    const grid = [
        <Card.Grid key="form" style={{width: md ? '100%' : '50%', textAlign: 'center'}} hoverable={false}>
            {wantRegister ? <RegisterForm/> : <LoginForm ssoLogin={configuration?.ssoLogin}/>}
            {
                configuration?.registerEnabled &&
                <Button
                    type='link'
                    block
                    style={{marginTop: '1em'}}
                    onClick={toggleWantRegister}
                >{wantRegister ? t`Log in` : t`Create an account`}
                </Button>
            }
        </Card.Grid>,
        <Card.Grid key="ads" style={{width: md ? '100%' : '50%'}} hoverable={false}>
            <TextPage resource='ads.md'/>
        </Card.Grid>
    ]

    if (md) {
        grid.reverse()
    }

    return (
        <Card title={wantRegister ? t`Register` : t`Log in`} style={{width: '100%'}}>
            {grid}
        </Card>
    )
}
