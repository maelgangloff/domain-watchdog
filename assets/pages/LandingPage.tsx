import * as React from 'react';
import {PaletteMode} from '@mui/material';
import CssBaseline from '@mui/material/CssBaseline';
import Box from '@mui/material/Box';
import Divider from '@mui/material/Divider';
import {createTheme, ThemeProvider} from '@mui/material/styles';
import AppAppBar from '../components/AppAppBar';
import Hero from '../components/Hero';
import Highlights from '../components/Highlights';
import FAQ from '../components/FAQ';
import Footer from '../components/Footer';

interface ToggleCustomThemeProps {
    showCustomTheme: Boolean;
    toggleCustomTheme: () => void;
}

export default function Index() {
    const [mode, setMode] = React.useState<PaletteMode>('light');
    const defaultTheme = createTheme({palette: {mode}});

    const toggleColorMode = () => {
        setMode((prev) => (prev === 'dark' ? 'light' : 'dark'));
    };

    return (
        <ThemeProvider theme={defaultTheme}>
            <CssBaseline/>
            <AppAppBar mode={mode} toggleColorMode={toggleColorMode}/>
            <Hero/>
            <Box sx={{bgcolor: 'background.default'}}>
                <Divider/>
                <Highlights/>
                <Divider/>
                <FAQ/>
                <Divider/>
                <Footer/>
            </Box>
        </ThemeProvider>
    );
}
