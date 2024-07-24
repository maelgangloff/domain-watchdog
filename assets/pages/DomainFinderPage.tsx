import React from 'react';
import Container from "@mui/material/Container";
import {Grid, Paper} from "@mui/material";


export default function DashboardPage() {
    return (
        <>
            <Container maxWidth="lg" sx={{mt: 4, mb: 4}}>
                <Grid container spacing={3}>
                    <Grid item xs={12} md={8} lg={9}>
                        <Paper
                            sx={{
                                p: 2,
                                display: 'flex',
                                flexDirection: 'column',
                                height: 240,
                            }}
                        >

                        </Paper>
                    </Grid>
                </Grid>
            </Container>
        </>
    );
};
