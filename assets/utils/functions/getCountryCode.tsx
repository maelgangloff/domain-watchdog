export const getCountryCode = (tld: string): string => {
    const exceptions = {uk: 'gb', su: 'ru', tp: 'tl'}
    if (tld in exceptions) return exceptions[tld as keyof typeof exceptions]
    return tld.toUpperCase()
}