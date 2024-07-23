import React, {useEffect, useState} from "react";
import ReactDOM from "react-dom/client";
import TextPage from "./pages/TextPage";
import {HashRouter, Route, Routes} from "react-router-dom";

import tosContent from "./content/tos.md"
import privacyContent from "./content/privacy.md"
import LoginPage from "./pages/LoginPage";
import {createTheme, PaletteMode, ThemeProvider} from "@mui/material";
import CssBaseline from "@mui/material/CssBaseline";
import AppAppBar from "./components/AppAppBar";
import DashboardPage from "./pages/DashboardPage";
import {getUser} from "./utils/api";


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
                <CssBaseline/>
                <AppAppBar mode={mode} toggleColorMode={toggleColorMode} isAuthenticated={isAuthenticated}/>
                <Routes>
                    {isAuthenticated ?
                        <Route path="/" element={<DashboardPage/>}/>
                        :
                        <Route path="*" element={<LoginPage setIsAuthenticated={setIsAuthenticated}/>}/>
                    }
                    <Route path="/tos" element={<TextPage content={tosContent}/>}/>
                    <Route path="/privacy" element={<TextPage content={privacyContent}/>}/>

                </Routes>
            </HashRouter>
        </ThemeProvider>
    </React.StrictMode>
}

root.render(<App/>)
