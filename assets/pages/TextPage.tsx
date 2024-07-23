import * as React from 'react';
import Box from '@mui/material/Box';
import Container from "@mui/material/Container";

interface Props {
    content: string
}

export default function Index({content}: Props) {
    return (
        <>
            <Container
                sx={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    gap: {xs: 4, sm: 8},
                    py: {xs: 8, sm: 10},
                    textAlign: {sm: 'center', md: 'left'},
                }}
            >
                <Box
                    sx={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        pt: {xs: 4, sm: 8},
                        width: '100%',
                        borderTop: '1px solid',
                        borderColor: 'divider',
                    }}
                >
                    <div dangerouslySetInnerHTML={{__html: content}}></div>
                </Box>
            </Container>
        </>
    )

}
