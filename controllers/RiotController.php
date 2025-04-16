<?php

namespace controllers;

use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use models\GoogleUser;
use models\UserLookingFor;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use traits\SecurityController;

require 'vendor/autoload.php';

class RiotController
{
    use SecurityController;
    private LeagueOfLegends $leagueOfLegends;
    private User $user;
    private Valorant $valorant;
    private GoogleUser $googleUser;
    private UserLookingFor $userlookingfor;
    private $tokenEndpoint = 'https://auth.riotgames.com/token';
    private $authorizeUrl = 'https://auth.riotgames.com/oauth2/authorize';

    public function __construct()
    {
        // Initialize models
        $this->leagueOfLegends = new LeagueOfLegends();
        $this->user = new User();
        $this->valorant = new Valorant();
        $this -> googleUser = new GoogleUser();
        $this -> userlookingfor = new userLookingFor();
    }

    // Redirect user to Riot's OAuth authorization URL
    public function riotAccount()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
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
                        $existingUser = $this->googleUser->getUserByPuuidGoogle($puuid);

                        if ($existingUser)
                        {
                            header('Location: /userProfile?message=This League of Legends account is already used on URSG.');
                            exit();
                        }
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

                                if ($summonerProfile === null) {
                                    header('Location: /userProfile?message=Your League of Legends region does not match the account.');
                                    exit();
                                }

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
                                    $updateSummoner = $this->leagueOfLegends->updateSummonerData(
                                        $userData['gameName'], 
                                        $summonerProfile['id'],
                                        $puuid,
                                        $summonerProfile['summonerLevel'], 
                                        $rankAndTier,
                                        $profileIconId,
                                        $fullAccountName,
                                        $user['user_id'],
                                    );

                                    if (!$updateSummoner) {
                                        header('Location: /userProfile?message=Couldnt bind account');
                                        exit();
                                    }
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
        } else {
            require_once 'keys.php';
            // Step 1: Redirect user to Riot's authorization URL
            if (!isset($_GET['code'])) {
                header('Location: /?message=Error with code');
                exit();
            } else {
                // Step 2: Riot redirects back with an authorization code
                $authCode = $_GET['code'];

                // Step 3: Exchange the authorization code for an access token
                $accessToken = $this->getAccessToken($authCode, $riotClientId, $riotClientSecret);

                // Step 4: Fetch user data using the access token
                if ($accessToken) {
                    $userData = $this->getUserData($accessToken);
                    $addPuuidLeague = false;
                    $addPuuidValorant = false;
                    $puuid = $userData['puuid'];

                    // Check if puuid is not empty before attempting to bind
                    if ($puuid) {

                        // Check if this puuid exists in the database, and if it's associated with a google account or not
                        $existingUser = $this->googleUser->getUserByPuuid($puuid);

                        if ($existingUser)
                        {
                            if ($existingUser['google_createdWithRSO'] === 1)
                            {
                                if (isset($existingUser['google_masterTokenWebsite']) && $existingUser['google_masterTokenWebsite'] !== null && !empty($existingUser['google_masterTokenWebsite'])) {
                                    $token = $existingUser['google_masterTokenWebsite'];
                                } else {
                                    $token = bin2hex(random_bytes(32));
                                    $createToken = $this->googleUser->storeMasterTokenWebsite($existingUser['google_userId'], $token);
                                }

                                $_SESSION['google_userId'] = $existingUser['google_userId'];
                                $_SESSION['google_id'] = $puuid;
                                $_SESSION['email'] = $existingUser['google_email'];
                                $_SESSION['full_name'] = $existingUser['google_fullName'];
                                $_SESSION['google_firstName'] = $existingUser['google_firstName'];
                                $_SESSION['masterTokenWebsite'] = $token;
                                $_SESSION['tagLine'] = $userData['tagLine'];
                                $_SESSION['full_name'] = $userData['gameName'];

                                $googleUser = $this->user->getUserDataByGoogleUserId($existingUser['google_userId']);

                                if ($googleUser)
                                {
                                    $user = $this->user->getUserByUsername($googleUser['user_username']);

                                    if ($user) 
                                    {
                                        $_SESSION['userId'] = $user['user_id'];
                                        $_SESSION['username'] = $user['user_username'];
                                        $_SESSION['gender'] = $user['user_gender'];
                                        $_SESSION['age'] = $user['user_age'];
                                        $_SESSION['kindOfGamer'] = $user['user_kindOfGamer'];
                                        $_SESSION['game'] = $user['user_game'];

                                        if ($user['user_game'] == 'League of Legends') {
                                            $lolUser = $this->leagueOfLegends->getLeageUserByUserId($user['user_id']);

                                            if ($lolUser)
                                            {
                                                $_SESSION['lol_id'] = $lolUser['lol_id'];
                                                $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

                                                if ($lfUser)
                                                {
                                                    $_SESSION['lf_id'] = $lfUser['lf_id'];
                                                    header('Location: /swiping?message=Connected successfully.');
                                                    exit();
                                                }
                                                else 
                                                {
                                                    header('Location: /signup?message=Create your Looking for account.');
                                                    exit();
                                                }
                                            }
                                            else 
                                            {
                                                header('Location: /signup?message=Create your LoL account.');
                                                exit();
                                            }
                                        }
                                        else 
                                        {
                                            $valorantUser = $this->valorant->getValorantUserByUserId($user['user_id']);

                                            if ($valorantUser)
                                            {

                                                $_SESSION['valorant_id'] = $valorantUser['valorant_id'];
                                
                                                $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
                                                if ($lfUser)
                                                {
                                                    $_SESSION['lf_id'] = $lfUser['lf_id'];
                                                    header('Location: /swiping?message=Connected successfully.');
                                                    exit();
                                                }
                                                else 
                                                {
                                                    header('Location: /signup?message=Create your Looking for account.');
                                                    exit();
                                                }

                                            }
                                            else 
                                            {
                                                header('Location: /signup?message=Create your Valorant account.');
                                                exit();
                                            }

                                        }

                                    }
                                    else 
                                    {
                                        header('Location: /signup?message=Create your account.');
                                        exit();
                                    }
                                }
                                else 
                                {
                                    header('Location: /signup?message=Create your account.');
                                    exit();
                                }

                            }
                            else
                            {
                                header('Location: /?message=This League of Legends account is already used on URSG.');
                                exit();
                            }

                        }
                        else 
                        {
                            $_SESSION['riot_id'] = $puuid;
                            $fakeEmail = "riot_{$puuid}@fake.riot";
                            // Create a new account
                            $RSO = 1;
                            $fullName = $userData['gameName'];
                            $firstName = $userData['gameName'];
                            $googleFamilyName = $userData['gameName'];
                            $createGoogleUserRiot = $this->googleUser->createGoogleUser($puuid, $fullName, $firstName, $googleFamilyName,  $RSO, $fakeEmail);

                            if ($createGoogleUserRiot)
                            {
                                require 'keys.php';
                
                                $lifetime = 7 * 24 * 60 * 60;
                
                                session_destroy();
                
                                session_set_cookie_params($lifetime);
                
                                if (session_status() == PHP_SESSION_NONE) {
                                    session_start();
                                }
            
                                // MASTER TOKEN SYSTEM
                                $token = bin2hex(random_bytes(32));
                                $createToken = $this->googleUser->storeMasterTokenWebsite($createGoogleUserRiot, $token);
            
                                if ($createToken) {
                                    $_SESSION['masterTokenWebsite'] = $token;
                                }
                                
                                if (!isset($_SESSION['googleId'])) {
                                    $_SESSION['google_userId'] = $createGoogleUserRiot;
                                    $_SESSION['google_id'] = $puuid;
                                    $_SESSION['email'] = $fakeEmail;
                                    $_SESSION['tagLine'] = $userData['tagLine'];
                                    $_SESSION['full_name'] = $fullName;
                                }

                                header('Location: /signup?message=Account created');
                                exit();

                            }

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
        
    }

    public function getGameStatusLoL()
    {
        if (isset($_POST['friendId']))
        {
            $friendId = $_POST['friendId'];
            $user = $this->user->getUserById($friendId);

            if ($user['lol_verified']) 
            {
                require_once 'keys.php';
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

                $gameStatus = $this->getGameStatus($user['lol_sPuuid'], $selectedRegionValue, $apiKey);

                if ($gameStatus && isset($gameStatus['gameId'])) {
                    $playerChampionId = null;
                    $playerData = null;

                    foreach ($gameStatus['participants'] as $participant) {
                        if ($participant['puuid'] === $user['lol_sPuuid']) {
                            $playerChampionId = $participant['championId'];
                            $playerData = $participant; // Save full data in case you want more info later
                            break;
                        }
                    }

                    $versionJson = file_get_contents("https://ddragon.leagueoflegends.com/api/versions.json");
                    $latestVersion = json_decode($versionJson, true)[0];

                    $championJson = file_get_contents("https://ddragon.leagueoflegends.com/cdn/{$latestVersion}/data/en_US/champion.json");
                    $championData = json_decode($championJson, true)['data'];

                    $championName = $this->getChampionNameById($playerChampionId, $championData);

                    $response = [
                        'success' => true,
                        'gameId' => $gameStatus['gameId'],
                        'region' => $selectedRegionValue,
                        'gameMode' => $gameStatus['gameMode'],
                        'mapId' => $gameStatus['mapId'],
                        'champion' => $championName,
                    ];
                } else {
                    $response = ['success' => false, 'error' => 'No active game found'];
                }
            }
            echo json_encode($response);
            return;
        }
        else
        {
            echo json_encode(['success' => false, 'error' => 'Wrong request']);
            return;
        }
    }

    public function getChampionNameById($championId, $championData) 
    {
        foreach ($championData as $champion) {
            if ((int)$champion['key'] === (int)$championId) {
                return $champion['name']; 
            }
        }
        return null; // ✅ only return null if nothing matched
    }

    public function getGameStatus($puuid, $region, $apiKey)
    {
        $url = "https://$region.api.riotgames.com/lol/spectator/v5/active-games/by-summoner/$puuid?api_key=$apiKey";
    
        $response = @file_get_contents($url);
    
        if ($response === false) {
            return null;
        }
    
        return json_decode($response, true);
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