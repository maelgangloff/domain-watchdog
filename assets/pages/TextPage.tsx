import React, {useEffect, useState} from "react";
import snarkdown from "snarkdown"
import {Skeleton, Typography} from "antd";
import axios from "axios";
import {t} from "ttag";

export default function TextPage({resource}: { resource: string }) {
    const [loading, setLoading] = useState<boolean>(false)
    const [markdown, setMarkdown] = useState<string | undefined>(undefined)

    useEffect(() => {
        setLoading(true)
        axios.get('/content/' + resource)
            .then(res => setMarkdown(res.data))
            .catch(err => {
                console.error(err)
                setMarkdown(undefined)
            })
            .finally(() => setLoading(false))
    }, [resource])

    return <Skeleton loading={loading} active>
        {markdown !== undefined ? <div
                dangerouslySetInnerHTML={{__html: snarkdown(markdown)}}></div> :
            <Typography.Text strong>
                {t`📝 Please create the /public/content/${resource} file.`}
            </Typography.Text>}
    </Skeleton>
}