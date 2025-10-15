import React from 'react'
import type {Connector} from '../../../utils/api/connectors'
import {WatchlistCard} from './WatchlistCard'
import type {Watchlist} from '../../../utils/api'

export function WatchlistsList({watchlists, onDelete, onUpdateWatchlist, connectors}: {
    watchlists: Watchlist[]
    onDelete: () => void
    onUpdateWatchlist: (values: { domains: string[], trackedEvents: string[], token: string }) => Promise<void>
    connectors: Array<Connector & { id: string }>
}) {
    return (
        <>
            {watchlists.map(watchlist =>
                <WatchlistCard
                    key={watchlist.token}
                    watchlist={watchlist}
                    onUpdateWatchlist={onUpdateWatchlist}
                    connectors={connectors}
                    onDelete={onDelete}
                />
            )}
        </>
    )
}
