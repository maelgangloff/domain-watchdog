import React, {useEffect, useState} from "react";
import ReactDOM from "react-dom/client";
import TextPage from "./pages/TextPage";
import {HashRouter, Navigate, Route, Routes} from "react-router-dom";

import tosContent from "./content/tos.md"
import privacyContent from "./content/privacy.md"
import LoginPage from "./pages/LoginPage";
import {createTheme, PaletteMode, ThemeProvider} from "@mui/material";
import CssBaseline from "@mui/material/CssBaseline";
import AppAppBar from "./components/AppAppBar";
import {getUser} from "./utils/api/user";
import DrawerBox from "./components/DrawerBox";
import Box from "@mui/material/Box";
import DomainFinderPage from "./pages/DomainFinderPage";
import EntityFinderPage from "./pages/EntityFinderPage";
import NameserverFinderPage from "./pages/NameserverFinderPage";
import ReverseDirectoryPage from "./pages/ReverseDirectoryPage";
import TldPage from "./pages/TldPage";
import WatchlistsPage from "./pages/WatchlistsPage";


const root = ReactDOM.createRoot(document.getElementById("root") as HTMLElement);


function App() {
    const [mode, setMode] = React.useState<PaletteMode>('dark')
    const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);

    const toggleColorMode = () => {
        setMode((prev) => (prev === 'dark' ? 'light' : 'dark'));
    }

    useEffect(() => {
        getUser().then(() => setIsAuthenticated(true)).catch(() => setIsAuthenticated(false))
    }, []);

    return <React.StrictMode>
        <ThemeProvider theme={createTheme({palette: {mode: mode}})}>
            <HashRouter>
                <Box sx={{display: 'flex'}}>
                    <CssBaseline/>
                    {isAuthenticated && <DrawerBox/>}

                    <AppAppBar mode={mode} toggleColorMode={toggleColorMode} isAuthenticated={isAuthenticated}/>
                    <Routes>
                        {isAuthenticated ?
                            <>
                                <Route path="/" element={<Navigate to="/finder/domain"/>}/>
                                <Route path="/finder/domain" element={<DomainFinderPage/>}/>
                                <Route path="/finder/entity" element={<EntityFinderPage/>}/>
                                <Route path="/finder/nameserver" element={<NameserverFinderPage/>}/>
                                <Route path="/reverse" element={<ReverseDirectoryPage/>}/>
                                <Route path="/tld" element={<TldPage/>}/>
                                <Route path="/watchlist" element={<WatchlistsPage/>}/>
                            </>
                            :
                            <Route path="*" element={<LoginPage setIsAuthenticated={setIsAuthenticated}/>}/>
                        }
                        <Route path="/tos" element={<TextPage content={tosContent}/>}/>
                        <Route path="/privacy" element={<TextPage content={privacyContent}/>}/>

                    </Routes>
                </Box>
            </HashRouter>
        </ThemeProvider>
    </React.StrictMode>
}

root.render(<App/>)
