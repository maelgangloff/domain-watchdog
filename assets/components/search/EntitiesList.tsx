import {Flex, List, Tag, Tooltip} from 'antd'
import React from 'react'
import type {Domain} from '../../utils/api'
import {
    icannAccreditationTranslation,
    rdapRoleDetailTranslation,
    rdapRoleTranslation
} from '../../utils/functions/rdapTranslation'
import {roleToAvatar} from '../../utils/functions/roleToAvatar'
import {rolesToColor} from '../../utils/functions/rolesToColor'
import {sortDomainEntities} from '../../utils/functions/sortDomainEntities'
import {extractDetailsFromJCard} from '../../utils/functions/extractDetailsFromJCard'
import {CheckCircleOutlined, CloseCircleOutlined, SettingOutlined} from "@ant-design/icons"

export function EntitiesList({domain}: { domain: Domain }) {
    const rdapRoleTranslated = rdapRoleTranslation()
    const rdapRoleDetailTranslated = rdapRoleDetailTranslation()

    const roleToTag = (r: string) => <Tooltip
        key={r}
        title={rdapRoleDetailTranslated[r as keyof typeof rdapRoleDetailTranslated] || undefined}
    >
        <Tag key={r} color={rolesToColor([r])}>
            {rdapRoleTranslated[r as keyof typeof rdapRoleTranslated] || r}
        </Tag>
    </Tooltip>

    return (
        <List
            className='demo-loadmore-list'
            itemLayout='horizontal'
            dataSource={sortDomainEntities(domain)}
            renderItem={(e) => {
                const details = extractDetailsFromJCard(e)
                const icannAccreditationTranslated = icannAccreditationTranslation()

                const status  = e.entity.icannAccreditation?.status as ('Terminated' | 'Accredited' | 'Reserved' | undefined)

                return <List.Item>
                    <List.Item.Meta
                        avatar={roleToAvatar(e)}
                        title={<Flex gap='small'>
                            <Tag>{e.entity.handle}</Tag>
                            {
                                e.entity.icannAccreditation && status && <Tooltip
                                    title={e.entity.icannAccreditation.registrarName + " (" + icannAccreditationTranslated[status] + ")"}>
                                    <Tag icon={
                                        status === 'Terminated' ? <CloseCircleOutlined /> :
                                            status === 'Accredited' ? <CheckCircleOutlined/> : <SettingOutlined/>
                                    } color={
                                        status === 'Terminated' ? 'red' :
                                            status === 'Accredited' ? 'green' : 'yellow'
                                    }>{e.entity.icannAccreditation.id}</Tag>
                                </Tooltip>
                            }
                        </Flex>}
                        description={<>
                            {details.fn && <div>👤 {details.fn}</div>}
                            {details.organization && <div>🏢 {details.organization}</div>}
                        </>}
                    />
                    <Flex gap='4px 0' wrap>
                        {e.roles.map(roleToTag)}
                    </Flex>
                </List.Item>
            }}
        />
    )
}
