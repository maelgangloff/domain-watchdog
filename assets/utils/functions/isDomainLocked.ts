export const isDomainLocked = (status: string[], type: 'client' | 'server'): boolean =>
    (status.includes(type + ' update prohibited') && status.includes(type + ' delete prohibited')) ||
    status.includes(type + ' transfer prohibited')