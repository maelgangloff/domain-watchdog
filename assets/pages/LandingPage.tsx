import * as React from 'react';
import Box from '@mui/material/Box';
import Divider from '@mui/material/Divider';
import Hero from '../components/Hero';
import Highlights from '../components/Highlights';
import FAQ from '../components/FAQ';
import Footer from '../components/Footer';

export default function Index() {
    return (
        <>
            <Hero/>
            <Box sx={{bgcolor: 'background.default'}}>
                <Divider/>
                <Highlights/>
                <Divider/>
                <FAQ/>
                <Divider/>
                <Footer/>
            </Box>
        </>
    );
}
