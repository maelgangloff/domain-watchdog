import {EventAction} from "../api";
import {
    ClockCircleOutlined,
    DeleteOutlined,
    LockOutlined,
    PushpinOutlined,
    ReloadOutlined,
    ShareAltOutlined,
    SignatureOutlined,
    SyncOutlined,
    UnlockOutlined
} from "@ant-design/icons";
import React from "react";

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
                                            <ReloadOutlined style={{fontSize: '16px'}}/> :
                                            <PushpinOutlined style={{fontSize: '16px'}}/>