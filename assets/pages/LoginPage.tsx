import * as React from 'react';
import {useState} from 'react';
import Avatar from '@mui/material/Avatar';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Box from '@mui/material/Box';
import LockOutlinedIcon from '@mui/icons-material/LockOutlined';
import Typography from '@mui/material/Typography';
import Link from "@mui/material/Link";
import {login} from "../utils/api";
import {useNavigate} from "react-router-dom";
import {Alert} from "@mui/material";
import Container from "@mui/material/Container";

interface Props {
    setIsAuthenticated: (val: boolean) => void
}

export default function LoginPage({setIsAuthenticated}: Props) {
    const navigate = useNavigate()

    const [error, setError] = useState<string>('')
    const [credentials, setCredentials] = useState({email: "", password: ""})

    const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault()
        try {
            await login(credentials.email, credentials.password);
            setIsAuthenticated(true)
            navigate('/');

        } catch (e: any) {
            setCredentials({email: "", password: ""})
            setError(e.response.data.message)
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
                        {
                            error !== "" && <Alert variant="outlined" severity="error">{error}</Alert>
                        }
                        <TextField
                            margin="normal"
                            required
                            fullWidth
                            id="email"
                            label="Email Address"
                            name="email"
                            autoComplete="email"
                            autoFocus
                            value={credentials.email}
                            onChange={(e) => setCredentials({...credentials, email: e.currentTarget.value})}
                        />
                        <TextField
                            margin="normal"
                            required
                            fullWidth
                            name="password"
                            label="Password"
                            type="password"
                            id="password"
                            value={credentials.password}
                            autoComplete="current-password"
                            onChange={(e) => setCredentials({...credentials, password: e.currentTarget.value})}
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
            </Container>
        </>
    );
}