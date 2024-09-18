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

export function ViewDiagramWatchlistButton({token}: { token: string }) {

    const [open, setOpen] = useState(false)
    const [loading, setLoading] = useState(false)
    const [nodes, setNodes, onNodesChange] = useNodesState([])
    const [edges, setEdges, onEdgesChange] = useEdgesState([])

    useEffect(() => {
        setEdges([])
        setNodes([])
    }, [])

    useEffect(() => {
        if (!open) return
        setLoading(true)
        getWatchlist(token).then(w => {
            const e = getLayoutedElements(watchlistToNodes(w, true), watchlistToEdges(w, true))
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
            width='90vw'
            height='100%'
        >
            <Flex style={{width: '85vw', height: '85vh'}}>
                <ReactFlow
                    fitView
                    colorMode='dark'
                    defaultEdges={[]}
                    defaultNodes={[]}
                    nodesConnectable={false}
                    edgesReconnectable={false}
                    nodes={nodes}
                    edges={edges}
                    onNodesChange={onNodesChange}
                    onEdgesChange={onEdgesChange}
                    style={{width: '100%', height: '100%'}}
                >
                    <MiniMap/>
                    <Controls/>
                    <Background/>
                </ReactFlow>
            </Flex>
        </Modal>
    </>
}
