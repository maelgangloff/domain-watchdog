<?php

namespace App\Config;

use Symfony\Component\Notifier\Bridge\Discord\DiscordTransportFactory;
use Symfony\Component\Notifier\Bridge\Engagespot\EngagespotTransportFactory;
use Symfony\Component\Notifier\Bridge\GoogleChat\GoogleChatTransportFactory;
use Symfony\Component\Notifier\Bridge\Mattermost\MattermostTransportFactory;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\MicrosoftTeamsTransportFactory;
use Symfony\Component\Notifier\Bridge\Ntfy\NtfyTransportFactory;
use Symfony\Component\Notifier\Bridge\Pushover\PushoverTransportFactory;
use Symfony\Component\Notifier\Bridge\RocketChat\RocketChatTransportFactory;
use Symfony\Component\Notifier\Bridge\Slack\SlackTransportFactory;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransportFactory;
use Symfony\Component\Notifier\Bridge\Zulip\ZulipTransportFactory;

enum WebhookScheme: string
{
    case DISCORD = 'discord';
    case GOOGLE_CHAT = 'googlechat';
    case MATTERMOST = 'mattermost';
    case MICROSOFT_TEAMS = 'microsoftteams';
    case ROCKET_CHAT = 'rocketchat';
    case SLACK = 'slack';
    case TELEGRAM = 'telegram';
    case ZULIP = 'zulip';
    case PUSHOVER = 'pushover';
    case NTFY = 'ntfy';
    case ENGAGESPOT = 'engagespot';

    public function getChatTransportFactory(): string
    {
        return match ($this) {
            WebhookScheme::DISCORD => DiscordTransportFactory::class,
            WebhookScheme::GOOGLE_CHAT => GoogleChatTransportFactory::class,
            WebhookScheme::MATTERMOST => MattermostTransportFactory::class,
            WebhookScheme::MICROSOFT_TEAMS => MicrosoftTeamsTransportFactory::class,
            WebhookScheme::ROCKET_CHAT => RocketChatTransportFactory::class,
            WebhookScheme::SLACK => SlackTransportFactory::class,
            WebhookScheme::TELEGRAM => TelegramTransportFactory::class,
            WebhookScheme::ZULIP => ZulipTransportFactory::class,
            WebhookScheme::PUSHOVER => PushoverTransportFactory::class,
            WebhookScheme::NTFY => NtfyTransportFactory::class,
            WebhookScheme::ENGAGESPOT => EngagespotTransportFactory::class
        };
    }
}
