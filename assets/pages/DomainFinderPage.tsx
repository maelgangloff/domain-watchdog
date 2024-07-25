import React, {ChangeEvent, useState} from 'react';
import Container from "@mui/material/Container";
import {Grid, InputAdornment, Paper} from "@mui/material";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import {Explore} from "@mui/icons-material";
import Footer from "../components/Footer";


export default function DomainFinderPage() {
    const [ldhName, setLdhName] = useState("")
    const [error, setError] = useState(false)

    const onChangeDomain = (e: ChangeEvent<HTMLInputElement>) => {
        setLdhName(e.currentTarget.value);
        const regex = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/
        setError(!regex.test(e.currentTarget.value))
    }

    return (
        <>
            <Container maxWidth="lg" sx={{mt: 20, mb: 4}}>
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
                            <TextField
                                sx={{mt: 5}} label="Domain name" variant="standard" value={ldhName}
                                onChange={onChangeDomain}
                                helperText={error && "This domain name does not appear to be valid"}
                                error={error}
                                InputProps={{
                                    startAdornment: (
                                        <InputAdornment position="start">
                                            <Explore/>
                                        </InputAdornment>
                                    ),
                                }}
                            />

                            <Typography variant="subtitle2" sx={{mt: 3}}>
                                This tool allows you to search for a domain name in the database.
                                As a reminder, if a domain name is unknown to Domain Watchdog or if the data is
                                more
                                than a week old, an RDAP search will be performed. The RDAP search is an operation worth
                                a token.
                            </Typography>
                        </Paper>
                    </Grid>
                </Grid>
                <Footer/>
            </Container>
        </>
    );
};
