<?php

namespace services;

interface RiotOAuthClientInterface
{
    /**
     * Exchanges an authorization code for a Riot RSO access token, or null
     * if the exchange failed.
     */
    public function getAccessToken(string $code, string $clientId, string $clientSecret): ?string;

    /**
     * Fetches the Riot account (puuid, gameName, tagLine) for a given
     * access token.
     */
    public function getUserData(string $accessToken): ?array;
}
