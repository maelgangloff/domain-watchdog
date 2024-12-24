import {Card, Flex} from "antd";
import {t} from "ttag";
import {TrackedDomainTable} from "../../components/tracking/watchlist/TrackedDomainTable";
import React from "react";

export default function TrackedDomainPage() {

    return <Flex gap="middle" align="center" justify="center" vertical>
        <Card title={t`Tracked domain names`}
              style={{width: '100%', height: '80vh'}}>
            <TrackedDomainTable/>
        </Card>
    </Flex>
}