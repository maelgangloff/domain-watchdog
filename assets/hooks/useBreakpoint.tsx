import {Breakpoint, theme} from 'antd'
import {useMediaQuery} from 'react-responsive'

const {useToken} = theme

type ScreenProperty = 'screenXXL' | 'screenXL' | 'screenLG' | 'screenMD' | 'screenSM' | 'screenXS'

const propertyName = (breakpoint: Breakpoint): ScreenProperty => {
    return 'screen' + breakpoint.toUpperCase() as ScreenProperty
}

export default function useBreakpoint(
    breakpoint: Breakpoint
) {
    const {token} = useToken()
    const width: number = token[propertyName(breakpoint)]

    return useMediaQuery({maxWidth: width})
}
