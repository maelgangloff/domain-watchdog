import React, {useEffect, useState} from "react";
import {Empty, Flex, FormProps, message, Skeleton} from "antd";
import {Domain, getDomain} from "../../utils/api";
import {AxiosError} from "axios"
import {t} from 'ttag'
import {DomainSearchBar, FieldType} from "../../components/search/DomainSearchBar";
import {DomainResult} from "../../components/search/DomainResult";
import {showErrorAPI} from "../../utils/functions/showErrorAPI";
import {useNavigate, useParams} from "react-router-dom";

export default function DomainSearchPage() {
    const [domain, setDomain] = useState<Domain | null>()
    const [messageApi, contextHolder] = message.useMessage()
    const navigate = useNavigate()

    const {query} = useParams()

    const onFinish: FormProps<FieldType>['onFinish'] = (values) => {
        navigate('/search/domain/' + values.ldhName)
    }

    useEffect(() => {
        if (query === undefined) return

        setDomain(null)
        getDomain(query).then(d => {
            setDomain(d)
            messageApi.success(t`Found !`)
        }).catch((e: AxiosError) => {
            setDomain(undefined)
            showErrorAPI(e, messageApi)
        })
    }, [query])

    return <Flex gap="middle" align="center" justify="center" vertical>
        {contextHolder}
        <DomainSearchBar onFinish={onFinish}/>

        <Skeleton loading={domain === null} active>
            {
                domain &&
                (!domain.deleted ? <DomainResult domain={domain}/>
                    : <Empty
                        description={t`Although the domain exists in my database, it has been deleted from the WHOIS by its registrar.`}/>)
            }
        </Skeleton>
    </Flex>
}