export const eppStatusCodeToColor = (s: string) =>
    ['active', 'ok'].includes(s) ? 'green' :
        s.startsWith('client') ? 'purple' :
            s.startsWith('server') ? 'geekblue' :
                s.includes('prohibited') ? 'red' : 'blue'