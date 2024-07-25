import Toolbar from "@mui/material/Toolbar";
import IconButton from "@mui/material/IconButton";
import {Bookmark, ChevronLeft, Dns, Explore, LocalPolice, MenuBook, People} from "@mui/icons-material";
import {Divider, List, ListItemButton, ListItemIcon, ListItemText, ListSubheader, styled} from "@mui/material";
import React from "react";
import MuiDrawer from "@mui/material/Drawer";
import {useNavigate} from "react-router-dom";


const Drawer = styled(MuiDrawer, {shouldForwardProp: (prop) => prop !== 'open'})(
    ({theme, open}) => ({
        '& .MuiDrawer-paper': {
            position: 'relative',
            whiteSpace: 'nowrap',
            width: 240,
            transition: theme.transitions.create('width', {
                easing: theme.transitions.easing.sharp,
                duration: theme.transitions.duration.enteringScreen,
            }),
            boxSizing: 'border-box',
            ...(!open && {
                overflowX: 'hidden',
                transition: theme.transitions.create('width', {
                    easing: theme.transitions.easing.sharp,
                    duration: theme.transitions.duration.leavingScreen,
                }),
                width: theme.spacing(7),
                [theme.breakpoints.up('sm')]: {
                    width: theme.spacing(9),
                },
            }),
        },
    }),
)

export default function DrawerBox() {
    const [open, setOpen] = React.useState(true);
    const toggleDrawer = () => {
        setOpen(!open);
    };
    const navigate = useNavigate()

    return <Drawer variant="permanent" open={open}>
        <Toolbar
            sx={{
                mt: 5,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'flex-end',
                px: [1],
            }}
        >
            <IconButton onClick={toggleDrawer}>
                <ChevronLeft/>
            </IconButton>
        </Toolbar>
        <Divider/>
        <List component="nav">
            <ListSubheader component="div" inset>
                Domain names
            </ListSubheader>
            <ListItemButton onClick={() => navigate('/finder/domain')}>
                <ListItemIcon>
                    <Explore/>
                </ListItemIcon>
                <ListItemText primary="Domain finder"/>
            </ListItemButton>
            <ListItemButton onClick={() => navigate('/tld')}>
                <ListItemIcon>
                    <LocalPolice/>
                </ListItemIcon>
                <ListItemText primary="Top Level Domain"/>
            </ListItemButton>
            <ListItemButton onClick={() => navigate('/finder/nameserver')}>
                <ListItemIcon>
                    <Dns/>
                </ListItemIcon>
                <ListItemText primary="Nameserver"/>
            </ListItemButton>

            <Divider sx={{my: 1}}/>
            <ListSubheader component="div" inset>
                Entities
            </ListSubheader>
            <ListItemButton onClick={() => navigate('/finder/entity')}>
                <ListItemIcon>
                    <People/>
                </ListItemIcon>
                <ListItemText primary="Entity finder"/>
            </ListItemButton>
            <ListItemButton onClick={() => navigate('/reverse')}>
                <ListItemIcon>
                    <MenuBook/>
                </ListItemIcon>
                <ListItemText primary="Reverse directory"/>
            </ListItemButton>

            <Divider sx={{my: 1}}/>
            <ListSubheader component="div" inset>
                Tracking
            </ListSubheader>
            <ListItemButton onClick={() => navigate('/watchlist')}>
                <ListItemIcon>
                    <Bookmark/>
                </ListItemIcon>
                <ListItemText primary="My watchlists"/>
            </ListItemButton>
        </List>
    </Drawer>
}