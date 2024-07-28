import React, {useEffect, useState} from "react";
import {Card, Flex, Skeleton, Typography} from "antd";
import {getUser, User} from "../../utils/api";
import {t} from 'ttag'

export default function UserPage() {

    const [user, setUser] = useState<User | null>(null)

    useEffect(() => {
        getUser().then(setUser)
    }, [])

    return <Skeleton loading={user === null} active>
        <Flex gap="middle" align="center" justify="center" vertical>
            <Card title={t`My Account`}>
                <Typography.Paragraph>
                    {t`Username`} : {user?.email}
                </Typography.Paragraph>
                <Typography.Paragraph>
                    {t`Roles`} : {user?.roles.join(',')}
                </Typography.Paragraph>
            </Card>
        </Flex>
    </Skeleton>
}