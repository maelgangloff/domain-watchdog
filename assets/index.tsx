import React from "react";
import ReactDOM from "react-dom/client";

import LandingPage from "./pages/LandingPage";
import {Route, Routes, HashRouter} from "react-router-dom";
import FAQ from "./components/FAQ";


const root = ReactDOM.createRoot(document.getElementById("root") as HTMLElement);
root.render(
    <React.StrictMode>
        <HashRouter>
            <Routes>
                <Route path="/" element={<LandingPage/>}/>
            </Routes>
        </HashRouter>
    </React.StrictMode>
);
