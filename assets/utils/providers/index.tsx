import {ConnectorProvider} from '../api/connectors'
import {Typography} from 'antd'
import {t} from 'ttag'
import React from 'react'

export const helpGetTokenLink = (provider?: string) => {
    switch (provider) {
        case ConnectorProvider.OVHcloud:
            return (
                <Typography.Link
                    target='_blank'
                    href='https://api.ovh.com/createToken/?GET=/order/cart&GET=/order/cart/*&POST=/order/cart&POST=/order/cart/*&DELETE=/order/cart/*&GET=/domain/extensions'
                >
                    {t`Retrieve a set of tokens from your customer account on the Provider's website`}
                </Typography.Link>
            )

        case ConnectorProvider.Gandi:
            return (
                <Typography.Link target='_blank' href='https://admin.gandi.net/organizations/account/pat'>
                    {t`Retrieve a Personal Access Token from your customer account on the Provider's website`}
                </Typography.Link>
            )
        case ConnectorProvider.Namecheap:
            return (
                <Typography.Link target='_blank' href='https://ap.www.namecheap.com/settings/tools/apiaccess/'>
                    {t`Retreive an API key and whitelist this instance's IP address on Namecheap's website`}
                </Typography.Link>
            )
        case ConnectorProvider.AutoDNS:
            return (
                <Typography.Link target='_blank' href='https://en.autodns.com/domain-robot-api/'>
                    {t`Because of some limitations in API of AutoDNS, we suggest to create an dedicated user for API with limited rights`}
                </Typography.Link>
            )
        case ConnectorProvider['Name.com']:
            return (
                <Typography.Link target='_blank' href='https://www.name.com/account/settings/api'>
                    {t`Retrieve a set of tokens from your customer account on the Provider's website`}
                </Typography.Link>
            )
        default:
            return <></>
    }
}

export const tosHyperlink = (provider?: string) => {
    switch (provider) {
        case ConnectorProvider.OVHcloud:
            return 'https://www.ovhcloud.com/en/terms-and-conditions/contracts/'
        case ConnectorProvider.Gandi:
            return 'https://www.gandi.net/en/contracts/terms-of-service'
        case ConnectorProvider.Namecheap:
            return 'https://www.namecheap.com/legal/universal/universal-tos/'
        case ConnectorProvider.AutoDNS:
            return 'https://www.internetx.com/agb/'
        case ConnectorProvider['Name.com']:
            return 'https://www.name.com/policies/'
        default:
            return ''
    }
}
