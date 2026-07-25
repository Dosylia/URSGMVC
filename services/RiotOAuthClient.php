<?php

namespace services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class RiotOAuthClient implements RiotOAuthClientInterface
{
    private $tokenEndpoint = 'https://auth.riotgames.com/token';

    public function getAccessToken(string $code, string $clientId, string $clientSecret): ?string
    {
        $client = new Client();

        try {
            $response = $client->post($this->tokenEndpoint, [
                'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => 'https://ur-sg.com/riotAccount',
                ]
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['access_token'])) {
                return $responseBody['access_token'];
            }

            return null;
        } catch (RequestException $e) {
            error_log('RequestException: ' . $e->getMessage());
            if ($e->hasResponse()) {
                error_log('Response: ' . $e->getResponse()->getBody()->getContents());
            }
            return null;
        }
    }

    public function getUserData(string $accessToken): ?array
    {
        $url = 'https://europe.api.riotgames.com/riot/account/v1/accounts/me';
        $headers = [
            'Authorization: Bearer ' . $accessToken
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}
