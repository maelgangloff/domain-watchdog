import {Result} from 'antd'
import React from 'react'
import {t} from 'ttag'

export default function NotFoundPage() {
    return (
        <Result
            status='404'
            title='404'
            subTitle={t`Sorry, the page you visited does not exist.`}
        />
    )
}
