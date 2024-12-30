export const rolesToColor = (roles: string[]) => roles.includes('registrant')
    ? 'green'
    : roles.includes('registrar')
        ? 'purple'
        : roles.includes('administrative')
            ? 'blue'
            : roles.includes('technical')
                ? 'orange'
                : roles.includes('sponsor')
                    ? 'magenta'
                    : roles.includes('billing') ? 'cyan' : 'default'
