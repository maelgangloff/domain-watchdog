import type React from 'react'
import {createContext} from 'react'
import type {InstanceConfig} from "../utils/api"


export type ConfigurationContextType = {
    configuration: InstanceConfig | undefined
}

export const ConfigurationContext = createContext<ConfigurationContextType>({
    configuration: undefined,
})


export type AuthContextType = {
    isAuthenticated: boolean
    setIsAuthenticated: React.Dispatch<React.SetStateAction<boolean>>
}

export const AuthenticatedContext = createContext<AuthContextType>({
    isAuthenticated: false,
    setIsAuthenticated: () => {
    },
})