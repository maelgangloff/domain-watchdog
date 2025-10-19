export const eppStatusCodeToColor = (s?: string) =>
    s === undefined ? 'default' :
        ['active', 'ok'].includes(s)
            ? 'green'
            : ['pending delete', 'redemption period'].includes(s)
                ? 'red'
                : s.startsWith('client')
                    ? 'purple'
                    : s.startsWith('server') ? 'geekblue' : 'blue'
