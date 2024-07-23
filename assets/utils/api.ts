import axios, {AxiosRequestConfig, AxiosResponse} from "axios";


export async function login(email: string, password: string): Promise<boolean> {
    const response = await request({
        method: 'POST',
        url: 'login',
        data: {email, password}
    })
    return response.status === 200
}

export async function getUser(): Promise<object> {
    const response = await request({
        url: 'me'
    })
    return response.data
}


async function request<T = any, R = AxiosResponse<T>, D = any>(config: AxiosRequestConfig): Promise<R> {
    const axiosConfig: AxiosRequestConfig = {
        ...config,
        baseURL: '/api',
        withCredentials: true,
        headers: {
            Accept: 'application/ld+json'
        }
    }
    return await axios.request<T, any, D>(axiosConfig)
}