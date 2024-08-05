import {ConnectorProvider} from "../api/connectors";
import {Typography} from "antd";
import {t} from "ttag";
import React from "react";

export const helpGetTokenLink = (provider?: string) => {
    switch (provider) {
        case ConnectorProvider.OVH:
            return <Typography.Link target='_blank'
                                    href="https://api.ovh.com/createToken/index.cgi?GET=/order/cart&GET=/order/cart/*&POST=/order/cart&POST=/order/cart/*&DELETE=/order/cart/*">
                {t`Retrieve a set of tokens from your customer account on the Provider's website`}
            </Typography.Link>
        default:
            return <></>
    }
}

export const tosHyperlink = (provider?: string) => {
    switch (provider) {
        case ConnectorProvider.OVH:
            return 'https://www.ovhcloud.com/fr/terms-and-conditions/contracts/'
        default:
            return ''
    }
}