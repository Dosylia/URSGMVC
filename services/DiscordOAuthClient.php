<?php

namespace services;

class DiscordOAuthClient implements DiscordOAuthClientInterface
{
    public function getAccessToken(string $code, string $clientId, string $clientSecret, string $redirectUri): ?array
    {
        $tokenUrl = "https://discord.com/api/oauth2/token";
        $data = [
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $redirectUri,
        ];

        $options = [
            "http" => [
                "header" => "Content-Type: application/x-www-form-urlencoded",
                "method" => "POST",
                "content" => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($tokenUrl, false, $context);
        $tokenInfo = json_decode($response, true);

        if (!isset($tokenInfo['access_token'])) {
            return null;
        }

        return $tokenInfo;
    }

    public function getUserInfo(string $accessToken): ?array
    {
        $userInfoUrl = "https://discord.com/api/users/@me";
        $options = [
            "http" => [
                "header" => "Authorization: Bearer " . $accessToken,
                "method" => "GET",
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($userInfoUrl, false, $context);
        $userInfo = json_decode($response, true);

        if (!isset($userInfo['id'])) {
            return null;
        }

        return $userInfo;
    }
}
