import React, {useEffect, useState} from "react";
import snarkdown from "snarkdown"
import {Skeleton} from "antd";
import axios from "axios";

export default function TextPage({resource}: { resource: string }) {
    const [markdown, setMarkdown] = useState<string>()

    useEffect(() => {
        console.log('heyyy')
        axios.get('/content/' + resource).then(res => setMarkdown(res.data))
    }, [resource])

    return <Skeleton loading={markdown === undefined} active>
        {markdown !== undefined && <div dangerouslySetInnerHTML={{__html: snarkdown(markdown)}}></div>}
    </Skeleton>
}