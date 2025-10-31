import React, {createContext, useEffect, useState} from 'react'
import {Button, Card} from 'antd'
import {t} from 'ttag'
import TextPage from './TextPage'
import {LoginForm} from '../components/LoginForm'
import type { InstanceConfig} from '../utils/api'
import {getConfiguration} from '../utils/api'
import {RegisterForm} from '../components/RegisterForm'
import useBreakpoint from "../hooks/useBreakpoint";

export const AuthenticatedContext = createContext<
    {
        authenticated: (authenticated: boolean) => void
        setIsAuthenticated: React.Dispatch<React.SetStateAction<boolean>>
    }
>({
    authenticated: () => {
    },
    setIsAuthenticated: () => {
    }
})

export default function LoginPage() {
    const [wantRegister, setWantRegister] = useState<boolean>(false)
    const [configuration, setConfiguration] = useState<InstanceConfig>()
    const md = useBreakpoint('md')

    const toggleWantRegister = () => {
        setWantRegister(!wantRegister)
    }

    useEffect(() => {
        getConfiguration().then(setConfiguration)
    }, [])

    const grid = [
        <Card.Grid style={{width: md ? '100%' : '50%', textAlign: 'center'}} hoverable={false}>
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
        <Card.Grid style={{width: md ? '100%' : '50%'}} hoverable={false}>
            <TextPage resource='ads.md'/>
        </Card.Grid>
    ];

    if (md) {
        grid.reverse();
    }

    return (
        <Card title={wantRegister ? t`Register` : t`Log in`} style={{width: '100%'}}>
            {grid}
        </Card>
    )
}
