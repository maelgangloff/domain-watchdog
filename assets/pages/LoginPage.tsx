import * as React from 'react';
import Avatar from '@mui/material/Avatar';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Box from '@mui/material/Box';
import LockOutlinedIcon from '@mui/icons-material/LockOutlined';
import Typography from '@mui/material/Typography';
import Container from '@mui/material/Container';
import Footer from "../components/Footer";
import Link from "@mui/material/Link";
import {login} from "../utils/api";
import {useNavigate} from "react-router-dom";

interface Props {
    setIsAuthenticated: (val: boolean) => void
    isAuthenticated: boolean
}

export default function LoginPage({setIsAuthenticated, isAuthenticated}: Props) {
    const navigate = useNavigate();

    const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);

        try {
            await login(data.get('email') as string, data.get('password') as string);
            setIsAuthenticated(true)

            navigate('/dashboard');

        } catch (e) {
            //TODO: handle error
            setIsAuthenticated(false)
        }
    };

    return (
        <>
            <Container component="main" maxWidth="xs">
                <Box
                    sx={{
                        marginTop: 20,
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                    }}
                >
                    <Avatar sx={{m: 1, bgcolor: 'secondary.main'}}>
                        <LockOutlinedIcon/>
                    </Avatar>
                    <Typography component="h1" variant="h5">
                        Sign in
                    </Typography>
                    <Box component="form" onSubmit={handleSubmit} noValidate sx={{mt: 1}}>
                        <TextField
                            margin="normal"
                            required
                            fullWidth
                            id="email"
                            label="Email Address"
                            name="email"
                            autoComplete="email"
                            autoFocus
                        />
                        <TextField
                            margin="normal"
                            required
                            fullWidth
                            name="password"
                            label="Password"
                            type="password"
                            id="password"
                            autoComplete="current-password"
                        />
                        <Button
                            type="submit"
                            fullWidth
                            variant="contained"
                            sx={{mt: 3}}
                        >
                            Sign In
                        </Button>

                    </Box>
                </Box>
                <Link href="/login/oauth">
                    <Button
                        type="button"
                        fullWidth
                        color='secondary'
                        variant="contained"
                        sx={{mt: 3, mb: 2}}
                    >
                        Single Sign-On
                    </Button>
                </Link>
                <Footer/>
            </Container>
        </>
    );
}