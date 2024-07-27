import {Event, EventAction, request, Watchlist} from "./index";

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

export async function postWatchlist(domains: string[], triggers: { action: string, event: EventAction }[]) {
    const response = await request<{ token: string }>({
        method: 'POST',
        url: 'watchlists',
        data: {
            domains,
            triggers
        },
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

export async function patchWatchlist(domains: string[], triggers: Event[]) {
    const response = await request<Watchlist>({
        method: 'PATCH',
        url: 'watchlists',
        data: {
            domains,
            triggers
        },
        headers: {
            "Content-Type": 'application/merge-patch+json'
        }
    })
    return response.data
}