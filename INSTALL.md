# Installation and Update

## Installation

To deploy a Domain Watchdog instance, please refer to the Symfony documentation
on [How to deploy a Symfony application](https://symfony.com/doc/current/deployment.html).

### Prerequisites

- PHP 8.2 or higher
- PostgreSQL 16 or higher

In order to retrieve information about domain names, Domain Watchdog will query the RDAP server responsible for the TLD.
It is crucial that the Domain Watchdog instance is placed in a clean environment from which these servers can be
queried.
In particular, the DNS servers and root certificates of the system must be trusted.

### Steps

Clone the repository:

```shell
git clone https://github.com/maelgangloff/domain-watchdog.git
 ```

#### Backend

1. Install dependencies:
    ```shell
    composer install
    ```
2. Set up your environment variables:
    ```shell
    cp .env .env.local
    ```
3. Generate the cryptographic key pair for the JWT signature
    ```shell
    php bin/console lexik:jwt:generate-keypair
    ```
4. Run database migrations:
    ```shell
    php bin/console doctrine:migrations:migrate
    ```
5. Start the Symfony server:
    ```shell
    symfony server:start
    ```
6. Build assets:
   ```shell
   php bin/console assets:install
   ```
7. Don't forget to set up workers to process the [message queue](https://symfony.com/doc/current/messenger.html)

#### Frontend

1. Install dependencies:
    ```shell
    yarn install
    ```
2. Generate language files:
    ```shell
    yarn run ttag:po2json
    ```
3. Make the final build:
    ```shell
    yarn build
    ```
4. Add and modify the following files as you wish:
   ~~~
   public/content/home.md
   public/content/privacy.md
   public/content/tos.md
   public/content/faq.md
   public/images/icons-512.png
   public/images/banner.png
   public/favicon.ico
   ~~~

## Update

**Any updates are your responsibility. Make a backup of the data if necessary.**

Fetch updates from the remote repository:

```shell
git pull origin master
 ```

### Backend

1. Install dependencies:
    ```shell
    composer install
    ```
2. Run database migrations:
    ```shell
    php bin/console doctrine:migrations:migrate
    ```
3. Clearing the Symfony cache:
   ```shell
   php bin/console cache:clear
    ```
4. Build assets:
   ```shell
   php bin/console assets:install
   ```

### Frontend

1. Install dependencies:
    ```shell
    yarn install
    ```
2. Generate language files:
    ```shell
    yarn run ttag:po2json
    ```
3. Make the final build:
    ```shell
    yarn build
    ```
