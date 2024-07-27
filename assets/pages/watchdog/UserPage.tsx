import React, {useEffect, useState} from "react";
import {Card, Flex, Skeleton, Typography} from "antd";
import {getUser, User} from "../../utils/api";

export default function UserPage() {

    const [user, setUser] = useState<User | null>(null)

    useEffect(() => {
        getUser().then(setUser)
    }, [])

    return <Skeleton loading={user === null} active>
        <Flex gap="middle" align="center" justify="center" vertical>
            <Card title="My Account">
                <Typography.Paragraph>
                    Username : {user?.email}
                </Typography.Paragraph>
                <Typography.Paragraph>
                    Roles : {user?.roles.join(',')}
                </Typography.Paragraph>
            </Card>
        </Flex>
    </Skeleton>
}