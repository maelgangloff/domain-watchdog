import {Event, request, Watchlist} from "./index";

export async function getWatchlists() {
    const response = await request({
        url: 'watchlists'
    })
    return response.data
}

export async function getWatchlist(token: string) {
    const response = await request<Watchlist & { token: string }>({
        url: 'watchlists/' + token
    })
    return response.data
}

export async function postWatchlist(watchlist: Watchlist) {
    const response = await request<{ token: string }>({
        method: 'POST',
        url: 'watchlists',
        data: watchlist,
        headers: {
            "Content-Type": 'application/json'
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
