<p align="center"><img src="https://github.com/user-attachments/assets/942ddfd0-2c76-4b00-bd9f-727cfddc0103" alt="Domain Watchdog" width="150" height="150" /></p>
<h1 align="center"><b>Domain Watchdog</b></h1>
<p align="center">Your companion in the quest for domain names 🔍 <br/><a href="https://domainwatchdog.eu">domainwatchdog.eu »</a></p>
<br/>

Domain Watchdog is an app that uses RDAP to collect publicly available info about domains, track their history, and
purchase them.
For more information please check out [the documentation](https://domainwatchdog.eu) !

## Why use it?

- **Historical Tracking**: Know the history of a domain name, from its inception to its release into the public domain.
- **Detailed Monitoring**: Follow the evolution of a domain name and the entities that manage it in detail.
- **Auto-purchase Domain**: You want the domain name of your dreams, but it is already taken? Domain Watchdog detects
  the deletion of the domain name on WHOIS and can trigger the purchase of the domain name via a provider's API

Although the RDAP and WHOIS protocols allow you to obtain precise information about a domain, it is not possible to
perform a reverse search to discover a list of domain names associated with an entity. Additionally, accessing a
detailed history of events (ownership changes, renewals, etc.) is not feasible with these protocols.

## Install

> [!TIP]
> For more details on the installation procedure, please refer to [the documentation](https://domainwatchdog.eu/en/self-hosting/docker-install/).

### Docker Deployment

1. Download the [docker-compose.yml](https://github.com/maelgangloff/domain-watchdog/blob/develop/docker-compose.yml)
   and modify it as needed
2. Download the [.env](https://github.com/maelgangloff/domain-watchdog/blob/develop/.env) and modify it as needed
3. Add static files to customize your instance (under `public/content`)
4. Pull the latest version of the Domain Watchdog image from Docker Hub

```shell
docker compose pull
```

5. Start the project in production environment

```shell
docker compose up
```

By default, the container listens on http://localhost:8080, but you can configure this in environment variables.

## Development and contributions

See [the documentation](https://domainwatchdog.eu) for information on setting up a development environment and making
your contributions.  
To add a new provider, a [dedicated page](https://domainwatchdog.eu/en/developing/add-provider/) is available.

## Security

Please see [SECURITY.md](./SECURITY.md).

## License

This source code of this project is licensed under *GNU Affero General Public License v3.0 or later*.
Contributions are welcome as long as they do not contravene the Code of Conduct.

## Disclaimer

> [!IMPORTANT]
> * Domain Watchdog is an opensource project distributed under *GNU Affero General Public License v3.0 or later* license
> * In the internal operation, everything is done to perform the least possible RDAP requests: rate limit, intelligent
    caching system, etc.
> * Please note that this project is NOT affiliated IN ANY WAY with the API Providers used to order domain names.
> * The project installers are responsible for the use of their own instance.
> * Under no circumstances will the owner of this project be held responsible for other cases over which he has no
    control.
