import React from 'react'
import type {Connector} from '../../../utils/api/connectors'
import {WatchlistCard} from './WatchlistCard'
import type {Watchlist} from '../../../utils/api'

export function WatchlistsList({watchlists, onChange, onUpdateWatchlist, connectors}: {
    watchlists: Watchlist[]
    onChange: () => void
    onUpdateWatchlist: (values: { domains: string[], trackedEvents: string[], trackedEppStatus: string[], token: string }) => Promise<void>
    connectors: Array<Connector & { id: string }>
}) {
    return (
        <>
            {[...watchlists.filter(w => w.enabled), ...watchlists.filter(w => !w.enabled)].map(watchlist =>
                <WatchlistCard
                    key={watchlist.token}
                    watchlist={watchlist}
                    onUpdateWatchlist={onUpdateWatchlist}
                    connectors={connectors}
                    onChange={onChange}
                />
            )}
        </>
    )
}
