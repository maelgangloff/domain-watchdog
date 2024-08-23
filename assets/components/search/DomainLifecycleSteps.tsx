import {StepProps, Steps, Tooltip} from "antd";
import React from "react";
import {t} from "ttag";
import {CheckOutlined, DeleteOutlined, ExclamationCircleOutlined, SignatureOutlined} from "@ant-design/icons";
import {rdapEventDetailTranslation, rdapStatusCodeDetailTranslation} from "../../utils/functions/rdapTranslation";

export function DomainLifecycleSteps({status}: { status: string[] }) {

    const rdapEventDetailTranslated = rdapEventDetailTranslation()
    const rdapStatusCodeDetailTranslated = rdapStatusCodeDetailTranslation()


    const steps: StepProps[] = [
        {
            title: <Tooltip title={rdapEventDetailTranslated.registration}>{t`Registration`}</Tooltip>,
            icon: <SignatureOutlined style={{color: 'green'}}/>
        },
        {
            title: <Tooltip title={rdapStatusCodeDetailTranslated.active}>{t`Active`}</Tooltip>,
            icon: <CheckOutlined/>
        },
        {
            title: <Tooltip
                title={rdapStatusCodeDetailTranslated["redemption period"]}>{t`Redemption Period`}</Tooltip>,
            icon: <ExclamationCircleOutlined style={{color: 'orangered'}}/>
        },
        {
            title: <Tooltip title={rdapStatusCodeDetailTranslated["pending delete"]}>{t`Pending Delete`}</Tooltip>,
            icon: <DeleteOutlined style={{color: 'palevioletred'}}/>
        }
    ]

    let currentStep = 1

    if (status.includes('redemption period')) {
        currentStep = 2
    } else if (status.includes('pending delete')) {
        currentStep = 3
    }

    return <Steps
        current={currentStep}
        items={steps}
    />
}