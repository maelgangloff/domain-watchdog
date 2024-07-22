import * as React from 'react';
import Box from '@mui/material/Box';
import Card from '@mui/material/Card';
import Container from '@mui/material/Container';
import Grid from '@mui/material/Grid';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import AutoFixHighRoundedIcon from '@mui/icons-material/AutoFixHighRounded';
import QueryStatsRoundedIcon from '@mui/icons-material/QueryStatsRounded';
import SettingsSuggestRoundedIcon from '@mui/icons-material/SettingsSuggestRounded';
import ThumbUpAltRoundedIcon from '@mui/icons-material/ThumbUpAltRounded';

const items = [
    {
        icon: <SettingsSuggestRoundedIcon/>,
        title: 'Virtuous RDAP requests',
        description:
            'Domain Watchdog is designed to make as few RDAP requests as possible so as not to overload them.',
    },
    {
        icon: <ThumbUpAltRoundedIcon/>,
        title: 'Open access API',
        description:
            'The Domain Watchdog API is accessible to all its users.',
    },
    {
        icon: <AutoFixHighRoundedIcon/>,
        title: 'Open Source',
        description:
            'The project is licensed under AGPL-3.0. The source code is freely available on GitHub.',
    },
    {
        icon: <QueryStatsRoundedIcon/>,
        title: 'Data quality',
        description:
            'The data is retrieved from official top-level domain name registries. Once collected, this data is made available to users of this service.',
    },
];

export default function Highlights() {
    return (
        <Box
            id="highlights"
            sx={{
                pt: {xs: 4, sm: 12},
                pb: {xs: 8, sm: 16},
                color: 'white',
                bgcolor: 'hsl(220, 30%, 2%)',
            }}
        >
            <Container
                sx={{
                    position: 'relative',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    gap: {xs: 3, sm: 6},
                }}
            >
                <Box
                    sx={{
                        width: {sm: '100%', md: '60%'},
                        textAlign: {sm: 'left', md: 'center'},
                    }}
                >
                    <Typography component="h2" variant="h4">
                        Highlights
                    </Typography>
                    <Typography variant="body1" sx={{color: 'grey.400'}}>
                        Here are the reasons why Domain Watchdog is the solution for domain name tracking.
                    </Typography>
                </Box>
                <Grid container spacing={2.5}>
                    {items.map((item, index) => (
                        <Grid item xs={6} sm={6} md={6} key={index}>
                            <Stack
                                direction="column"
                                component={Card}
                                spacing={1}
                                useFlexGap
                                sx={{
                                    color: 'inherit',
                                    p: 3,
                                    height: '100%',
                                    border: '1px solid',
                                    borderColor: 'hsla(220, 25%, 25%, .3)',
                                    background: 'transparent',
                                    backgroundColor: 'grey.900',
                                    boxShadow: 'none',
                                }}
                            >
                                <Box sx={{opacity: '50%'}}>{item.icon}</Box>
                                <div>
                                    <Typography gutterBottom sx={{fontWeight: 'medium'}}>
                                        {item.title}
                                    </Typography>
                                    <Typography variant="body2" sx={{color: 'grey.400'}}>
                                        {item.description}
                                    </Typography>
                                </div>
                            </Stack>
                        </Grid>
                    ))}
                </Grid>
            </Container>
        </Box>
    );
}
