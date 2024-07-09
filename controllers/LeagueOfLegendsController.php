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
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectLeague()){
            // Code block 2: User is connected via Google, Website but not connected to LoL LATER ADD VALORANT CHECK
            $user = $this-> user -> getUserById($_SESSION['userId']);
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
            // Code block 4: Redirect to index.php if none of the above conditions are met
            header("Location: index.php");
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

            
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Master", "Grand Master", "Challenger"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill"];
            $lol_servers = ["Europe West", "North America", "Europe Nordic" => "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia",  "Turkey", "Japan", "Korea"];


            $template = "views/swiping/update_league";
            $page_title = "URSG - Profile";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: index.php");
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
            header("Location: index.php");
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

            require 'keys.php';

            $summoner = getSummonerByNameAndTag($username, $tagLine, $apiKey);

            if ($summoner)
            {
                $summonerId = $summoner['puuid'];
                $summoner_name = $summoner['gameName'];
                $verificationCode = bin2hex(random_bytes(5));

                            // Save to the session
                            $_SESSION['verification_code'] = $verificationCode;
                            $_SESSION['summoner_id'] = $summonerId;
                            $_SESSION['tag_line'] = $tagLine;
                            $_SESSION['summoner_name'] = $summoner_name;

                            $insertLeagueData = $this->leagueOfLegends->addLoLAccount($this->getLolServer(), $this->getLolAccount(), $verificationCode, $summonerId, $this->getUserId());

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

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'X-Riot-Token: ' . $apiKey,
        // ]);

        // $response = curl_exec($ch);
        // curl_close($ch);

        // $data = json_decode($response, true);
        // return $data
    }

    public function verifyLeagueAccount()
    {
        
        if (isset($_POST['param']))
        {
            $data = json_decode($_POST['param']);
            $userId = $this->validateInput($_SESSION['userId']);
            $this->setUserId($userId);
            $verificationCode = $_SESSION['verification_code'];
            $summonerId = $_SESSION['summoner_id'];
            $tagLine = $_SESSION['tag_line'];
            
            require 'keys.php';
            
            $summonerProfile = $this->getSummonerProfile($summonerId, $tagLine, $apiKey);

            
            if ($summonerProfile && strpos($summonerProfile['profileIconId'], $verificationCode) !== false) {

                $summonerRankedStats = $this->getSummonerRankedStats($summonerProfile['id'], $tagLine, $apiKey);
                // Verification success
                $rankedStats = isset($summonerRankedStats[0]) ? $summonerRankedStats[0] : null;
                $rankAndTier = $rankedStats ? $rankedStats['tier'] . ' ' . $rankedStats['rank'] : null;
    
                // Save updated summoner data to the database
                $this->leagueOfLegends->updateSummonerData(
                    $summonerProfile['name'], 
                    $summonerProfile['summonerLevel'], 
                    $summonerProfile['profileIconId'], 
                    $rankAndTier,
                    $this->getUserId()
                );
    
                echo json_encode(['status' => 'success', 'message' => 'Account verified successfully!']);
            } else {
                // Verification failed
                echo json_encode(['status' => 'failure', 'message' => 'Verification failed. Please check your profile icon and try again.']);
            }
        } 
        else 
        {
            echo json_encode(['status' => 'failure', 'message' => 'Invalid request method']);
        }
    }

    public function getSummonerProfile($summonerId, $tagLine ,$apiKey) {
        $url = "https://". strtolower($tagLine) .".api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{$summonerId}?api_key={$apiKey}";
        return json_decode(file_get_contents($url), true);
    }
    
    public function getSummonerRankedStats($summonerId, $tagLine, $apiKey) {
        $url = "https://". strtolower($tagLine) .".api.riotgames.com/lol/league/v4/entries/by-summoner/{$summonerId}?api_key={$apiKey}";
        return json_decode(file_get_contents($url), true);
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
            $loLAccount = $this->validateInput($_POST["account_lol"]);
            $this->setLoLAccount($loLAccount);

            if ($this->emptyInputSignup($this->getLoLAccount()) !== false) {
                header("location:index.php?action=leagueuser&message=Account input cannot be empty");
                exit();
            }

            $createLoLUser = $this->leagueOfLegends->createLoLUser(
                $this->getUserId(), 
                $this->getLoLMain1(), 
                $this->getLoLMain2(), 
                $this->getLoLMain3(), 
                $this->getLoLRank(), 
                $this->getLoLRole(), 
                $this->getLoLServer(), 
                $this->getLoLAccount());

            if ($createLoLUser)
            {

                $lolUser = $this->leagueOfLegends->getLeageUserByUsername($this->getLoLAccount());

                if (session_status() == PHP_SESSION_NONE) 
                {
                    $lifetime = 7 * 24 * 60 * 60;
                    session_set_cookie_params($lifetime);
                    session_start();
                }
                
                    $_SESSION['lol_id'] = $lolUser['lol_id'];
                    $_SESSION['lol_account'] = $lolUser['lol_account'];

                header("location:index.php?action=lookingforuserlol");
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
            $loLAccount = $this->validateInput($_POST["account_lol"]);
            $this->setLoLAccount($loLAccount);

            if ($this->emptyInputSignup($this->getLoLAccount()) !== false) {
                header("location:index.php?action=updateLeague&message=No data sent");
                exit();
            }

            $updateLeague = $this->leagueOfLegends->updateLeagueData(
                $this->getUserId(), 
                $this->getLoLMain1(), 
                $this->getLoLMain2(), 
                $this->getLoLMain3(), 
                $this->getLoLRank(), 
                $this->getLoLRole(), 
                $this->getLoLServer(), 
                $this->getLoLAccount());

            if ($updateLeague)
            {
                header("location:index.php?action=userProfile&message=Udpated successfully");
                exit();  
            }
            else
            {
                header("location:index.php?action=userProfile&message=Could not update");
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
