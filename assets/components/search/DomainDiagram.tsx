import React, {useEffect} from "react";
import {Background, Controls, MiniMap, ReactFlow, useEdgesState, useNodesState} from "@xyflow/react";
import {Flex} from "antd";
import {Domain} from "../../utils/api";
import {getLayoutedElements} from "../tracking/watchlist/diagram/getLayoutedElements";
import {domainEntitiesToNode, domainToNode, nsToNode, tldToNode} from "../tracking/watchlist/diagram/watchlistToNodes";
import {domainEntitiesToEdges, domainNSToEdges, tldToEdge} from "../tracking/watchlist/diagram/watchlistToEdges";

export function DomainDiagram({domain}: { domain: Domain }) {
    const [nodes, setNodes, onNodesChange] = useNodesState([])
    const [edges, setEdges, onEdgesChange] = useEdgesState([])


    useEffect(() => {
        const e = getLayoutedElements([
            domainToNode(domain),
            ...domainEntitiesToNode(domain, true),
            tldToNode(domain.tld),
            ...domain.nameservers.map(nsToNode)
        ].flat(), [
            domainEntitiesToEdges(domain, true),
            tldToEdge(domain),
            ...domainNSToEdges(domain)
        ].flat())

        setNodes(e.nodes)
        setEdges(e.edges)
    }, [])

    return <Flex style={{width: '100%', height: '100vh'}}>
        <ReactFlow
            fitView
            colorMode='dark'
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
}