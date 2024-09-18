import {AxiosError, AxiosResponse} from "axios";
import {MessageInstance, MessageType} from "antd/lib/message/interface";
import {t} from "ttag";

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