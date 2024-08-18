import React from "react";
import {Watchlist} from "../../../pages/tracking/WatchlistPage";
import {Connector} from "../../../utils/api/connectors";
import {WatchlistCard} from "./WatchlistCard";

export function WatchlistsList({watchlists, onDelete, onUpdateWatchlist, connectors}: {
    watchlists: Watchlist[],
    onDelete: () => void,
    onUpdateWatchlist: (values: { domains: string[], triggers: string[], token: string }) => Promise<void>,
    connectors: (Connector & { id: string })[]
}) {


    return <>
        {watchlists.map(watchlist =>
            <WatchlistCard watchlist={watchlist}
                           onUpdateWatchlist={onUpdateWatchlist}
                           connectors={connectors}
                           onDelete={onDelete}/>
        )
        }
    </>
}