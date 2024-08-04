import {InstanceConfig, request, User} from "./index";


export async function login(email: string, password: string): Promise<boolean> {
    const response = await request({
        method: 'POST',
        url: 'login',
        data: {email, password}
    })
    return response.status === 200
}

export async function getUser(): Promise<User> {
    const response = await request<User>({
        url: 'me'
    })
    return response.data
}

export async function getConfiguration(): Promise<InstanceConfig> {
    const response = await request<InstanceConfig>({
        url: 'config'
    })
    return response.data
}