import {MessageInstance, MessageType} from "antd/lib/message/interface";
import {AxiosError, AxiosResponse} from "axios";
import {t} from "ttag";
import {Avatar} from "antd";
import {
    BankOutlined,
    ClockCircleOutlined,
    DeleteOutlined,
    DollarOutlined,
    IdcardOutlined,
    LockOutlined,
    ReloadOutlined,
    ShareAltOutlined,
    SignatureOutlined,
    SyncOutlined,
    ToolOutlined,
    UnlockOutlined,
    UserOutlined
} from "@ant-design/icons";
import {Domain, Entity, EventAction} from "./api";
import vCard from "vcf";
import React from "react";


export const roleToAvatar = (e: { roles: string[] }) => <Avatar style={{backgroundColor: rolesToColor(e.roles)}}
                                                                icon={e.roles.includes('registrant') ?
                                                                    <SignatureOutlined/> : e.roles.includes('registrar') ?
                                                                        <BankOutlined/> :
                                                                        e.roles.includes('technical') ?
                                                                            <ToolOutlined/> :
                                                                            e.roles.includes('administrative') ?
                                                                                <IdcardOutlined/> :
                                                                                e.roles.includes('billing') ?
                                                                                    <DollarOutlined/> :
                                                                                    <UserOutlined/>}/>


export const rolesToColor = (roles: string[]) => roles.includes('registrant') ? 'green' :
    roles.includes('administrative') ? 'blue' :
        roles.includes('technical') ? 'orange' :
            roles.includes('registrar') ? 'purple' :
                roles.includes('sponsor') ? 'magenta' :
                    roles.includes('billing') ? 'cyan' : 'default'


export const actionToColor = (a: EventAction) => a === 'registration' ? 'green' :
    a === 'reregistration' ? 'cyan' :
        a === 'expiration' ? 'red' :
            a === 'deletion' ? 'magenta' :
                a === 'transfer' ? 'orange' :
                    a === 'last changed' ? 'blue' :
                        a === 'registrar expiration' ? 'red' :
                            a === 'reinstantiation' ? 'purple' :
                                a === 'enum validation expiration' ? 'red' : 'default'

export const actionToIcon = (a: EventAction) => a === 'registration' ?
    <SignatureOutlined style={{fontSize: '16px'}}/> : a === 'expiration' ?
        <ClockCircleOutlined style={{fontSize: '16px'}}/> : a === 'transfer' ?
            <ShareAltOutlined style={{fontSize: '16px'}}/> : a === 'last changed' ?
                <SyncOutlined style={{fontSize: '16px'}}/> : a === 'deletion' ?
                    <DeleteOutlined style={{fontSize: '16px'}}/> : a === 'reregistration' ?
                        <ReloadOutlined style={{fontSize: '16px'}}/> : a === 'locked' ?
                            <LockOutlined style={{fontSize: '16px'}}/> : a === 'unlocked' ?
                                <UnlockOutlined style={{fontSize: '16px'}}/> : a === 'registrar expiration' ?
                                    <ClockCircleOutlined
                                        style={{fontSize: '16px'}}/> : a === 'enum validation expiration' ?
                                        <ClockCircleOutlined style={{fontSize: '16px'}}/> : a === 'reinstantiation' ?
                                            <ReloadOutlined style={{fontSize: '16px'}}/> : undefined


export const entityToName = (e: { entity: Entity }): string => {
    const jCard = vCard.fromJSON(e.entity.jCard)
    let name = e.entity.handle
    if (jCard.data.fn && !Array.isArray(jCard.data.fn) && jCard.data.fn.valueOf() !== '') name = jCard.data.fn.valueOf()
    return name
}

export const sortDomainEntities = (domain: Domain) => domain.entities.sort((e1, e2) => {
    const p = (r: string[]) => r.includes('registrant') ? 5 :
        r.includes('administrative') ? 4 :
            r.includes('billing') ? 3 :
                r.includes('registrar') ? 2 : 1
    return p(e2.roles) - p(e1.roles)
})


export function showErrorAPI(e: AxiosError, messageApi: MessageInstance): MessageType | undefined {

    const response = e.response as AxiosResponse
    const data = response.data

    if ('message' in data) {
        return messageApi.error(data.message as string)
    }

    if (!('detail' in data)) return
    const detail = data.detail as string

    if (response.status === 429) {
        const duration = response.headers['retry-after']
        return messageApi.error(t`Please retry after ${duration} seconds`)
    }

    if (response.status.toString()[0] === '4') {
        return messageApi.warning(detail !== '' ? detail : t`An error occurred`)
    }

    return messageApi.error(detail !== '' ? detail : t`An error occurred`)
}