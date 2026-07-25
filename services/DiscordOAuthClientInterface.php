<?php

namespace services;

interface DiscordOAuthClientInterface
{
    /**
     * Exchanges an authorization code for a Discord access token.
     * Returns the decoded token response (access_token, refresh_token,
     * expires_in, ...), or null if Discord didn't return an access_token.
     */
    public function getAccessToken(string $code, string $clientId, string $clientSecret, string $redirectUri): ?array;

    /**
     * Fetches the Discord user (id, username, email, avatar, ...) for a
     * given access token, or null if Discord didn't return a user id.
     */
    public function getUserInfo(string $accessToken): ?array;
}
