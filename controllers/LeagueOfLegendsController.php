<?php

namespace controllers;

use models\LeagueOfLegends;
use models\User;
use models\FriendRequest;
use traits\SecurityController;

class LeagueOfLegendsController
{
    use SecurityController;

    private LeagueOfLegends $leagueOfLegends;
    private FriendRequest $friendrequest;
    private User $user;
    private $userId;
    private $loLMain1;
    private $loLMain2;
    private $loLMain3;
    private $loLRank;
    private $loLRole;
    private $loLServer;
    private $loLAccount;

    
    public function __construct()
    {
        $this -> leagueOfLegends = new LeagueOfLegends();
        $this -> user = new User();
        $this -> friendrequest = new FriendRequest();
    }

    public function pageLeagueUser()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague()) {
            // Code block 1: User is connected via Google, Website and has League data, need looking for
            $lolUser = $this->leagueOfLegends->getLeageUserByUsername($_SESSION['lol_account']);
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectLeague()){
            // Code block 2: User is connected via Google, Website but not connected to LoL LATER ADD VALORANT CHECK
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $template = "views/signup/leagueoflegendsuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite()) {
            // Code block 3: User is connected via Google but doesn't have a username
            $template = "views/signup/basicinfo";
            $title = "Sign up";
            $page_title = "URSG - Sign";
            require "views/layoutSignup.phtml";
        } else {
            // Code block 4: Redirect to / if none of the above conditions are met
            header("Location: /");
            exit();
        }
    }

    public function pageUpdateLeague()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

          // Get important datas
          $user = $this-> user -> getUserByUsername($_SESSION['username']);
          $allUsers = $this-> user -> getAllUsers();
          $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
          $lolUser = $this->leagueOfLegends->getLeageUserByLolId($_SESSION['lol_id']);

            
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill"];
            $lol_servers = ["Europe West", "North America", "Europe Nordic" => "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia",  "Turkey", "Japan", "Korea"];


            $template = "views/swiping/update_league";
            $page_title = "URSG - Profile";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function pageUpdateLeagueAccount()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $allUsers = $this-> user -> getAllUsers();
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lolUser = $this->leagueOfLegends->getLeageUserByLolId($_SESSION['lol_id']);
            $lol_servers = ["Europe West", "North America", "Europe Nordic" => "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia",  "Turkey", "Japan", "Korea"];

            $template = "views/swiping/update_leagueAccount";
            $page_title = "URSG - Bind league account";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function sendAccountToPhp()
    {
        if (isset($_POST['param']))
        {
            $data = json_decode($_POST['param']);
            
            $userId = $this->validateInput($_SESSION['userId']);
            $this->setUserId($userId);
            $loLAccount = $this->validateInput($data->lolAccount);
            $this->setLolAccount(str_replace(' ', '', $loLAccount));
            $parts = explode('#', $this->getLolAccount());
            $username = $parts[0];
            $tagLine = $parts[1];
            $loLServer = $data->lolServer;
            $this->setLolServer($loLServer);

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


            $selectedRegionValue = $regionMap[$this->getLolServer()] ?? null;

            require_once 'keys.php';

            $summoner = $this->getSummonerByNameAndTag($username, $tagLine, $apiKey);


            if ($summoner)
            {
                $puudId = $summoner['puuid'];
                $summoner_name = $summoner['gameName'];
                $verificationCode = bin2hex(random_bytes(5));

                            // Save to the session
                            $_SESSION['verification_code'] = $verificationCode;
                            $_SESSION['$puudId'] = $puudId;
                            $_SESSION['tag_line'] = $tagLine;
                            $_SESSION['summoner_name'] = $summoner_name;
                            $_SESSION['server'] = $selectedRegionValue;

                            $insertLeagueData = $this->leagueOfLegends->addLoLAccount($this->getLolServer(), $this->getLolAccount(), $verificationCode, $this->getUserId());

                            if ($insertLeagueData)
                            {
                                echo json_encode(['status' => 'success', 'message' => 'Verification code generated', 'verification_code' => $verificationCode]);
                            }
            } 
            else
            {
                echo json_encode(['success' => false, 'message' => "Couldn't find a LoL account"]);
            }
    
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function getSummonerByNameAndTag($summonerName, $tagLine, $apiKey) {
        $region = "americas";
        $url = "https://{$region}.api.riotgames.com/riot/account/v1/accounts/by-riot-id/" . urlencode($summonerName) . "/{$tagLine}?api_key={$apiKey}";
        return json_decode(file_get_contents($url), true);
    }

    public function verifyLeagueAccount()
    {
        
        if (isset($_POST['param']))
        {

            $userId = $this->validateInput($_SESSION['userId']);
            $this->setUserId($userId);
            $puudId = $_SESSION['$puudId'];
            $server = $_SESSION['server'];
            $tagLine = $_SESSION['tag_line'];
            $username = $_SESSION['summoner_name'];

            require_once 'keys.php';
            
            $summonerProfile = $this->getSummonerProfile($puudId, $server, $apiKey);

            if($summonerProfile && $summonerProfile['profileIconId'] === 7) 
            {


                $summonerRankedStats = $this->getSummonerRankedStats($summonerProfile['id'], $server, $apiKey);

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
                        $username, 
                        $summonerProfile['id'],
                        $puudId,
                        $summonerProfile['summonerLevel'], 
                        $rankAndTier,
                        $summonerProfile['profileIconId'], 
                        $this->getUserId()
                    );
        
                    echo json_encode(['status' => 'success', 'message' => 'Account verified successfully!']);
            }
            else
            {
                echo json_encode(['status' => 'failure', 'message' => 'Verification failed. Picture does not match.']);
            }
            
        } 
        else 
        {
            echo json_encode(['status' => 'failure', 'message' => 'Invalid request method']);
        }
    }

    public function getSummonerProfile($puudId, $server ,$apiKey) {
        $url = "https://". strtolower($server) .".api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{$puudId}?api_key={$apiKey}";
        return json_decode(file_get_contents($url), true);
    }
    
    public function getSummonerRankedStats($summonerId, $server, $apiKey) {
        $url = "https://". strtolower($server) .".api.riotgames.com/lol/league/v4/entries/by-summoner/{$summonerId}?api_key={$apiKey}";
        return json_decode(file_get_contents($url), true);
    }

    public function refreshRiotData()
    {

        $allUsers = $this->user->getAllUsers();
    
        foreach ($allUsers as $user)
        {
            if ($user['lol_sPuuid'] && $user['lol_verified'] === 1)
            {
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
    
                require_once 'keys.php';
    
                $summonerProfile = $this->getSummonerProfile($user['lol_sPuuid'], $selectedRegionValue, $apiKey);
    
                if ($summonerProfile)
                {
                    $summonerRankedStats = $this->getSummonerRankedStats($summonerProfile['id'], $selectedRegionValue, $apiKey);

                    $rankAndTier = $this->determineRankAndTier($summonerRankedStats);
    
                    $username = $user['lol_sUsername'];
                    $userId = $user['user_id'];
                    $puudId = $user['lol_sPuuid'];
    
                    $this->leagueOfLegends->updateSummonerData(
                        $username, 
                        $summonerProfile['id'],
                        $puudId,
                        $summonerProfile['summonerLevel'], 
                        $rankAndTier,
                        $summonerProfile['profileIconId'], 
                        $userId
                    );
                }
            }
        }
    }

    public function determineRankAndTier($summonerRankedStats)
{
    // Default to 'Unranked'
    $rankAndTier = 'Unranked';
    $soloQueueRank = null;
    $flexQueueRank = null;

    // Define tier and division order for comparison
    $tiers = [
        'IRON' => 1,
        'BRONZE' => 2,
        'SILVER' => 3,
        'GOLD' => 4,
        'PLATINUM' => 5,
        'DIAMOND' => 6,
        'MASTER' => 7,
        'GRANDMASTER' => 8,
        'CHALLENGER' => 9
    ];

    $divisions = [
        'IV' => 1,
        'III' => 2,
        'II' => 3,
        'I' => 4
    ];

    // Loop through the ranked stats array to find the desired queue types
    foreach ($summonerRankedStats as $rankedStats) {
        if ($rankedStats['queueType'] === 'RANKED_SOLO_5x5') {
            $soloQueueRank = [
                'tier' => $rankedStats['tier'],
                'rank' => $rankedStats['rank']
            ];
        } elseif ($rankedStats['queueType'] === 'RANKED_FLEX_SR') {
            $flexQueueRank = [
                'tier' => $rankedStats['tier'],
                'rank' => $rankedStats['rank']
            ];
        }
    }

    // Compare solo queue and flex queue ranks to determine the higher one
    if ($soloQueueRank && $flexQueueRank) {
        if ($tiers[$soloQueueRank['tier']] > $tiers[$flexQueueRank['tier']]) {
            $rankAndTier = $soloQueueRank['tier'] . ' ' . $soloQueueRank['rank'];
        } elseif ($tiers[$soloQueueRank['tier']] < $tiers[$flexQueueRank['tier']]) {
            $rankAndTier = $flexQueueRank['tier'] . ' ' . $flexQueueRank['rank'];
        } else {
            // If tiers are the same, compare divisions
            if ($divisions[$soloQueueRank['rank']] > $divisions[$flexQueueRank['rank']]) {
                $rankAndTier = $soloQueueRank['tier'] . ' ' . $soloQueueRank['rank'];
            } else {
                $rankAndTier = $flexQueueRank['tier'] . ' ' . $flexQueueRank['rank'];
            }
        }
    } elseif ($soloQueueRank) {
        $rankAndTier = $soloQueueRank['tier'] . ' ' . $soloQueueRank['rank'];
    } elseif ($flexQueueRank) {
        $rankAndTier = $flexQueueRank['tier'] . ' ' . $flexQueueRank['rank'];
    }

    return $rankAndTier;
}

    public function createLeagueUser()
    {
        if (isset($_POST['submit'])) 
        {

            $userId = $this->validateInput($_POST["userId"]);
            $this->setUserId($userId);
            $loLMain1 = $this->validateInput($_POST["main1"]);
            $this->setLoLMain1($loLMain1);
            $loLMain2 = $this->validateInput($_POST["main2"]);
            $this->setLoLMain2($loLMain2);
            $loLMain3 = $this->validateInput($_POST["main3"]);
            $this->setLoLMain3($loLMain3);
            $loLRank = $this->validateInput($_POST["rank_lol"]);
            $this->setLoLRank($loLRank);
            $loLRole = $this->validateInput($_POST["role_lol"]);
            $this->setLoLRole($loLRole);
            $loLServer = $this->validateInput($_POST["server"]);
            $this->setLoLServer($loLServer);

            $createLoLUser = $this->leagueOfLegends->createLoLUser(
                $this->getUserId(), 
                $this->getLoLMain1(), 
                $this->getLoLMain2(), 
                $this->getLoLMain3(), 
                $this->getLoLRank(), 
                $this->getLoLRole(), 
                $this->getLoLServer()); // This also get the ID 

            if ($createLoLUser)
            {

                $lolUser = $this->leagueOfLegends->getLeageAccountByLeagueId($createLoLUser);

                if (session_status() == PHP_SESSION_NONE) 
                {
                    $lifetime = 7 * 24 * 60 * 60;
                    session_set_cookie_params($lifetime);
                    session_start();
                }
                
                    $_SESSION['lol_id'] = $lolUser['lol_id'];

                header("location:/lookingforuserlol");
                exit();
            }

        }

    }


    public function UpdateLeague()
    {
        if (isset($_POST['submit'])) 
        {

            $userId = $this->validateInput($_POST["userId"]);
            $this->setUserId($userId);
            $loLMain1 = $this->validateInput($_POST["main1"]);
            $this->setLoLMain1($loLMain1);
            $loLMain2 = $this->validateInput($_POST["main2"]);
            $this->setLoLMain2($loLMain2);
            $loLMain3 = $this->validateInput($_POST["main3"]);
            $this->setLoLMain3($loLMain3);
            $loLRank = $this->validateInput($_POST["rank_lol"]);
            $this->setLoLRank($loLRank);
            $loLRole = $this->validateInput($_POST["role_lol"]);
            $this->setLoLRole($loLRole);
            $loLServer = $this->validateInput($_POST["server"]);
            $this->setLoLServer($loLServer);

            $updateLeague = $this->leagueOfLegends->updateLeagueData(
                $this->getUserId(), 
                $this->getLoLMain1(), 
                $this->getLoLMain2(), 
                $this->getLoLMain3(), 
                $this->getLoLRank(), 
                $this->getLoLRole(), 
                $this->getLoLServer());

            if ($updateLeague)
            {
                header("location:/userProfile&message=Udpated successfully");
                exit();  
            }
            else
            {
                header("location:/userProfile&message=Could not update");
                exit();
            }

        }

    }

    public function emptyInputSignup($account) 
    {
        $result;
        if (empty($account))
        {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    public function validateInput($input) 
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getLoLMain1()
    {
        return $this->loLMain1;
    }

    public function setLoLMain1($loLMain1)
    {
        $this->loLMain1 = $loLMain1;
    }

    public function getLoLMain2()
    {
        return $this->loLMain2;
    }

    public function setLoLMain2($loLMain2)
    {
        $this->loLMain2 = $loLMain2;
    }

    public function getLoLMain3()
    {
        return $this->loLMain3;
    }

    public function setLoLMain3($loLMain3)
    {
        $this->loLMain3 = $loLMain3;
    }

    public function getLoLRank()
    {
        return $this->loLRank;
    }

    public function setLoLRank($loLRank)
    {
        $this->loLRank = $loLRank;
    }


    public function getLoLRole()
    {
        return $this->loLRole;
    }

    public function setLoLRole($loLRole)
    {
        $this->loLRole = $loLRole;
    }

    public function getLoLServer()
    {
        return $this->loLServer;
    }

    public function setLoLServer($loLServer)
    {
        $this->loLServer = $loLServer;
    }

    public function getLoLAccount()
    {
        return $this->loLAccount;
    }

    public function setLoLAccount($loLAccount)
    {
        $this->loLAccount = $loLAccount;
    }

}
