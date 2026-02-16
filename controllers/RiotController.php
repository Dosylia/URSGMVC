<?php

namespace controllers;

use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use models\GoogleUser;
use models\UserLookingFor;
use models\Items;
use models\RatingGames;
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
    private Items $items;
    private RatingGames $rating;
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
        $this->items = new Items();
        $this -> rating = new RatingGames();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    // Redirect user to Riot's OAuth authorization URL
    public function riotAccount()
    {   
        // Step 1: Redirect user to Riot's authorization URL
        $isMobile = isset($_SESSION['riotConnectMobile']) ? true : false;
        if (!isset($_GET['code'])) {
            if ($isMobile) {
                $this->handleMobileFlowFailure('No authorization code received');
            } else {
                header('Location: /?message=Error with code');
            }
        }

        require_once 'keys.php';

        // Step 2: Riot redirects back with an authorization code
        $authCode = $_GET['code'];

        if ($isMobile) {
            $this->handleMobileFlow($authCode, $riotClientId, $riotClientSecret, $apiKey);
        }
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {

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

                    if ($existingUser && $existingUser['google_userId'] != $_SESSION['google_userId'])
                    {
                        header('Location: /userProfile?message=This League of Legends account is already used on URSG.');
                        return;
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
                                "Europe Nordic & East" => "eun1",
                                "Europe Nordic &amp;" => "eun1",
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
                                return;
                            }

                            // Now you can access the profileIconId
                            $profileIconId = $summonerProfile['profileIconId'];

                            // Fetch ranked stats
                            $summonerRankedStats = $this->getSummonerRankedStats($puuid, $selectedRegionValue, $apiKey);

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
                                    'Removed',
                                    $puuid,
                                    $summonerProfile['summonerLevel'], 
                                    $rankAndTier,
                                    $profileIconId,
                                    $fullAccountName,
                                    $user['user_id'],
                                );

                                if (!$updateSummoner) {
                                    header('Location: /userProfile?message=Couldnt bind account');
                                    return;
                                }


                                $badge = $this->items->getBadgeByName("Riot account");
                                if ($badge && !$this->items->userOwnsItem($user['user_id'], $badge['items_id'])) {
                                    $this->items->addItemToUser($user['user_id'], $badge['items_id']);
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
                        return;
                    } else {
                        header('Location: /userProfile?message=Couldnt find Puuid');
                        return;
                    }
                } else {
                    // Handle case where puuid is empty
                    header('Location: /userProfile?message=No Puuid received'.$accessToken);
                    return;
                }
            } else {
                error_log('Failed to obtain access token. Authorization code: ' . $authCode);
                header('Location: /&message=NO_ACCESS_TOKEN' . $authCode);
                return;
            }
        } else {
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

                            setcookie("auth_token", $token, [
                                'expires' => time() + 60 * 60 * 24 * 60,
                                'path' => '/',
                                'secure' => true,
                                'httponly' => true,
                                'samesite' => 'Strict',
                            ]);

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
                                                return;
                                            }
                                            else 
                                            {
                                                header('Location: /signup?message=Create your Looking for account.');
                                                return;
                                            }
                                        }
                                        else 
                                        {
                                            header('Location: /signup?message=Create your LoL account.');
                                            return;
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
                                                return;
                                            }
                                            else 
                                            {
                                                header('Location: /signup?message=Create your Looking for account.');
                                                return;
                                            }

                                        }
                                        else 
                                        {
                                            header('Location: /signup?message=Create your Valorant account.');
                                            return;
                                        }

                                    }

                                }
                                else 
                                {
                                    header('Location: /signup?message=Create your account.');
                                    return;
                                }
                            }
                            else 
                            {
                                header('Location: /signup?message=Create your account.');
                                return;
                            }

                        }
                        else
                        {
                            header('Location: /?message=This League of Legends account is already used on URSG.');
                            return;
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
            
                            $lifetime = 7 * 24 * 60 * 60;
            
                            session_destroy();
            
                            session_set_cookie_params($lifetime);
            
                            if (session_status() == PHP_SESSION_NONE) {
                                session_start();
                            }
        
                            // MASTER TOKEN SYSTEM
                            $token = bin2hex(random_bytes(32));
                            $createToken = $this->googleUser->storeMasterTokenWebsite($createGoogleUserRiot, $token);

                            setcookie("auth_token", $token, [
                                'expires' => time() + 60 * 60 * 24 * 60,
                                'path' => '/',
                                'secure' => true,
                                'httponly' => true,
                                'samesite' => 'Strict',
                            ]);
        
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
                            return;

                        }

                    }
                } else {
                    // Handle case where puuid is empty
                    header('Location: /userProfile?message=No Puuid received'.$accessToken);
                    return;
                }
            } else {
                error_log('Failed to obtain access token. Authorization code: ' . $authCode);
                header('Location: /&message=NO_ACCESS_TOKEN' . $authCode);
                return;
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

                    echo json_encode([
                        'success' => true,
                        'gameId' => $gameStatus['gameId'],
                        'region' => $selectedRegionValue,
                        'gameMode' => $gameStatus['gameMode'],
                        'mapId' => $gameStatus['mapId'],
                        'champion' => $championName,
                    ]);
                    return;
                } else {
                    echo json_encode(['success' => false, 'message' => $this->_('messages.no_active_game_found')]);
                    return;
                }
            }
        }
        else
        {
            echo json_encode(['success' => false, 'message' => $this->_('messages.wrong_request')]);
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
    
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);
    
        $response = @file_get_contents($url, false, $context);
    
        // Check for HTTP errors
        if (isset($http_response_header)) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $match);
            $statusCode = $match[1] ?? 0;
    
            if ($statusCode == '404') {
                // Summoner is not currently in a game — not an error
                return null;
            } elseif ($statusCode != '200') {
                error_log("Riot API error with getGameStatus: HTTP $statusCode");
                return null;
            }
        }
    
        return json_decode($response, true);
    }
    

    // Fetch the summoner profile details
    public function getSummonerProfile($puuid, $server, $apiKey) {
        $url = "https://". strtolower($server) .".api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{$puuid}?api_key={$apiKey}";
        error_log("Fetching Summoner Profile from: $url");

        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if (isset($http_response_header)) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $match);
            $statusCode = $match[1] ?? 0;

            if ($statusCode != '200') {
                error_log("Riot API error when fetching summoner profile.");
                return null;
            }
        } else {
            error_log("No HTTP response header found for summoner profile request.");
            return null;
        }

        return json_decode($response, true);
    }

    // Fetch ranked stats for the summoner
    public function getSummonerRankedStats($puuid, $server, $apiKey) {
        $url = "https://". strtolower($server) .".api.riotgames.com/lol/league/v4/entries/by-puuid/{$puuid}?api_key={$apiKey}";

        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if (isset($http_response_header)) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $match);
            $statusCode = $match[1] ?? 0;

            if ($statusCode != '200') {
                error_log("Riot API error when fetching ranked stats.");
                return null;
            }
        } else {
            error_log("No HTTP response header found for ranked stats request.");
            return null;
        }

        return json_decode($response, true);
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
            echo json_encode(['message' => $this->_('messages.error')]);
            return;
        } else {
            echo json_encode(['message' => $this->_('messages.success'), 'code' => $_GET['code']]);
            return;
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
                            $summonerRankedStats = $this->getSummonerRankedStats($puuid, $selectedRegionValue, $apiKey);

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
                                    'Removed',
                                    $puuid,
                                    $summonerProfile['summonerLevel'], 
                                    $rankAndTier,
                                    $profileIconId,
                                    $user['user_id']
                                );
                            }
                        }

                        echo json_encode(['message' => $this->_('messages.success')]);
                        return;
                    } else {
                        echo json_encode(['message' => $this->_('messages.could_not_find_puuid')]);
                        return;
                    }
                } else {
                    // Handle case where puuid is empty
                    echo json_encode(['message' => $this->_('messages.no_puuid_received')]);
                    return;
                }
            }

        }
        else
        {
            echo json_encode(['message' => $this->_('messages.error')]);
            return;
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

    public function checkIfUsersPlayedTogether()
    {
        if (isset($_POST['friendId']) && isset($_POST['userId'])) {
            $friendId = $_POST['friendId'];
            $userId = $_POST['userId'];

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.unauthorized')]);
                return;
            }

            $token = $matches[1];

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
                return;
            }

            // Get user data
            $user = $this->user->getUserById($userId);
            $friend = $this->user->getUserById($friendId);
            if (!$user || !$friend) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.user_or_friend_not_found')]);
                return;
            }

            // Check if both have LoL accounts
            if (!$user['lol_verified'] || !$friend['lol_verified']) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.no_verified_lol_account')]);
                return;
            }

            require_once 'keys.php';
            $regionMap = [
                "Europe West" => "europe",
                "North America" => "americas",
                "Europe Nordic" => "europe",
                "Brazil" => "americas",
                "Latin America North" => "americas",
                "Latin America South" => "americas",
                "Oceania" => "sea",
                "Russia" => "europe",
                "Turkey" => "europe",
                "Japan" => "asia",
                "Korea" => "asia",
            ];

            $selectedRegionValue = $regionMap[$user['lol_server']] ?? null;

            if (!$selectedRegionValue) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_region')]);
                return;
            }

            // Get match IDs
            $userMatches = $this->getMatchIds($user['lol_sPuuid'], $selectedRegionValue, $apiKey);
            $friendMatches = $this->getMatchIds($friend['lol_sPuuid'], $selectedRegionValue, $apiKey);

            if (!$userMatches || !$friendMatches) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.failed_to_get_match_history')]);
                return;
            }

            // Check intersection
            $commonMatches = array_intersect($userMatches, $friendMatches);
            $orderedCommonMatches = array_values(array_intersect($userMatches, $friendMatches));
            $lastMatchId = $orderedCommonMatches[0] ?? null;
            $playedTogether = false;

            // Check if match already been rated
            if ($lastMatchId) {
                $existingRating = $this->rating->getRatingByMatchId($lastMatchId);
                $playedTogether = !$existingRating; // true if not rated, false if rated
            } else {
                $playedTogether = false;
            }

            echo json_encode([
                'success' => true,
                'playedTogether' => $playedTogether,
                'commonMatches' => array_values($commonMatches) // optional, useful for debugging
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
        }
    }

    public function getMatchIds($puuid, $region, $apiKey)
    {

        // Riot API call to get last 20 matches
        $url = "https://{$region}.api.riotgames.com/lol/match/v5/matches/by-puuid/{$puuid}/ids?start=0&count=20&api_key={$apiKey}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $matchIds = json_decode($response, true);

        return (is_array($matchIds) && !empty($matchIds)) ? $matchIds : false;
    }

    public function connectRiotMobile()
    {
        if (!isset($_GET['phoneData'])) {
            echo json_encode(['success' => false, 'message' => $this->_('messages.missing_phone_data')]);
            header("Location: /?error=Incorrect phone data");
            return;
        }

        // Generate a simple token to mark this as mobile flow
        $riotToken = bin2hex(random_bytes(16));

        $_SESSION['phoneData'] = $_GET['phoneData'];
        $_SESSION['riotConnectMobile'] = $riotToken; // identify mobile flow

        // Redirect to Riot OAuth
        require 'keys.php';
        $riotAuthUrl = "https://auth.riotgames.com/authorize?" . http_build_query([
            'redirect_uri'  => "https://ur-sg.com/riotAccount",
            'client_id'     => $riotClientId,
            'response_type' => 'code',
            'scope'         => 'openid',
        ]);

        header("Location: $riotAuthUrl");
        return;
    }

    public function handleMobileFlow($authCode, $riotClientId, $riotClientSecret, $apiKey)
    {
        $accessToken = $this->getAccessToken($authCode, $riotClientId, $riotClientSecret);

        if (!$accessToken) {
            $this->handleMobileFlowFailure('Failed to obtain access token');
            return;
        }

        $userData = $this->getUserData($accessToken);
        $puuid = $userData['puuid'];

        $existingUser = $this->googleUser->getUserByPuuid($puuid);

        // If user exists, allow connection on mobile, otherwise create account
        if ($existingUser)
        {
            if ($existingUser['google_createdWithRSO'] === 1)
            {
                $step = '';
                if (isset($existingUser['google_masterToken']) && $existingUser['google_masterToken'] !== null && !empty($existingUser['google_masterToken'])) {
                    $token = $existingUser['google_masterToken'];
                } else {
                    $token = bin2hex(random_bytes(32));
                    $createToken = $this->googleUser->storeMasterToken($existingUser['google_userId'], $token);
                }

                $googleUserData = array(
                    'googleId' => $existingUser['google_id'],
                    'fullName' => $existingUser['google_fullName'],
                    'firstName' => $existingUser['google_firstName'],
                    'lastName' => $existingUser['google_lastName'],
                    'email' => $existingUser['google_email'],
                    'googleUserId' => $existingUser['google_userId'],
                    'token' => $token
                );

                setcookie("auth_token", $token, [
                    'expires' => time() + 60 * 60 * 24 * 60,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);


                $googleUser = $this->user->getUserDataByGoogleUserId($existingUser['google_userId']);

                if ($googleUser)
                {
                    $user = $this->user->getUserByUsername($googleUser['user_username']);

                    if ($user) 
                    {
                        $userData = array(
                            'userId' => $user['user_id'],
                            'username' => $user['user_username'],
                            'gender' => $user['user_gender'],
                            'age' => $user['user_age'],
                            'kindOfGamer' => $user['user_kindOfGamer'],
                            'game' => $user['user_game'],
                            'shortBio' => $user['user_shortBio'],
                            'picture' => $user['user_picture'] ?? null,
                            'bonusPicture' => $user['user_bonusPicture'] ?? null,
                            'discord' => $user['user_discord'] ?? null,
                            'twitch' => $user['user_twitch'] ?? null,
                            'instagram' => $user['user_instagram'] ?? null,
                            'twitter' => $user['user_twitter'] ?? null,
                            'bluesky' => $user['user_bluesky'] ?? null,
                            'currency' => $user['user_currency'] ?? null,
                            'isGold' => $user['user_isGold'] ?? null,
                            'isPartner'=> $user['user_isPartner'] ?? null,
                            'isCertified' => $user['user_isCertified'] ?? null,
                            'hasChatFilter' => $user['user_hasChatFilter'] ?? null,
                            'arcane' => $user['user_arcane'] ?? null,
                            'arcaneIgnore' => $user['user_ignore'] ?? null
                        );

                        if ($user['user_game'] == 'League of Legends') {
                            $lolUser = $this->leagueOfLegends->getLeageUserByUserId($user['user_id']);

                            if ($lolUser)
                            {
                                $lolUserData = array(
                                    'lolId' => $lolUser['lol_id'],
                                    'main1' => $lolUser['lol_main1'],
                                    'main2' => $lolUser['lol_main2'],
                                    'main3' => $lolUser['lol_main3'],
                                    'rank' => $lolUser['lol_rank'],
                                    'role' => $lolUser['lol_role'],
                                    'server' => $lolUser['lol_server'],
                                    'account' => $lolUser['lol_account'],
                                    'sUsername' => $lolUser['lol_sUsername'],
                                    'sLevel' => $lolUser['lol_sLevel'],
                                    'sRank' => $lolUser['lol_sRank'],
                                    'sProfileIcon' => $lolUser['lol_sProfileIcon'],
                                    'skipSelectionLol' => $lolUser['lol_noChamp']
                                );

                                $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

                                if ($lfUser)
                                {
                                    $lookingforUserData = array(
                                        'lfId' => $lfUser['lf_id'],
                                        'lfGender' => $lfUser['lf_gender'],
                                        'lfKingOfGamer' => $lfUser['lf_kindofgamer'],
                                        'lfGame' => $lfUser['lf_game'],
                                        'main1Lf' => $lfUser['lf_lolmain1'],
                                        'main2Lf' => $lfUser['lf_lolmain2'],
                                        'main3Lf' => $lfUser['lf_lolmain3'],
                                        'rankLf' => $lfUser['lf_lolrank'],
                                        'roleLf' => $lfUser['lf_lolrole'],
                                        'skipSelectionLf' => $lfUser['lf_lolNoChamp'],
                                        'filteredServerLf' => $lfUser['lf_filteredServer']
                                    );

                                    $response = array(
                                        'message' => $this->_('messages.success'),
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => true,
                                        'lookingForUserExists' => true,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'leagueUser' => $lolUserData,
                                        'lookingForUser' => $lookingforUserData
                                    );     
                                    $step = 'ConnectLeague';
                                    $this->handleMobileFlowSuccess('Account connected', $response);
                                }
                                else 
                                {
                                    $response = array(
                                        'message' => $this->_('messages.success'),
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => true,
                                        'lookingForUserExists' => false,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'leagueUser' => $lolUserData
                                    );
                                    $step = 'lookingForAccount';
                                    $this->handleMobileFlowSuccess('Create your Looking for account.', $response);
                                }
                            }
                            else 
                            {
                                $response = array(
                                    'message' => $this->_('messages.success'),
                                    'newUser' => false,
                                    'googleUser' => $googleUserData,
                                    'user' => $userData,
                                    'userExists' => true,
                                    'leagueUserExists' => false
                                );
                                $step = 'LeagueAccount';
                                $this->handleMobileFlowSuccess('Create your League account.', $response);
                            }
                        }
                        else 
                        {
                            $valorantUser = $this->valorant->getValorantUserByUserId($user['user_id']);

                            if ($valorantUser)
                            {

                                $valorantUserData = array(
                                    'valorantId' => $valorantUser['valorant_id'],
                                    'main1' => $valorantUser['valorant_main1'],
                                    'main2' => $valorantUser['valorant_main2'],
                                    'main3' => $valorantUser['valorant_main3'],
                                    'rank' => $valorantUser['valorant_rank'],
                                    'role' => $valorantUser['valorant_role'],
                                    'server' => $valorantUser['valorant_server'],
                                    'skipSelectionVal' => $valorantUser['valorant_noChamp']
                                );

                                $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
                                if ($lfUser)
                                {
                                    $lookingforUserData = array(
                                        'lfId' => $lfUser['lf_id'],
                                        'lfGender' => $lfUser['lf_gender'],
                                        'lfKingOfGamer' => $lfUser['lf_kindofgamer'],
                                        'lfGame' => $lfUser['lf_game'],
                                        'valmain1Lf' => $lfUser['lf_valmain1'],
                                        'valmain2Lf' => $lfUser['lf_valmain2'],
                                        'valmain3Lf' => $lfUser['lf_valmain3'],
                                        'valrankLf' => $lfUser['lf_valrank'],
                                        'valroleLf' => $lfUser['lf_valrole'],
                                        'skipSelectionLf' => $lfUser['lf_valNoChamp'],
                                        'filteredServerLf' => $lfUser['lf_filteredServer']
                                    );

                                    $response = array(
                                        'message' => $this->_('messages.success'),
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => false,
                                        'lookingForUserExists' => true,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'valorantUser' => $valorantUserData,
                                        'lookingForUser' => $lookingforUserData,
                                        'valorantUserExists' => true
                                    );  

                                    $step = 'ConnectValorant';
                                    $this->handleMobileFlowSuccess('Account connected', $response);
                                }
                                else 
                                {
                                    $response = array(
                                        'message' => $this->_('messages.success'),
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => false,
                                        'lookingForUserExists' => false,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'valorantUser' => $valorantUserData,
                                        'valorantUserExists' => true
                                    );
                                    $step = 'lookingForAccount';
                                    $this->handleMobileFlowSuccess('Create your Looking for account.', $response);
                                }

                            }
                            else 
                            {
                                $response = array(
                                    'message' => $this->_('messages.success'),
                                    'newUser' => false,
                                    'googleUser' => $googleUserData,
                                    'user' => $userData,
                                    'userExists' => true,
                                    'leagueUserExists' => false,
                                    'valorantUserExists' => false
                                );

                                $step = 'valorantAccount';
                                $this->handleMobileFlowSuccess('Create your Valorant account.', $response);
                            }

                        }

                    }
                    else 
                    {
                        $response = array(
                            'message' => $this->_('messages.success'),
                            'newUser' => false,
                            'googleUser' => $googleUserData,
                            'userExists' => false
                        );
                        $step = 'basicInfo';
                        $this->handleMobileFlowSuccess('Create your account.', $response);
                    }
                }
                else 
                {
                    $response = array(
                            'message' => $this->_('messages.success'),
                            'newUser' => false,
                            'googleUser' => $googleUserData,
                            'userExists' => false
                    );
                    $step = 'basicInfo';
                    $this->handleMobileFlowSuccess('Create your account.', $response);
                }

            }
            else
            {
                $this->handleMobileFlowFailure('This League of Legends account is already used on URSG.');
            }

        }
        else 
        {
            $fakeEmail = "riot_{$puuid}@fake.riot";
            // Create a new account
            $RSO = 1;
            $fullName = $userData['gameName'];
            $firstName = $userData['gameName'];
            $googleFamilyName = $userData['gameName'];
            $createGoogleUserRiot = $this->googleUser->createGoogleUser($puuid, $fullName, $firstName, $googleFamilyName,  $RSO, $fakeEmail);

            if ($createGoogleUserRiot)
            {

                // MASTER TOKEN SYSTEM
                $token = bin2hex(random_bytes(32));
                $createToken = $this->googleUser->storeMasterToken($createGoogleUserRiot, $token);

                setcookie("auth_token", $token, [
                    'expires' => time() + 60 * 60 * 24 * 60,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict',
                ]);

                $googleData = array(
                    'googleId' => $puuid,
                    'fullName' => $fullName,
                    'firstName' => $firstName,
                    'lastName' => $googleFamilyName,
                    'email' => $fakeEmail,
                    'googleUserId' => $createGoogleUser,
                    'token' => $token
                );

                $response = array(
                    'message' => $this->_('messages.success'),
                    'newUser' => true,
                    'googleUser' => $googleData,
                );


                $step = 'basicInfo';
                $this->handleMobileFlowSuccess('Create your account.', $step, $puuid, $token, $createGoogleUserRiot, $response);
            }

        }
    }


    public function handleMobileFlowFailure($error)
    {
        unset($_SESSION['phoneData']);
        unset($_SESSION['riotConnectMobile']);

        $response = array(
            'status' => 'failure',
            'message' => $error
        );
        $responseJson = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        error_log(print_r('Error ' . $error, true));
        $redirectUrl = "intent://riotCallback?response=" . rawurlencode($responseJson) . "#Intent;scheme=com.dosylia.URSG;package=com.dosylia.URSG;end;";
        error_log(print_r('Redirecting to ' . $redirectUrl, true));
        $this->outputMobileFlowHtml($redirectUrl, false);
    }

    public function handleMobileFlowSuccess($message, $response)
    {
        unset($_SESSION['phoneData']);
        unset($_SESSION['riotConnectMobile']);

        $responseJson = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $redirectUrl = "intent://riotCallback?response=" . rawurlencode($responseJson) . "#Intent;scheme=com.dosylia.URSG;package=com.dosylia.URSG;end;";
        $this->outputMobileFlowHtml($redirectUrl, true);
    }

    private function outputMobileFlowHtml($redirectUrl, $success = true)
    {
        $title = $success ? 'Authentication Successful' : 'Authentication Failed';
        $message = $success ? 'Redirecting you back to the URSG app...' : 'There was a problem. Redirecting you back to the URSG app...';
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Return to URSG App</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <script>
                function openApp() {
                    window.location.href = "' . $redirectUrl . '";
                    setTimeout(function() {
                        if (!document.webkitHidden && !document.hidden) {
                            document.getElementById("fallbackButton").style.display = "block";
                            document.getElementById("appStoreButton").style.display = "block";
                        }
                    }, 1000);
                }
                window.onload = function() { openApp(); };
            </script>
        </head>
        <body style="font-family: Arial, sans-serif; text-align: center; padding: 40px;">
            <h2>' . $title . '</h2>
            <p>' . $message . '</p>
            <div id="fallbackButton" style="display: none;">
                <p>If you werent redirected automatically, click below:</p>
                <a href="' . htmlspecialchars($redirectUrl) . '" style="padding: 15px 30px; background: #e74057; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; display: inline-block;">
                    Open URSG App
                </a>
            </div>
            <div id="appStoreButton" style="display: none; margin-top: 20px;">
                <p>Dont have the app?</p>
                <a href="https://play.google.com/store/apps/details?id=com.dosylia.URSG" style="padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px;">
                    Get on Google Play
                </a>
                <a href="https://apps.apple.com/app/" style="padding: 10px 20px; background: #007AFF; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Get on App Store
                </a>
            </div>
        </body>
        </html>';
        return;
    }
}