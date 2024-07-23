import * as React from 'react';
import Box from '@mui/material/Box';
import Container from '@mui/material/Container';
import IconButton from '@mui/material/IconButton';
import Link from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import {NavLink} from "react-router-dom";

function Copyright() {
    return (
        <Typography variant="body2" sx={{color: 'text.secondary', mt: 1}}>
            {'Copyright © '}
            <Link href="https://github.com/maelgangloff/domain-watchdog">Domain Watchdog&nbsp;</Link>
            {new Date().getFullYear()}
        </Typography>
    );
}

export default function Footer() {
    return (
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
                <div>
                    <NavLink to="/privacy">
                        <Link color="text.secondary" variant="body2">
                            Privacy Policy
                        </Link>
                    </NavLink>
                    <Typography sx={{display: 'inline', mx: 0.5, opacity: 0.5}}>
                        &nbsp;•&nbsp;
                    </Typography>
                    <NavLink to="/tos">
                        <Link color="text.secondary" variant="body2">
                            Terms of Service
                        </Link>
                    </NavLink>
                    <Copyright/>
                </div>
                <Stack
                    direction="row"
                    spacing={1}
                    useFlexGap
                    sx={{justifyContent: 'left', color: 'text.secondary'}}
                >
                    <IconButton
                        color="inherit"
                        href="https://github.com/maelgangloff/domain-watchdog"
                        aria-label="GitHub"
                        sx={{alignSelf: 'center'}}
                    >
                    </IconButton>
                </Stack>
            </Box>
        </Container>
    );
}
