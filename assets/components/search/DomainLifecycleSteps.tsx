import {StepProps, Steps, Tooltip} from "antd";
import React from "react";
import {t} from "ttag";
import {
    CheckOutlined,
    DeleteOutlined,
    ExclamationCircleOutlined,
    ReloadOutlined,
    SignatureOutlined
} from "@ant-design/icons";
import {rdapEventDetailTranslation, rdapStatusCodeDetailTranslation} from "../../utils/functions/rdapTranslation";

export function DomainLifecycleSteps({status}: { status: string[] }) {

    const rdapEventDetailTranslated = rdapEventDetailTranslation()
    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()


    const steps: StepProps[] = [
        {
            title: <Tooltip title={rdapEventDetailTranslated.registration}>{t`Registration`}</Tooltip>,
            icon: <SignatureOutlined/>
        },
        {
            title: <Tooltip title={rdapStatusCodeDetailTranslated.active}>{t`Active`}</Tooltip>,
            icon: <CheckOutlined/>
        },
        {
            title: <Tooltip title={rdapStatusCodeDetailTranslated["renew period"]}>{t`Renew Period`}</Tooltip>,
            icon: <ReloadOutlined/>
        },
        {
            title: <Tooltip
                title={rdapStatusCodeDetailTranslated["redemption period"]}>{t`Redemption Period`}</Tooltip>,
            icon: <ExclamationCircleOutlined/>
        },
        {
            title: <Tooltip title={rdapStatusCodeDetailTranslated["pending delete"]}>{t`Pending Delete`}</Tooltip>,
            icon: <DeleteOutlined/>
        }
    ]

    let currentStep = 1

    if (status.includes('redemption period')) {
        currentStep = 4
    } else if (status.includes('pending delete')) {
        currentStep = 5
    }

    return <Steps
        current={currentStep}
        items={steps}
    />
}