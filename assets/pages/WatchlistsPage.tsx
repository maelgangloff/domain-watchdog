import React, {useEffect, useState} from 'react';
import Container from "@mui/material/Container";
import {Grid, List, ListItem, ListItemText} from "@mui/material";
import Footer from "../components/Footer";
import {deleteWatchlist, getWatchlists, Watchlist} from "../utils/api";
import IconButton from "@mui/material/IconButton";
import {DeleteForever} from '@mui/icons-material'

export default function WatchlistsPage() {
    const [watchlists, setWatchlists] = useState<(Partial<Watchlist> & { token: string })[]>([])
    const [refreshKey, setRefreshKey] = useState(0)

    useEffect(() => {
        getWatchlists().then(setWatchlists)
    }, [refreshKey])

    return (
        <Container maxWidth="lg" sx={{mt: 20, mb: 4}}>
            <Grid container spacing={3}>
                <Grid item xs={12} md={8} lg={9}>
                    <List sx={{width: '100%', bgcolor: 'background.paper'}}>
                        {watchlists.map((w) => (
                            <ListItem
                                key={w.token}
                                secondaryAction={
                                    <IconButton aria-label="delete"
                                                onClick={(e) => deleteWatchlist(w.token).then(() => setRefreshKey(refreshKey + 1))}>
                                        <DeleteForever/>
                                    </IconButton>
                                }
                            >
                                <ListItemText primary={`Token ${w.token}`}/>
                            </ListItem>
                        ))}
                    </List>
                </Grid>
            </Grid>
            <Footer/>
        </Container>
    );
};
