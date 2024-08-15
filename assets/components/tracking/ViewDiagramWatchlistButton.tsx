import {Button, Modal, Space, Typography} from "antd"
import {t} from "ttag"
import React, {useState} from "react"
import {Watchlist} from "../../pages/tracking/WatchlistPage"
import {ApartmentOutlined} from "@ant-design/icons"

export function ViewDiagramWatchlistButton({watchlist}: { watchlist: Watchlist }) {
    const [open, setOpen] = useState(false)

    return <>
        <Typography.Link>
            <ApartmentOutlined title={t`View the Watchlist Entity Diagram`}
                               style={{color: 'darkviolet'}}
                               onClick={() => setOpen(true)}/>
        </Typography.Link>
        <Modal
            title={t`Watchlist Entity Diagram`}
            centered
            open={open}
            footer={
                <Space>
                    <Button type="primary" color='violet' onClick={() => {
                    }}>
                        Download
                    </Button>

                    <Button type="default" onClick={() => setOpen(false)}>
                        Close
                    </Button>
                </Space>
            }
            onOk={() => setOpen(false)}
            onCancel={() => setOpen(false)}
            width='80%'
        >

        </Modal>
    </>

}