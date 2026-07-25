<?php

// Local-dev stand-in for DiscordOAuthClient - returns fabricated data instead
// of talking to discord.com, so the real "Sign up with Discord" button can be
// clicked through entirely locally. Only ever wired in when environment=local.

namespace services;

class FakeDiscordOAuthClient implements DiscordOAuthClientInterface
{
    public function getAccessToken(string $code, string $clientId, string $clientSecret, string $redirectUri): ?array
    {
        return [
            'access_token' => 'mock-discord-access-token',
            'refresh_token' => 'mock-discord-refresh-token',
            'expires_in' => 604800,
        ];
    }

    public function getUserInfo(string $accessToken): ?array
    {
        return [
            'id' => $_GET['mockDiscordId'] ?? 'mock-discord-1',
            'username' => $_GET['mockDiscordUsername'] ?? 'MockDiscordUser',
            'email' => $_GET['mockDiscordEmail'] ?? 'mockdiscord@local.test',
            'avatar' => null,
        ];
    }
}
