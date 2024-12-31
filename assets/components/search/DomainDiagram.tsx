import React, {useEffect} from 'react'
import type { Edge, Node} from '@xyflow/react'
import {Background, Controls, MiniMap, ReactFlow, useEdgesState, useNodesState} from '@xyflow/react'
import {Flex} from 'antd'
import type {Domain} from '../../utils/api'
import {getLayoutedElements} from '../tracking/watchlist/diagram/getLayoutedElements'
import {domainEntitiesToNode, domainToNode, nsToNode, tldToNode} from '../tracking/watchlist/diagram/watchlistToNodes'
import {domainEntitiesToEdges, domainNSToEdges, tldToEdge} from '../tracking/watchlist/diagram/watchlistToEdges'

export function DomainDiagram({domain}: { domain: Domain }) {
    const [nodes, setNodes, onNodesChange] = useNodesState<Node>([])
    const [edges, setEdges, onEdgesChange] = useEdgesState<Edge>([])

    useEffect(() => {
        const nodes = [
            domainToNode(domain),
            ...domainEntitiesToNode(domain, true),
            ...domain.nameservers.map(nsToNode)
        ].flat()
        const edges = [
            domainEntitiesToEdges(domain, true),
            ...domainNSToEdges(domain)
        ].flat()

        if (domain.tld.tld !== '.') {
            nodes.push(tldToNode(domain.tld))
            edges.push(tldToEdge(domain))
        }

        const e = getLayoutedElements(nodes, edges)

        setNodes(e.nodes)
        setEdges(e.edges)
    }, [])

    return (
        <Flex style={{width: '100%', height: '100vh'}}>
            <ReactFlow
                fitView
                colorMode='system'
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
    )
}
