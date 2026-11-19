---
title: 🔏 Security policy
description: How to report a vulnerability in Domain Watchdog privately, what to include in your report, and how to secure a self-hosted instance.
---

## How to report a vulnerability?

If you want to report a vulnerability, please contact the maintainer of this repository privately, by email. Thanks!

~~~
Contact: mailto:contact@maelgangloff.fr
Encryption: openpgp4fpr:323E63F1AB879F30B38F527611FDC81C24A7F629
Encryption: https://maelgangloff.fr/pgp-key.txt
Preferred-Languages: fr, en
~~~

Please do not disclose the vulnerability publicly (GitHub issue, discussion, social network) before it has been fixed.
You can encrypt your message with the OpenPGP key above.

## What should a report include?

- The affected version of Domain Watchdog (Docker image tag or Git commit)
- The affected component: web interface, API endpoint, worker, connector to a registrar…
- The steps to reproduce the issue, and a proof of concept if you have one
- The potential impact: data exposure, privilege escalation, unwanted domain purchase…

## How to secure a self-hosted instance?

These recommendations rely on the settings described in the [configuration](/en/install-config/configuration/) and
[Docker installation](/en/install-config/install/docker-compose/) pages.

- Always run the latest version of the Docker image (`docker compose pull`).
- Replace the default database password `!ChangeMe!` before the first start.
- Serve the instance over HTTPS behind a reverse proxy and keep `HTTP_SECURE_COOKIE` set to `true`.
- On a public instance, disable open registration (`REGISTRATION_ENABLED=false`), require email verification
  (`REGISTRATION_VERIFY_EMAIL=true`) or limit usage with `LIMITED_FEATURES` and the `LIMIT_MAX_*` variables.
- Registrar API credentials stored in Connectors allow domain purchases: create dedicated API keys with the smallest
  possible scope at your registrar, and revoke them when you delete a Connector.
