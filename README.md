# Domain Watchdog

Domain Watchdog is a standalone web application that collects open access information about domain names, helping users
track the history and changes associated with domain names.

## Why use it?

- **Historical Tracking**: Know the history of a domain name, from its inception to its release into the public domain.
- **Detailed Monitoring**: Follow the evolution of a domain name and the entities that manage it in detail.
- **Reverse Directory**: Discover domain names associated with an entity registered with a registrar.

Although the RDAP and WHOIS protocols allow you to obtain precise information about a domain, it is not possible to
perform a reverse search to discover a list of domain names associated with an entity. Additionally, accessing a
detailed history of events (ownership changes, renewals, etc.) is not feasible with these protocols.

## How it works?

The latest version of the WHOIS protocol was standardized in 2004 by RFC 3912.[^1] This protocol allows anyone to
retrieve key information concerning a domain name, an IP address, or an entity registered with a registry.

ICANN launched a global vote in 2023 to propose replacing the WHOIS protocol with RDAP. As a result, registries and
registrars will no longer be required to support WHOIS from 2025 (*WHOIS Sunset Date*).[^2]

Domain Watchdog uses the RDAP protocol, which will soon be the new standard for retrieving information concerning domain
names. The data is organized in a SQL database to minimize space by ensuring an entity is not repeated.

## Installation

To deploy a Domain Watchdog instance, please refer to the Symfony documentation
on [How to deploy a Symfony application](https://symfony.com/doc/current/deployment.html).

### Prerequisites

- PHP 8.2 or higher
- Symfony 5.9 or higher
- A SQL-compatible database (e.g., MariaDB, MySQL, PostgreSQL)

### Steps

1. Clone the repository:
    ```shell
    git clone https://github.com/maelgangloff/domain-watchdog.git
    ```
2. Navigate to the project directory:
    ```shell
    cd domain-watchdog
    ```
3. Install dependencies:
    ```shell
    composer install
    ```
4. Set up your environment variables:
    ```shell
    cp .env .env.local
    ```
5. Generate the cryptographic key pair for the JWT signature
    ```shell
    php bin/console lexik:jwt:generate-keypair
    ```
6. Run database migrations:
    ```shell
    php bin/console doctrine:migrations:migrate
    ```
7. Start the Symfony server:
    ```shell
    symfony server:start
    ```

> [!NOTE]
> ## Useful documentation
> - [RFC 7482 : Registration Data Access Protocol (RDAP) Query Format](https://datatracker.ietf.org/doc/html/rfc7482)
> - [RFC 7483 : JSON Responses for the Registration Data Access Protocol (RDAP)](https://datatracker.ietf.org/doc/html/rfc7483)
> - [RFC 7484 : Finding the Authoritative Registration Data (RDAP) Service](https://datatracker.ietf.org/doc/html/rfc7484)

## Licensing
This entire project is licensed under *GNU Affero General Public License v3.0 or later*.
Contributions are welcome as long as they do not contravene the Code of Conduct.


[^1]: RFC 3912 : WHOIS Protocol Specification. (2004). IETF Datatracker. https://datatracker.ietf.org/doc/html/rfc3912
[^2]: 2023 Global Amendments to the Base gTLD Registry Agreement (RA), Specification 13, and 2013 Registrar
Accreditation Agreement (RAA) - ICANN. (s. d.). https://www.icann.org/resources/pages/global-amendment-2023-en
