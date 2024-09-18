import {Domain, request, Watchlist, WatchlistRequest} from "./index";

export async function getWatchlists() {
    const response = await request({
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

export async function putWatchlist(watchlist: Partial<WatchlistRequest> & { token: string }) {
    const response = await request<WatchlistRequest>({
        method: 'PUT',
        url: 'watchlists/' + watchlist.token,
        data: watchlist,
    })
    return response.data
}

export async function getTrackedDomainList(params: { page: number, itemsPerPage: number }): Promise<any> {
    const response = await request({
        method: 'GET',
        url: 'tracked',
        params
    })
    return response.data
}

