import vCard from "vcf";
import {Avatar, List, Tag, Tooltip} from "antd";
import {BankOutlined, IdcardOutlined, SignatureOutlined, ToolOutlined, UserOutlined} from "@ant-design/icons";
import React from "react";
import {Domain} from "../../utils/api";
import {rdapRoleDetailTranslation, rdapRoleTranslation} from "./rdapTranslation";
import {rolesToColor} from "../tracking/watchlist/diagram/watchlistToEdges";


export function EntitiesList({domain}: { domain: Domain }) {
    const rdapRoleTranslated = rdapRoleTranslation()
    const rdapRoleDetailTranslated = rdapRoleDetailTranslation()

    return <List
        className="demo-loadmore-list"
        itemLayout="horizontal"
        dataSource={domain.entities.sort((e1, e2) => {
            const p = (r: string[]) => r.includes('registrant') ? 4 : r.includes('administrative') ? 3 : r.includes('billing') ? 2 : 1
            return p(e2.roles) - p(e1.roles)
        })}
        renderItem={(e) => {
            const jCard = vCard.fromJSON(e.entity.jCard)
            let name = ''
            if (jCard.data.fn !== undefined && !Array.isArray(jCard.data.fn)) name = jCard.data.fn.valueOf()

            return <List.Item>
                <List.Item.Meta
                    avatar={<Avatar style={{backgroundColor: '#87d068'}}
                                    icon={e.roles.includes('registrant') ?
                                        <SignatureOutlined/> : e.roles.includes('registrar') ?
                                            <BankOutlined/> :
                                            e.roles.includes('technical') ?
                                                <ToolOutlined/> :
                                                e.roles.includes('administrative') ?
                                                    <IdcardOutlined/> :
                                                    <UserOutlined/>}/>}
                    title={e.entity.handle}
                    description={name}
                />
                {e.roles.map((r) =>
                    <Tooltip
                        title={r in rdapRoleDetailTranslated ? rdapRoleDetailTranslated[r as keyof typeof rdapRoleDetailTranslated] : undefined}>
                        <Tag
                            color={rolesToColor([r])}>{rdapRoleTranslated[r as keyof typeof rdapRoleTranslated]}</Tag>
                    </Tooltip>)}
            </List.Item>
        }}
    />
}