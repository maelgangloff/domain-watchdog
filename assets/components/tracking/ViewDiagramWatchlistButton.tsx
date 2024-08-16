import {Button, Flex, Modal, Space, Typography} from "antd"
import {t} from "ttag"
import React, {useEffect, useState} from "react"
import {ApartmentOutlined} from "@ant-design/icons"

import '@xyflow/react/dist/style.css'
import {Background, Controls, MiniMap, ReactFlow, useEdgesState, useNodesState} from "@xyflow/react";
import {getWatchlist, Watchlist} from "../../utils/api";

import dagre from 'dagre'
import vCard from "vcf";

const dagreGraph = new dagre.graphlib.Graph();
dagreGraph.setDefaultEdgeLabel(() => ({}));

const nodeWidth = 172;
const nodeHeight = 200;

const getLayoutedElements = (nodes: any, edges: any, direction = 'TB') => {
    const isHorizontal = direction === 'LR';
    dagreGraph.setGraph({rankdir: direction});

    nodes.forEach((node: any) => {
        dagreGraph.setNode(node.id, {width: nodeWidth, height: nodeHeight});
    });

    edges.forEach((edge: any) => {
        dagreGraph.setEdge(edge.source, edge.target);
    });

    dagre.layout(dagreGraph);

    const newNodes = nodes.map((node: any) => {
        const nodeWithPosition = dagreGraph.node(node.id)

        return {
            ...node,
            targetPosition: isHorizontal ? 'left' : 'top',
            sourcePosition: isHorizontal ? 'right' : 'bottom',
            position: {
                x: nodeWithPosition.x - nodeWidth / 2,
                y: nodeWithPosition.y - nodeHeight / 2
            },
        };
    });

    return {nodes: newNodes, edges};
}


function watchlistToNodes(watchlist: Watchlist) {
    const domains = watchlist.domains.map(d => ({
        id: d.ldhName,
        data: {label: <b>{d.ldhName}</b>},
        style: {
            width: 200
        }
    }))
    const entities = [...new Set(watchlist.domains
        .map(d => d.entities
            .filter(e => !e.roles.includes('registrar'))
            .map(e => e.entity
            )
        ).flat())].map(e => {
        const jCard = vCard.fromJSON(e.jCard)
        let label = e.handle
        if (jCard.data.fn !== undefined && !Array.isArray(jCard.data.fn)) label = jCard.data.fn.valueOf()

        return {
            id: e.handle,
            data: {label},
            style: {
                width: 200
            }
        }
    })

    return [...domains, ...entities]
}

const rolesToColor = (roles: string[]) => roles.includes('registrant') ? 'green' :
    roles.includes('technical') ? 'orange' : 'black'

function watchlistToEdges(watchlist: Watchlist) {
    return watchlist.domains
        .map(d => d.entities
            .filter(e => !e.roles.includes('registrar'))
            .map(e => ({
                id: `${d.ldhName}-${e.entity.handle}`,
                source: e.roles.includes('technical') ? d.ldhName : e.entity.handle,
                target: e.roles.includes('technical') ? e.entity.handle : d.ldhName,
                style: {stroke: rolesToColor(e.roles), strokeWidth: 3},
                animated: e.roles.includes('registrant'),
            }))
        ).flat(2)
}

export function ViewDiagramWatchlistButton({token}: { token: string }) {
    const [open, setOpen] = useState(false)

    const [loading, setLoading] = useState(false)
    const [nodes, setNodes, onNodesChange] = useNodesState([]);
    const [edges, setEdges, onEdgesChange] = useEdgesState([]);

    useEffect(() => {
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
            width='80vw'
        >
            {nodes && edges && <Flex style={{width: '75vw', height: '80vh'}}>
                <ReactFlow
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
            </Flex>}
        </Modal>
    </>
}