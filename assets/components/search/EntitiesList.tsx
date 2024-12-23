import {List, Tag, Tooltip, Typography} from "antd";
import React from "react";
import {Domain} from "../../utils/api";
import {rdapRoleDetailTranslation, rdapRoleTranslation} from "../../utils/functions/rdapTranslation";
import {roleToAvatar} from "../../utils/functions/roleToAvatar";
import {rolesToColor} from "../../utils/functions/rolesToColor";
import {sortDomainEntities} from "../../utils/functions/sortDomainEntities";
import {extractDetailsFromJCard} from "../../utils/functions/extractDetailsFromJCard";

export function EntitiesList({domain}: { domain: Domain }) {
    const rdapRoleTranslated = rdapRoleTranslation()
    const rdapRoleDetailTranslated = rdapRoleDetailTranslation()

    const roleToTag = (r: string) => <Tooltip
        title={r in rdapRoleDetailTranslated ? rdapRoleDetailTranslated[r as keyof typeof rdapRoleDetailTranslated] : undefined}>
        <Tag color={rolesToColor([r])}>{
            r in rdapRoleTranslated ? rdapRoleTranslated[r as keyof typeof rdapRoleTranslated] : r
        }</Tag>
    </Tooltip>

    return <List
        className="demo-loadmore-list"
        itemLayout="horizontal"
        dataSource={sortDomainEntities(domain)}
        renderItem={(e) => {
            const details = extractDetailsFromJCard(e)

            return <List.Item>
                <List.Item.Meta
                    avatar={roleToAvatar(e)}
                    title={<Typography.Text code>{e.entity.handle}</Typography.Text>}
                    description={<>
                        {details.fn && <div>👤 {details.fn}</div>}
                        {details.organization && <div>🏢 {details.organization}</div>}
                    </>}
                />
                {e.roles.map(roleToTag)}
            </List.Item>
        }
        }
    />
}