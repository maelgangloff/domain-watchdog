import * as React from 'react';
import Box from '@mui/material/Box';
import AppBar from '@mui/material/AppBar';
import Toolbar from '@mui/material/Toolbar';
import Button from '@mui/material/Button';
import Container from '@mui/material/Container';

import Sitemark from './SitemarkIcon';
import {NavLink} from "react-router-dom";
import ToggleColorMode from "./ToggleColorMode";
import {PaletteMode} from "@mui/material";
import Link from "@mui/material/Link";

interface AppAppBarProps {
    mode: PaletteMode;
    toggleColorMode: () => void;
    isAuthenticated: boolean
}

export default function AppAppBar({mode, toggleColorMode, isAuthenticated}: AppAppBarProps) {
    return (
        <AppBar
            position="fixed"
            sx={{boxShadow: 0, bgcolor: 'transparent', backgroundImage: 'none', mt: 2}}
        >
            <Container maxWidth="lg">
                <Toolbar
                    variant="regular"
                    sx={(theme) => ({
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        flexShrink: 0,
                        borderRadius: '999px',
                        backdropFilter: 'blur(24px)',
                        maxHeight: 40,
                        border: '1px solid',
                        borderColor: 'divider',
                        bgcolor: 'hsla(220, 60%, 99%, 0.6)',
                        boxShadow:
                            '0 1px 2px hsla(210, 0%, 0%, 0.05), 0 2px 12px hsla(210, 100%, 80%, 0.5)',
                        ...theme.applyStyles('dark', {
                            bgcolor: 'hsla(220, 0%, 0%, 0.7)',
                            boxShadow:
                                '0 1px 2px hsla(210, 0%, 0%, 0.5), 0 2px 12px hsla(210, 100%, 25%, 0.3)',
                        }),
                    })}
                >
                    <Box sx={{flexGrow: 1, display: 'flex', alignItems: 'center', px: 0}}>
                        <Sitemark/>
                        <Box sx={{display: {xs: 'none', md: 'flex'}}}>
                            <NavLink to='/'>
                                <Button
                                    variant="text"
                                    color="info"
                                    size="small"
                                >
                                    Presentation
                                </Button>
                            </NavLink>
                        </Box>
                        <Box sx={{display: {xs: 'none', md: 'flex'}}}>
                            <NavLink to="/dashboard">
                                <Button
                                    variant="text"
                                    color="info"
                                    size="small"
                                >
                                    Dashboard
                                </Button>
                            </NavLink>
                        </Box>
                    </Box>
                    <Box
                        sx={{
                            display: {xs: 'none', md: 'flex'},
                            gap: 0.5,
                            alignItems: 'center',
                        }}
                    >
                        <ToggleColorMode
                            data-screenshot="toggle-mode"
                            mode={mode}
                            toggleColorMode={toggleColorMode}
                        />
                        {
                            !isAuthenticated ?
                                <NavLink to="/login">
                                    <Button color="primary" variant="text" size="small">
                                        Sign in
                                    </Button>
                                </NavLink>
                                : <Link href="/logout">
                                    <Button color="primary" variant="text" size="small">
                                        Log out
                                    </Button>
                                </Link>
                        }
                    </Box>
                </Toolbar>
            </Container>
        </AppBar>
    );
}
