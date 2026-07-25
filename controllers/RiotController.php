<?php

namespace controllers;

use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use models\GoogleUser;
use models\UserLookingFor;
use models\Items;
use models\RatingGames;
use traits\SecurityController;
use traits\Translatable;
use traits\MobileDeepLinkResponder;
use services\LogInService;
use services\SignUpService;
use services\MasterTokenService;
use services\LoginDestination;
use services\RiotOAuthClientInterface;
use services\RiotOAuthClient;
use services\FakeRiotOAuthClient;

require 'vendor/autoload.php';

class RiotController
{
    use SecurityController;
    use Translatable;
    use MobileDeepLinkResponder;
    private LeagueOfLegends $leagueOfLegends;
    private User $user;
    private Valorant $valorant;
    private GoogleUser $googleUser;
    private UserLookingFor $userlookingfor;
    private Items $items;
    private RatingGames $rating;
    private LogInService $logInService;
    private SignUpService $signUpService;
    private RiotOAuthClientInterface $riotOAuthClient;
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
        $masterTokenService = new MasterTokenService($this->googleUser);
        $this->logInService = new LogInService(
            $masterTokenService,
            $this->user,
            $this->leagueOfLegends,
            $this->valorant,
            $this->userlookingfor
        );
        $this->signUpService = new SignUpService($this->googleUser, $masterTokenService);
        $this->riotOAuthClient = (($_ENV['environment'] ?? null) === 'local')
            ? new FakeRiotOAuthClient()
            : new RiotOAuthClient();
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
            $accessToken = $this->riotOAuthClient->getAccessToken($authCode, $riotClientId, $riotClientSecret);

            // Step 4: Fetch user data using the access token
            if ($accessToken) {
                $userData = $this->riotOAuthClient->getUserData($accessToken);
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
            $accessToken = $this->riotOAuthClient->getAccessToken($authCode, $riotClientId, $riotClientSecret);

            // Step 4: Fetch user data using the access token
            if ($accessToken) {
                $userData = $this->riotOAuthClient->getUserData($accessToken);
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
                            $outcome = $this->logInService->resumeWebSession($existingUser);
                            $_SESSION['tagLine'] = $userData['tagLine'];
                            $_SESSION['full_name'] = $userData['gameName'];

                            $user = $outcome->userRow;

                            if (!$user) {
                                header('Location: /signup?message=Create your account.');
                                return;
                            }

                            if ($outcome->destination === LoginDestination::ONBOARDED) {
                                header('Location: /swiping?message=Connected successfully.');
                                return;
                            }

                            if ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                                header('Location: /signup?message=Create your Looking for account.');
                                return;
                            }

                            if ($outcome->game === 'League of Legends') {
                                header('Location: /signup?message=Create your LoL account.');
                            } else {
                                header('Location: /signup?message=Create your Valorant account.');
                            }
                            return;
                        }
                        else
                        {
                            header('Location: /?message=This League of Legends account is already used on URSG.');
                            return;
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

                        $outcome = $this->signUpService->createWebIdentity($puuid, $fullName, $firstName, $googleFamilyName, $fakeEmail, $RSO);

                        if ($outcome) {
                            $_SESSION['tagLine'] = $userData['tagLine'];
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

            $accessToken = $this->riotOAuthClient->getAccessToken($authCode, $riotClientId, $riotClientSecret);

            if ($accessToken) {
                $userData = $this->riotOAuthClient->getUserData($accessToken);
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
        $accessToken = $this->riotOAuthClient->getAccessToken($authCode, $riotClientId, $riotClientSecret);

        if (!$accessToken) {
            $this->handleMobileFlowFailure('Failed to obtain access token');
            return;
        }

        $userData = $this->riotOAuthClient->getUserData($accessToken);
        $puuid = $userData['puuid'];

        $existingUser = $this->googleUser->getUserByPuuid($puuid);

        $cookieOptions = [
            'expires' => time() + 60 * 60 * 24 * 60,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ];

        // If user exists, allow connection on mobile, otherwise create account
        if ($existingUser)
        {
            if ($existingUser['google_createdWithRSO'] !== 1)
            {
                $this->handleMobileFlowFailure('This League of Legends account is already used on URSG.');
                return;
            }

            $outcome = $this->logInService->resumeMobileProfile($existingUser);
            setcookie("auth_token", $outcome->masterToken, $cookieOptions);

            if (!$outcome->userExists) {
                $response = array(
                    'message' => $this->_('messages.success'),
                    'newUser' => false,
                    'googleUser' => $outcome->identityRow,
                    'userExists' => false
                );
                $this->handleMobileFlowSuccess('Create your account.', $response);
                return;
            }

            if ($outcome->game === 'League of Legends') {
                if ($outcome->destination === LoginDestination::NEEDS_GAME_ACCOUNT) {
                    $response = array(
                        'message' => $this->_('messages.success'),
                        'newUser' => false,
                        'googleUser' => $outcome->identityRow,
                        'user' => $outcome->userRow,
                        'userExists' => true,
                        'leagueUserExists' => false
                    );
                    $this->handleMobileFlowSuccess('Create your League account.', $response);
                    return;
                }

                if ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                    $response = array(
                        'message' => $this->_('messages.success'),
                        'newUser' => false,
                        'userExists' => true,
                        'leagueUserExists' => true,
                        'lookingForUserExists' => false,
                        'googleUser' => $outcome->identityRow,
                        'user' => $outcome->userRow,
                        'leagueUser' => $outcome->gameProfile
                    );
                    $this->handleMobileFlowSuccess('Create your Looking for account.', $response);
                    return;
                }

                $response = array(
                    'message' => $this->_('messages.success'),
                    'newUser' => false,
                    'userExists' => true,
                    'leagueUserExists' => true,
                    'lookingForUserExists' => true,
                    'googleUser' => $outcome->identityRow,
                    'user' => $outcome->userRow,
                    'leagueUser' => $outcome->gameProfile,
                    'lookingForUser' => $outcome->lookingForRow
                );
                $this->handleMobileFlowSuccess('Account connected', $response);
                return;
            }

            if ($outcome->destination === LoginDestination::NEEDS_GAME_ACCOUNT) {
                $response = array(
                    'message' => $this->_('messages.success'),
                    'newUser' => false,
                    'googleUser' => $outcome->identityRow,
                    'user' => $outcome->userRow,
                    'userExists' => true,
                    'leagueUserExists' => false,
                    'valorantUserExists' => false
                );
                $this->handleMobileFlowSuccess('Create your Valorant account.', $response);
                return;
            }

            if ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                $response = array(
                    'message' => $this->_('messages.success'),
                    'newUser' => false,
                    'userExists' => true,
                    'leagueUserExists' => false,
                    'lookingForUserExists' => false,
                    'googleUser' => $outcome->identityRow,
                    'user' => $outcome->userRow,
                    'valorantUser' => $outcome->gameProfile,
                    'valorantUserExists' => true
                );
                $this->handleMobileFlowSuccess('Create your Looking for account.', $response);
                return;
            }

            $response = array(
                'message' => $this->_('messages.success'),
                'newUser' => false,
                'userExists' => true,
                'leagueUserExists' => false,
                'lookingForUserExists' => true,
                'googleUser' => $outcome->identityRow,
                'user' => $outcome->userRow,
                'valorantUser' => $outcome->gameProfile,
                'lookingForUser' => $outcome->lookingForRow,
                'valorantUserExists' => true
            );
            $this->handleMobileFlowSuccess('Account connected', $response);
            return;
        }
        else
        {
            $fakeEmail = "riot_{$puuid}@fake.riot";
            // Create a new account
            $RSO = 1;
            $fullName = $userData['gameName'];
            $firstName = $userData['gameName'];
            $googleFamilyName = $userData['gameName'];

            $outcome = $this->signUpService->createMobileIdentity($puuid, $fullName, $firstName, $googleFamilyName, $fakeEmail, $RSO);

            if ($outcome) {
                setcookie("auth_token", $outcome->masterToken, $cookieOptions);

                $response = array(
                    'message' => $this->_('messages.success'),
                    'newUser' => true,
                    'googleUser' => $outcome->identityRow,
                );

                $this->handleMobileFlowSuccess('Create your account.', $response);
            }

        }
    }


    protected function mobileFlowSessionKey(): string
    {
        return 'riotConnectMobile';
    }

    protected function mobileDeepLinkCallback(): string
    {
        return 'riotCallback';
    }
}