---
title: Supported registrar
---

:::caution[Reminder]

* Please note that this project is NOT affiliated IN ANY WAY with the API Providers used to order domain names.
* The project installers are responsible for the use of their own instance.
* Under no circumstances will the owner of this project be held responsible for other cases over which he has no
  control.
  :::

## OVH

| Field          | Description                                                                      | Required |
|----------------|----------------------------------------------------------------------------------|:--------:|
| App key        | the key that allows OVH to identify your application                             | Required | 
| App secret key | the secret key associated with your application                                  | Required |
| Consumer key   | the secret key that links the application to your account                        | Required |
| Endpoint       | allows you to choose which server to use (Europe, United States or Canada)       | Required |
| Subsidiary     | the country linked to the OVH subsidiary associated with your account            | Required |
| Pricing mode   | choose whether you want to pay for a Premium domain name or only standard prices | Required |

## Gandi

| Field      | Description                                                      | Required |
|------------|------------------------------------------------------------------|:--------:|
| Token      | your account authentication token                                | Required | 
| Sharing ID | indicates the organization that will pay for the ordered product | Optional |

## Namecheap

:::caution
This provider requires that the IPv4 address of your instance be entered on its web interface when creating the API
connection. This information must also be entered in the configuration.
:::

| Field    | Description                           | Required |
|----------|---------------------------------------|:--------:|
| API user | the API user as given by the Provider | Required | 
| API key  | the API key as given by the Provider  | Required |

## AutoDNS

:::caution
This provider does not provide a list of supported TLD. Please double-check if the domain you want to register is
supported.
:::

| Field         | Description                                                   | Required |
|---------------|---------------------------------------------------------------|:--------:|
| Username      | the account username                                          | Required | 
| Password      | the account password                                          | Required |
| Owner consent | purchase consent                                              | Required |
| Context       | the "context" as given by the Provider                        | Required |
| Contact ID    | Contact ID of the domain name holder of the purchased domains | Required |

## Name.com

:::caution
This provider does not provide a list of supported TLD. Please double-check if the domain you want to register is
supported.
:::

| Field    | Description                       | Required |
|----------|-----------------------------------|:--------:|
| Username | the account username              | Required | 
| Token    | your account authentication token | Required | 

## Custom EPP server

This type of connector allows you to directly link your instance to a registry via the EPP protocol.
This requires that you have signed a contract with a registry; you are then considered a registry in your own right.

Currently, the implementation of this feature has not been tested; your feedback is important!
