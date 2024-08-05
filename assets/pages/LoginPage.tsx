import React, {createContext, useEffect, useState} from "react";
import {Button, Card} from "antd";
import {t} from 'ttag'
import TextPage from "./TextPage";
import {LoginForm} from "../components/LoginForm";
import {getConfiguration, InstanceConfig} from "../utils/api";
import {RegisterForm} from "../components/RegisterForm";


const gridStyle: React.CSSProperties = {
    width: '50%',
    textAlign: 'center',
}

export const AuthenticatedContext = createContext<any>(null)

export default function LoginPage() {

    const [wantRegister, setWantRegister] = useState<boolean>(false)
    const [configuration, setConfiguration] = useState<InstanceConfig>()

    const toggleWantRegister = () => {
        setWantRegister(!wantRegister)
    }

    useEffect(() => {
        getConfiguration().then(setConfiguration)
    }, [])

    return <Card title={wantRegister ? t`Register` : t`Log in`} style={{width: '100%'}}>
        <Card.Grid style={gridStyle} hoverable={false}>
            {wantRegister ? <RegisterForm/> : <LoginForm ssoLogin={configuration?.ssoLogin}/>}
            {
                configuration?.registerEnabled &&
                <Button type='link'
                        block
                        style={{marginTop: '1em'}}
                        onClick={toggleWantRegister}>{wantRegister ? t`Log in` : t`Create an account`}</Button>
            }
        </Card.Grid>
        <Card.Grid style={gridStyle} hoverable={false}>
            <TextPage resource='ads.md'/>
        </Card.Grid>
    </Card>
}