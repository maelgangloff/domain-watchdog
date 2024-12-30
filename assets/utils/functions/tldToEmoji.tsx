import {getCountryCode} from './getCountryCode'

export const tldToEmoji = (tld: string) => {
    if (tld.startsWith('xn--')) return '-'

    return String.fromCodePoint(
        ...getCountryCode(tld)
            .toUpperCase()
            .split('')
            .map((char) => 127397 + char.charCodeAt(0))
    )
}
