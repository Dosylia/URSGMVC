<?php

// Local-dev stand-in for RiotOAuthClient - returns fabricated data instead of
// talking to Riot's servers, so the real Riot sign-up button can be clicked
// through entirely locally. Only ever wired in when environment=local.

namespace services;

class FakeRiotOAuthClient implements RiotOAuthClientInterface
{
    public function getAccessToken(string $code, string $clientId, string $clientSecret): ?string
    {
        return 'mock-riot-access-token';
    }

    public function getUserData(string $accessToken): ?array
    {
        return [
            'puuid' => $_GET['mockRiotPuuid'] ?? 'mock-riot-puuid-1',
            'gameName' => $_GET['mockRiotGameName'] ?? 'MockSummoner',
            'tagLine' => $_GET['mockRiotTagLine'] ?? 'NA1',
        ];
    }
}
