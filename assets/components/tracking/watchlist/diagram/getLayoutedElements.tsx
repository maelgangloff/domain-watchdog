import dagre from 'dagre'
import type {Edge, Node} from '@xyflow/react'
import { Position} from '@xyflow/react'

export const getLayoutedElements = (nodes: Node[], edges: Edge[], direction = 'TB') => {
    const dagreGraph = new dagre.graphlib.Graph()
    dagreGraph.setDefaultEdgeLabel(() => ({}))

    const nodeWidth = 172
    const nodeHeight = 200

    const isHorizontal = direction === 'LR'
    dagreGraph.setGraph({rankdir: direction})

    nodes.forEach(node => {
        dagreGraph.setNode(node.id, {width: nodeWidth, height: nodeHeight})
    })

    edges.forEach(edge => {
        dagreGraph.setEdge(edge.source, edge.target)
    })

    dagre.layout(dagreGraph)

    const newNodes: Node[] = nodes.map(node => {
        const nodeWithPosition = dagreGraph.node(node.id)

        return {
            ...node,
            targetPosition: isHorizontal ? Position.Left : Position.Top,
            sourcePosition: isHorizontal ? Position.Right : Position.Bottom,
            position: {
                x: nodeWithPosition.x - nodeWidth / 2,
                y: nodeWithPosition.y - nodeHeight / 2
            }
        }
    })

    return {nodes: newNodes, edges}
}
