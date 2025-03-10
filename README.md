# seat-srp
A module for SeAT that tracks SRP requests

This plugin write for [SeAT](https://github.com/eveseat/seat) is providing to your instance a way to manage your ship replacement program (SRP)

[![Latest Stable Version](https://img.shields.io/packagist/v/cryptatech/seat-srp.svg?style=flat-square)]()
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square)](https://raw.githubusercontent.com/eveseat-plugins/seat-srp/master/LICENSE)

If you have issues with this, you can contact me on Eve as **Crypta Electrica**, or on email as 'crypta@crypta.tech'

## Quick Installation:

Please see the SeAT docs for installation instructions [HERE](https://eveseat.github.io/docs/community_packages/).

The composer string to use is `cryptatech/seat-srp`

And now, when you log into SeAT, you should see a 'Ship Replacement Program' link on the left.

## Price Provider Setup

In order to use this plugin you must have configured at least one PriceProvider. See [here](https://github.com/recursivetree/seat-prices-core) for available providers.

## SRP Payout Calculations

### Simple SRP

By default, the application is configured in simple mode. In this mode, the SRP payout is calculated by using the for the whole killmail.

### Advanced SRP

Advanced SRP can be enabled in the settings menu. Once enabled, the SRP Admin will need to specify rules around payout calculations. The rule types available are `Type`, `Group` and `Default`. The rules are matched in that order with the first match being used to calculate payout value.

#### Shared Configuration Options

- **Price Source** - Where the pricing of individual elements will be drawn from
- **Base Value** - A fixed ISK amount added to each payout from this rule
- **Hull %** - The percentage of the ship hull value to be paid out. 
- **Fit %** - The percentage of the ship fit value to be paid out. 
- **Cargo %** - The percentage of the ship cargo value to be paid out. 
- **Deduct Insurance** - If selected, the payout will be reduced by the benefit gained from insurance (payout - cost)

#### Rule Types

##### Type Rules
Type rules match the ship type exactly, for example a Scorpion or Blackbird. Note that variants are considered separate ships. Ie a Raven is different to a Raven Navy Issue. 

##### Group Rules
Group rules match based on the group of the ship, such as `Frigate`, `Shuttle` or `Battleship`.

##### Default Rule
The default rule is the rule used when there are no type or group rules that have been triggered. The default rule is a catch all for any remaining payout calculations.

## Discord Webhook (optional)

Automated notifications of new SRP Requests submitted in Discord

***In Discord application:***

1. On a channel of your choice, click the cog icon to open the channel settings
2. In the channel settings, navigate to the Webhooks tab
3. Click `Create Webhook`
4. Fill in name for the webhook and (optional) image
5. Copy the Webhook URL
6. Click `Save` to finish creating the webhook

***In SeAT file:***

The Ship Replacement Program Settings page accepts two variables for the webhook:

1. (required) `Webhook URL`: this is the url you copied when creating the webhook in Discord
2. (optional) `Discord Mention Role`: this can be a room mention (e.g. `@here`), a Discord role ID, or a specific user ID
        - Role ID and User ID can be obtained by typing `/@rolename` into a channel (e.g. `/@srp_manager`) 


Example of entries:

```
Webhook URL = https://discordapp.com/api/webhooks/513619798362554369/Px9VQwiE5lhhBqOjW7rFBuLmLzMimwcklC2kIDJhQ9hLcDzCRPCkbI0LgWq6YwIbFtuk
Discord Mention Role = <@&198725153385873409>
```


Good luck, and Happy Hunting!!  o7


## Usage Tracking

In order to get an idea of the usage of this plugin, a very simplistic form of anonymous usage tracking has been implemented.

Read more about the system in use [here](https://github.com/Crypta-Eve/snoopy)
