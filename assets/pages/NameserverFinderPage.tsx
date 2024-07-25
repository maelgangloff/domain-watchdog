import React from 'react';
import Container from "@mui/material/Container";
import {Grid} from "@mui/material";
import Footer from "../components/Footer";


export default function NameserverFinderPage() {
    return (
        <>
            <Container maxWidth="lg" sx={{mt: 20, mb: 4}}>
                <Grid container spacing={3}>
                    <Grid item xs={12} md={8} lg={9}>

                    </Grid>
                </Grid>
                <Footer/>
            </Container>
        </>
    );
};
