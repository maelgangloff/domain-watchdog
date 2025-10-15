import type {TrackedDomains, Watchlist, WatchlistRequest} from './index'
import {request} from './index'

interface WatchlistList {
    'hydra:totalItems': number
    'hydra:member': Watchlist[]
}

export async function getWatchlists(): Promise<WatchlistList> {
    const response = await request<WatchlistList>({
        url: 'watchlists'
    })
    return response.data
}

export async function getWatchlist(token: string) {
    const response = await request<Watchlist>({
        url: 'watchlists/' + token
    })
    return response.data
}

export async function postWatchlist(watchlist: WatchlistRequest) {
    const response = await request<{ token: string }>({
        method: 'POST',
        url: 'watchlists',
        data: watchlist,
        headers: {
            'Content-Type': 'application/json'
        }
    })
    return response.data
}

export async function deleteWatchlist(token: string): Promise<void> {
    await request({
        method: 'DELETE',
        url: 'watchlists/' + token
    })
}

export async function putWatchlist(watchlist: Partial<WatchlistRequest> & { token: string }) {
    const response = await request<WatchlistRequest>({
        method: 'PUT',
        url: 'watchlists/' + watchlist.token,
        data: watchlist
    })
    return response.data
}

export async function getTrackedDomainList(params: { page: number, itemsPerPage: number }): Promise<TrackedDomains> {
    const response = await request<TrackedDomains>({
        method: 'GET',
        url: 'tracked',
        params
    })
    return response.data
}
