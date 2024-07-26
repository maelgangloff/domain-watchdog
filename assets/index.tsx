import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import {HashRouter} from "react-router-dom";

import 'antd/dist/reset.css';


const root = ReactDOM.createRoot(document.getElementById("root") as HTMLElement)

function Index() {

    return (
        <HashRouter>
            <App/>
        </HashRouter>
    );
}

root.render(<Index/>)
