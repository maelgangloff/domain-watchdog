import {Tag} from "antd";
import {DeleteOutlined, ExclamationCircleOutlined} from "@ant-design/icons";
import punycode from "punycode/punycode";
import {Link} from "react-router-dom";
import React from "react";

export function DomainToTag({domain}: { domain: { ldhName: string, deleted: boolean, status: string[] } }) {
    return <Link to={'/search/domain/' + domain.ldhName}>
        <Tag
            color={
                domain.deleted ? 'magenta' :
                    domain.status.includes('redemption period') ? 'yellow' :
                        domain.status.includes('pending delete') ? 'volcano' : 'default'
            }
            icon={
                domain.deleted ? <DeleteOutlined/> :
                    domain.status.includes('redemption period') ? <ExclamationCircleOutlined/> :
                        domain.status.includes('pending delete') ? <DeleteOutlined/> : null
            }>{punycode.toUnicode(domain.ldhName)}</Tag>
    </Link>
}