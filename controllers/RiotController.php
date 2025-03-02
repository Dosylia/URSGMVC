<?php

namespace controllers;

use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

require 'vendor/autoload.php';

class RiotController
{
    private LeagueOfLegends $leagueOfLegends;
    private User $user;
    private Valorant $valorant;
    private $tokenEndpoint = 'https://auth.riotgames.com/token';
    private $authorizeUrl = 'https://auth.riotgames.com/oauth2/authorize';

    public function __construct()
    {
        // Initialize models
        $this->leagueOfLegends = new LeagueOfLegends();
        $this->user = new User();
        $this->valorant = new Valorant();
    }

    // Redirect user to Riot's OAuth authorization URL
    public function riotAccount()
    {
        require_once 'keys.php';
        // Step 1: Redirect user to Riot's authorization URL
        if (!isset($_GET['code'])) {
            header('Location: /userProfile?message=Error with code');
            exit();
        } else {
            // Step 2: Riot redirects back with an authorization code
            $authCode = $_GET['code'];

            // Step 3: Exchange the authorization code for an access token
            $accessToken = $this->getAccessToken($authCode, $riotClientId, $riotClientSecret);

            // Step 4: Fetch user data using the access token
            if ($accessToken) {
                $userData = $this->getUserData($accessToken);
                $user = $this->user->getUserById($_SESSION['userId']);
                $addPuuidLeague = false;
                $addPuuidValorant = false;
                $puuid = $userData['puuid'];

                // Check if puuid is not empty before attempting to bind
                if ($puuid) {
                    if ($user['lol_id']) {
                        $addPuuidLeague = $this->leagueOfLegends->addPuuid($puuid, $_SESSION['userId']);
                    }

                    if ($user['valorant_id']) {
                        $addPuuidValorant = $this->valorant->addPuuid($puuid, $_SESSION['userId']);
                    }

                    // Check if either addPuuid was successful
                    if ($addPuuidLeague || $addPuuidValorant) {
                        if ($addPuuidLeague) {
                            // Now make a call to get the summoner's profile data
                            $regionMap = [
                                "Europe West" => "euw1",
                                "North America" => "na1",
                                "Europe Nordic" => "eun1",
                                "Brazil" => "br1",
                                "Latin America North" => "la1",
                                "Latin America South" => "la2",
                                "Oceania" => "oc1",
                                "Russia" => "ru1",
                                "Turkey" => "tr1",
                                "Japan" => "jp1",
                                "Korea" => "kr",
                            ];

                            $selectedRegionValue = $regionMap[$user['lol_server']] ?? null;

                            // Fetch the summoner profile to get profileIconId
                            $summonerProfile = $this->getSummonerProfile($puuid, $selectedRegionValue, $apiKey);

                            // Now you can access the profileIconId
                            $profileIconId = $summonerProfile['profileIconId'];

                            // Fetch ranked stats
                            $summonerRankedStats = $this->getSummonerRankedStats($summonerProfile['id'], $selectedRegionValue, $apiKey);

                            if (isset($summonerRankedStats)) {
                                // Default to 'Unranked'
                                $rankAndTier = 'Unranked';
                                $soloQueueRankAndTier = null;
                                $flexQueueRankAndTier = null;

                                // Loop through the ranked stats array to find the desired queue types
                                foreach ($summonerRankedStats as $rankedStats) {
                                    if ($rankedStats['queueType'] === 'RANKED_SOLO_5x5') {
                                        $soloQueueRankAndTier = $rankedStats['tier'] . ' ' . $rankedStats['rank'];
                                    } elseif ($rankedStats['queueType'] === 'RANKED_FLEX_SR') {
                                        $flexQueueRankAndTier = $rankedStats['tier'] . ' ' . $rankedStats['rank'];
                                    }
                                }

                                // Prioritize solo queue rank, if available
                                if ($soloQueueRankAndTier !== null) {
                                    $rankAndTier = $soloQueueRankAndTier;
                                } elseif ($flexQueueRankAndTier !== null) {
                                    $rankAndTier = $flexQueueRankAndTier;
                                }

                                $fullAccountName = $userData['gameName'] . '#' . $userData['tagLine']; 

                                // $topChamps = $this->getTopPlayedChamps($puuid, $selectedRegionValue, $apiKey);

                                // Save updated summoner data to the database
                                $this->leagueOfLegends->updateSummonerData(
                                    $userData['gameName'], 
                                    $summonerProfile['id'],
                                    $puuid,
                                    $summonerProfile['summonerLevel'], 
                                    $rankAndTier,
                                    $profileIconId,
                                    $fullAccountName,
                                    $user['user_id'],
                                );
                            }
                        }

                        // if ($addPuuidValorant) {
                        //     // Now make a call to get the Valorant player profile data
                        //     $valorantProfile = $this->getValorantProfile($userData['puuid'], $apiKey);
            
                        //     // Fetch the current act ID
                        //     $actId = $this->getCurrentActId($apiKey);
            
                        //     // Now fetch the rank and profileIconId
                        //     $valorantLevel = $valorantProfile['accountLevel'] ?? null; // Assuming 'accountLevel' gives the level
                        //     $valorantRankData = $this->getValorantRank($userData['puuid'], $apiKey, $actId);
                        //     $valorantRank = $valorantRankData['rank'] ?? 'Unranked'; // Assuming 'rank' provides the rank
                        //     $profileIconId = $valorantProfile['profileIconId'] ?? null; // Assuming 'profileIconId' is available in profile data
            
                        //     // Save Valorant data to the database
                        //     $this->valorant->updateValorantRiot(
                        //         $valorantProfile['gameName'], 
                        //         $valorantRank,
                        //         $valorantLevel,
                        //         $profileIconId,
                        //         $user['user_id']
                        //     );
                        // }

                        header('Location: /userProfile?message=Binded successfully');
                        exit();
                    } else {
                        header('Location: /userProfile?message=Couldnt find Puuid');
                        exit();
                    }
                } else {
                    // Handle case where puuid is empty
                    header('Location: /userProfile?message=No Puuid received'.$accessToken);
                    exit();
                }
            } else {
                error_log('Failed to obtain access token. Authorization code: ' . $authCode);
                header('Location: /&message=NO_ACCESS_TOKEN' . $authCode);
                exit();
            }
        }
    }

    // Fetch the summoner profile details
    public function getSummonerProfile($puuid, $server, $apiKey) {
        $url = "https://". strtolower($server) .".api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{$puuid}?api_key={$apiKey}";
        return json_decode(file_get_contents($url), true);
    }

    // Fetch ranked stats for the summoner
    public function getSummonerRankedStats($summonerId, $server, $apiKey) {
        $url = "https://". strtolower($server) .".api.riotgames.com/lol/league/v4/entries/by-summoner/{$summonerId}?api_key={$apiKey}";
        return json_decode(file_get_contents($url), true);
    }

    public function getTopPlayedChamps($puuid, $server, $apiKey) {
        $url = "https://". strtolower($server) .".api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-puuid/{$puuid}?api_key={$apiKey}";
        $response = json_decode(file_get_contents($url), true);
    
        if (!$response || empty($response)) {
            return [];
        }
    
        // Get the top 3 champions
        return array_slice($response, 0, 3);
    }

    public function riotAccountPhone()
    {
        if (!isset($_GET['code'])) {
            $response = array('message' => 'Error');
            echo json_encode($response);
            exit;
        } else {
            $response = array('message' => 'Success', 'code' => $_GET['code']);
            echo json_encode($response);
            exit;
        }
    }

    public function RiotCodePhone()
    {
        if (isset($_POST['dataToSend']))
        {
            $data = json_decode($_POST['dataToSend']);
            
            $userId = $this->$data->userId;
            $authCode = $this->$data->code;

            require_once 'keys.php';

            $accessToken = $this->getAccessToken($authCode, $riotClientId, $riotClientSecret);

            if ($accessToken) {
                $userData = $this->getUserData($accessToken);
                $user = $this->user->getUserById($userId);
                $addPuuidLeague = false;
                $addPuuidValorant = false;
                $puuid = $userData['puuid'];

                // Check if puuid is not empty before attempting to bind
                if ($puuid) {
                    if ($user['lol_id']) {
                        $addPuuidLeague = $this->leagueOfLegends->addPuuid($puuid, $userId);
                    }

                    if ($user['valorant_id']) {
                        $addPuuidValorant = $this->valorant->addPuuid($puuid, $userId);
                    }

                    // Check if either addPuuid was successful
                    if ($addPuuidLeague || $addPuuidValorant) {
                        if ($addPuuidLeague) {
                            // Now make a call to get the summoner's profile data
                            $regionMap = [
                                "Europe West" => "euw1",
                                "North America" => "na1",
                                "Europe Nordic" => "eun1",
                                "Brazil" => "br1",
                                "Latin America North" => "la1",
                                "Latin America South" => "la2",
                                "Oceania" => "oc1",
                                "Russia" => "ru1",
                                "Turkey" => "tr1",
                                "Japan" => "jp1",
                                "Korea" => "kr",
                            ];

                            $selectedRegionValue = $regionMap[$user['lol_server']] ?? null;

                            // Fetch the summoner profile to get profileIconId
                            $summonerProfile = $this->getSummonerProfile($puuid, $selectedRegionValue, $apiKey);

                            // Now you can access the profileIconId
                            $profileIconId = $summonerProfile['profileIconId'];

                            // Fetch ranked stats
                            $summonerRankedStats = $this->getSummonerRankedStats($summonerProfile['id'], $selectedRegionValue, $apiKey);

                            if (isset($summonerRankedStats)) {
                                // Default to 'Unranked'
                                $rankAndTier = 'Unranked';
                                $soloQueueRankAndTier = null;
                                $flexQueueRankAndTier = null;

                                // Loop through the ranked stats array to find the desired queue types
                                foreach ($summonerRankedStats as $rankedStats) {
                                    if ($rankedStats['queueType'] === 'RANKED_SOLO_5x5') {
                                        $soloQueueRankAndTier = $rankedStats['tier'] . ' ' . $rankedStats['rank'];
                                    } elseif ($rankedStats['queueType'] === 'RANKED_FLEX_SR') {
                                        $flexQueueRankAndTier = $rankedStats['tier'] . ' ' . $rankedStats['rank'];
                                    }
                                }

                                // Prioritize solo queue rank, if available
                                if ($soloQueueRankAndTier !== null) {
                                    $rankAndTier = $soloQueueRankAndTier;
                                } elseif ($flexQueueRankAndTier !== null) {
                                    $rankAndTier = $flexQueueRankAndTier;
                                }

                                // Save updated summoner data to the database
                                $this->leagueOfLegends->updateSummonerData(
                                    $userData['gameName'], 
                                    $summonerProfile['id'],
                                    $puuid,
                                    $summonerProfile['summonerLevel'], 
                                    $rankAndTier,
                                    $profileIconId,
                                    $user['user_id']
                                );
                            }
                        }

                        $response = array('message' => 'Success');
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = array('message' => 'Couldnt find Puuid');
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    // Handle case where puuid is empty
                    $response = array('message' => 'No Puuid received');
                    echo json_encode($response);
                    exit;
                }
            }

        }
        else
        {
            $response = array('message' => 'Error');
            echo json_encode($response);
            exit;
        }
    }

    // public function getValorantProfile($puuid, $apiKey) {
    //     $url = "https://americas.api.riotgames.com/riot/account/v1/accounts/by-puuid/{$puuid}?api_key={$apiKey}";
    //     return json_decode(file_get_contents($url), true);
    // }

    // public function getCurrentActId($apiKey) {
    //     $url = "https://americas.api.riotgames.com/val/content/v1/contents?api_key={$apiKey}";
    //     $response = json_decode(file_get_contents($url), true);
    
    //     foreach ($response['acts'] as $act) {
    //         if ($act['isActive']) {
    //             return $act['id'];
    //         }
    //     }
    
    //     return null;
    // }
    
    // // Fetch ranked stats for the Valorant player
    // public function getValorantRank($puuid, $apiKey, $actId) {
    //     $url = "https://americas.api.riotgames.com/val/ranked/v1/leaderboards/by-act/{$actId}?size=200&startIndex=0&api_key={$apiKey}";
    //     $response = json_decode(file_get_contents($url), true);
    
    //     // Find the player in the leaderboard
    //     foreach ($response['players'] as $player) {
    //         if ($player['puuid'] === $puuid) {
    //             return $player;
    //         }
    //     }
    
    //     return null;
    // }

    // Exchange the authorization code for an access token
    public function getAccessToken($authCode, $clientId, $clientSecret)
    {
        $client = new Client();

        try {
            $response = $client->post($this->tokenEndpoint, [
                'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $authCode, 
                'redirect_uri' => 'https://ur-sg.com/riotAccount', 
                ]
            ]);

            // Handle response
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['access_token'])) {
                return $responseBody['access_token'];
            }

            error_log('Access token not found in response: ' . json_encode($responseBody));
            return null;
        } catch (RequestException $e) {
            // Log the error message for debugging
            error_log('RequestException: ' . $e->getMessage());
            if ($e->hasResponse()) {
                error_log('Response: ' . $e->getResponse()->getBody()->getContents());
            }
            return null;
        }
    }

    // Fetch user data from Riot API using the access token
    public function getUserData($accessToken)
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