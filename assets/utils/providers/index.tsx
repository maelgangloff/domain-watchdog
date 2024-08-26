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

        case ConnectorProvider.GANDI:
            return <Typography.Link target='_blank' href="https://admin.gandi.net/organizations/account/pat">
                {t`Retrieve a Personal Access Token from your customer account on the Provider's website`}
            </Typography.Link>
        case ConnectorProvider.NAMECHEAP:
            return <Typography.Link target='_blank' href="https://ap.www.namecheap.com/settings/tools/apiaccess/">
                {t`Retreive an API key and whitelist this instance's IP address on Namecheap's website`}
            </Typography.Link>
        default:
            return <></>

    }
}

export const tosHyperlink = (provider?: string) => {
    switch (provider) {
        case ConnectorProvider.OVH:
            return 'https://www.ovhcloud.com/fr/terms-and-conditions/contracts/'
        case ConnectorProvider.GANDI:
            return 'https://www.gandi.net/en/contracts/terms-of-service'
        default:
            return ''
    }
}