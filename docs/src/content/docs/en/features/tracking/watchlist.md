---
title: Watchlist
---

A Watchlist is a list of domain names, triggers and possibly an API connector. They allow you to follow the life of the
listed domain names and send you a notification when a change has been detected.

If a domain has expired and a connector is linked to the Watchlist, then Domain Watchdog will try to order it via the
connector provider's API.

:::note
If the same domain name is present on several Watchlists, on the same principle as the raise condition, it is not
possible to predict in advance which user will win the domain name. The choice is left to chance...
:::

## Create a Watchlist

1. Choose a name for your Watchlist and find it more easily
2. Add the domain names you want to follow
3. Select the events for which you want to receive an email notification
4. Optionally add a connector to try to automatically buy a domain name that becomes available
5. Click the button to create your Watchlist. **Congratulations 🎉**

Now, it's your turn to create a Watchlist!

## Limitations

Depending on the instance configuration, there are several limitations to frame user behavior:

* `LIMIT_MAX_WATCHLIST` : Maximum number of Watchlists that can be created
* `LIMIT_MAX_WATCHLIST_DOMAINS` : Maximum number of domain names for each Watchlist
