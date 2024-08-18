import {Button, Flex, Modal, Space, Typography} from "antd"
import {t} from "ttag"
import React, {useEffect, useState} from "react"
import {ApartmentOutlined} from "@ant-design/icons"

import '@xyflow/react/dist/style.css'
import {Background, Controls, MiniMap, ReactFlow, useEdgesState, useNodesState} from "@xyflow/react";
import {getWatchlist} from "../../../../utils/api";
import {getLayoutedElements} from "./getLayoutedElements";
import {watchlistToNodes} from "./watchlistToNodes";
import {watchlistToEdges} from "./watchlistToEdges";

export type DiagramConfig = {
    tld?: boolean
    nameserver?: boolean
    entities?: boolean
}

export function ViewDiagramWatchlistButton({token}: { token: string }) {

    const [open, setOpen] = useState(false)
    const [loading, setLoading] = useState(false)
    const [nodes, setNodes, onNodesChange] = useNodesState([])
    const [edges, setEdges, onEdgesChange] = useEdgesState([])

    useEffect(() => {
        setNodes([])
        setEdges([])

        if (!open) return
        setLoading(true)
        getWatchlist(token).then(w => {
            const e = getLayoutedElements(watchlistToNodes(w), watchlistToEdges(w))
            setNodes(e.nodes)
            setEdges(e.edges)
        }).catch(() => setOpen(false)).finally(() => setLoading(false))

    }, [open])


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
            loading={loading}
            footer={
                <Space>
                    <Button type="default" onClick={() => setOpen(false)}>
                        Close
                    </Button>
                </Space>
            }
            onOk={() => setOpen(false)}
            onCancel={() => setOpen(false)}
            width='85vw'
        >
            <Flex style={{width: '80vw', height: '80vh'}}>
                <ReactFlow
                    fitView
                    colorMode='system'
                    nodesConnectable={false}
                    edgesReconnectable={false}
                    nodes={nodes}
                    edges={edges}
                    onNodesChange={onNodesChange}
                    onEdgesChange={onEdgesChange}
                    style={{width: '100%', height: '100vh'}}
                >
                    <MiniMap/>
                    <Controls/>
                    <Background/>
                </ReactFlow>
            </Flex>
        </Modal>
    </>
}