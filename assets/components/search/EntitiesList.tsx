import {List, Tag, Tooltip} from "antd";
import React from "react";
import {Domain} from "../../utils/api";
import {rdapRoleDetailTranslation, rdapRoleTranslation} from "./rdapTranslation";
import {entityToName, rolesToColor, roleToAvatar, sortDomainEntities} from "../../utils";


export function EntitiesList({domain}: { domain: Domain }) {
    const rdapRoleTranslated = rdapRoleTranslation()
    const rdapRoleDetailTranslated = rdapRoleDetailTranslation()

    const roleToTag = (r: string) => <Tooltip
        title={r in rdapRoleDetailTranslated ? rdapRoleDetailTranslated[r as keyof typeof rdapRoleDetailTranslated] : undefined}>
        <Tag
            color={rolesToColor([r])}>{rdapRoleTranslated[r as keyof typeof rdapRoleTranslated]}</Tag>
    </Tooltip>

    return <List
        className="demo-loadmore-list"
        itemLayout="horizontal"
        dataSource={sortDomainEntities(domain)}
        renderItem={(e) =>
            <List.Item>
                <List.Item.Meta
                    avatar={roleToAvatar(e)}
                    title={e.entity.handle}
                    description={entityToName(e)}
                />
                {e.roles.map(roleToTag)}
            </List.Item>
        }
    />
}