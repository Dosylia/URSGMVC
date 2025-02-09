<?php

namespace controllers;

use models\UserLookingFor;
use models\User;
use models\LeagueOfLegends;
use models\FriendRequest;
use models\GoogleUser;
use traits\SecurityController;

class UserLookingForController
{
    use SecurityController;

    private UserLookingFor $userlookingfor;
    private User $user; 
    private LeagueOfLegends $leagueoflegends;
    private FriendRequest $friendrequest;
    private GoogleUser $googleUser;
    private $userId;
    private $lfGender;
    private $lfKindOfGamer;
    private $lfGame;
    private $lfFilteredServer;
    private $loLMain1;
    private $loLMain2;
    private $loLMain3;
    private $loLRank;
    private $loLRole;
    private $valorantMain1;
    private $valorantMain2;
    private $valorantMain3;
    private $valorantRank;
    private $valorantRole;


    
    public function __construct()
    {
        $this -> userlookingfor = new userLookingFor();
        $this -> user = new User();
        $this -> leagueoflegends = new LeagueOfLegends();
        $this -> friendrequest = new FriendRequest();
        $this -> googleUser = new GoogleUser();
    }

    public function pageLookingFor()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && !$this->isConnectLf()) {
            // Code block 1: User is connected via Google, Website and has League data, need looking for
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $template = "views/signup/lookingforlol";
            $current_url = "https://ur-sg.com/lookingforuserlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectLeague()){
            // Code block 2: User is connected via Google, Website but not connected to LoL LATER ADD VALORANT CHECK
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
                $template = "views/signup/leagueoflegendsuser";
            $current_url = "https://ur-sg.com/leagueuser";
                $title = "More about you";
                $page_title = "URSG - Sign up";
                require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite()) {
            // Code block 3: User is connected via Google but doesn't have a username
            $current_url = "https://ur-sg.com/basicinfo";
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

    public function pageLookingForValorant()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectValorant() && !$this->isConnectLf()) {
            // Code block 1: User is connected via Google, Website and has League data, need looking for
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $template = "views/signup/lookingforvalorant";
            $current_url = "https://ur-sg.com/lookingforuservalorant";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectValorant()){
            // Code block 2: User is connected via Google, Website but not connected to LoL LATER ADD VALORANT CHECK
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
                $template = "views/signup/leagueoflegendsuser";
            $current_url = "https://ur-sg.com/leagueuser";
                $title = "More about you";
                $page_title = "URSG - Sign up";
                require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite()) {
            // Code block 3: User is connected via Google but doesn't have a username
            $current_url = "https://ur-sg.com/basicinfo";
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

    public function pageUpdateLookingFor()
    {    
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $allUsers = $this-> user -> getAllUsers();
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger", "Any"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill", "Any"];
            $valorant_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant"];
            $valorant_roles = ["Controller", "Duelist", "Initiator", "Sentinel", "Fill"];
            $genders = ["Male", "Female", "Non binary", "Male and Female", "All", "Trans"];
            $kindofgamers = ["Chill" => "Chill / Normal games", "Competition" => "Competition / Ranked", "Competition and Chill" => "Competition/Ranked and chill"];
            $filteredServers = [
                "Europe West", "North America", "Europe Nordic & East", "Brazil", 
                "Latin America North", "Latin America South", "Oceania", 
                "Russia", "Turkey", "Japan", "Korea"
            ];

            $current_url = "https://ur-sg.com/updateLookingForPage";
            $template = "views/swiping/update_lookingFor";
            $page_title = "URSG - Profile";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function pageUpdateLookingForGame()
    {    
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            !$this->isConnectLf()
        )
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $allUsers = $this-> user -> getAllUsers();
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
            $title = "What are you looking for?";
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger", "Any"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill", "Any"];
            $valorant_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant"];
            $valorant_roles = ["Controller", "Duelist", "Initiator", "Sentinel", "Fill"];
            $genders = ["Male", "Female", "Non binary", "Male and Female", "All", "Trans"];
            $kindofgamers = ["Chill" => "Chill / Normal games", "Competition" => "Competition / Ranked", "Competition and Chill" => "Competition/Ranked and chill"];

            $current_url = "https://ur-sg.com/updateLookingForGamePage";
            $template = "views/swiping/update_lookingForGame";
            $page_title = "URSG - Profile";
            require "views/layoutSignup.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function createLookingFor()
    {
        if (isset($_POST['submit'])) 
        {
            if (isset($_POST['game']) && $_POST['game'] == "League of Legends") {

                $userId = $this->validateInput($_POST["userId"]);
                $this->setUserId($userId);

                if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
                    header("location:/userProfile?message=Token not valid");
                    exit();
                }

                $lfGender = $this->validateInput($_POST["gender"]);
                $this->setLfGender($lfGender);
                $lfKindOfGamer = $this->validateInput($_POST["kindofgamer"]);
                $this->setLfKindOfGamer($lfKindOfGamer);
                $lfGame = $this->validateInput($_POST["game"]);
                $this->setLfGame($lfGame);
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
                $statusChampion = 0;

                $user = $this->user->getUserById($_SESSION['userId']);

                // if ($user['user_id'] != $this->getUserId())
                // {
                //     header("location:/userProfile?message=Could not update");
                //     exit();
                // }

                if (isset($_POST["skipSelection"])) {
                    $statusChampion = $this->validateInput($_POST["skipSelection"]);
                }

                if ($statusChampion == "1") {
                    if (empty($loLRank) || empty($loLRole))
                    {
                        header("location:/signup?message=Inputs cannot be empty");
                        exit();
                    }
                } else {
                    if (($loLMain1 === $loLMain2 || $loLMain1 === $loLMain3 || $loLMain2 === $loLMain3)) {
                        header("location:/signup?message=Each champion must be unique");
                        exit();
                    }
                    if (empty($loLMain1) || empty($loLMain2) || empty($loLMain3) || empty($loLRank) || empty($loLRole))
                    {
                        header("location:/signup?message=Inputs cannot be empty");
                        exit();
                    }
                }
                
                $testLeagueAccount = $this->user->getUserById($this->getUserId());
    
                if ($testLeagueAccount && $testLeagueAccount['lf_id'] !== null) {
                    $updateLookingFor = $this->userlookingfor->updateLookingForData(
                        $this->getLfGender(),
                        $this->getLfKindOfGamer(),     
                        $this->getLfGame(),        
                        $this->getLoLMain1(), 
                        $this->getLoLMain2(), 
                        $this->getLoLMain3(), 
                        $this->getLoLRank(), 
                        $this->getLoLRole(),
                        $statusChampion,
                        $this->getUserId());
    
    
                    if ($updateLookingFor)
                    {
                        if (!isset($_SESSION['lf_id'])) 
                        {
                                $leagueLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
                                $_SESSION['lf_id'] = $leagueLookingFor['lf_id'];
                        }
                        header("location:/userProfile?message=Updated successfully");
                        exit();  
                    }
                    else
                    {
                        header("location:/userProfile?message=Could not update");
                        exit();  
                    }
                }

    
                $createLookingFor = $this->userlookingfor->createLookingForUser(
                    $this->getUserId(), 
                    $this->getLfGender(),
                    $this->getLfKindOfGamer(),
                    $this->getLfGame(),               
                    $this->getLoLMain1(), 
                    $this->getLoLMain2(), 
                    $this->getLoLMain3(), 
                    $this->getLoLRank(), 
                    $this->getLoLRole(),
                    $statusChampion);

                    if ($createLookingFor)
                    {
        
                        $lolLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
        
                        if (session_status() == PHP_SESSION_NONE) 
                        {
                            $lifetime = 7 * 24 * 60 * 60;
                            session_set_cookie_params($lifetime);
                            session_start();
                        }
                        
                            $_SESSION['lf_id'] = $lolLookingFor['lf_id'];
        
                        header("location:/swiping");
                        exit();
                    }

            } else {

                $userId = $this->validateInput($_POST["userId"]);

                if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
                    header("location:/userProfile?message=Token not valid");
                    exit();
                }

                $this->setUserId($userId);
                $lfGender = $this->validateInput($_POST["gender"]);
                $this->setLfGender($lfGender);
                $lfKindOfGamer = $this->validateInput($_POST["kindofgamer"]);
                $this->setLfKindOfGamer($lfKindOfGamer);
                $lfGame = $this->validateInput($_POST["game"]);
                $this->setLfGame($lfGame);
                $valorantMain1 = $this->validateInput($_POST["main1"]);
                $this->setValorantMain1($valorantMain1);
                $valorantMain2 = $this->validateInput($_POST["main2"]);
                $this->setValorantMain2($valorantMain2);
                $valorantMain3 = $this->validateInput($_POST["main3"]);
                $this->setValorantMain3($valorantMain3);
                $valorantRank = $this->validateInput($_POST["rank_valorant"]);
                $this->setValorantRank($valorantRank);
                $valotantRole = $this->validateInput($_POST["role_valorant"]);
                $this->setValorantRole($valotantRole);

                $user = $this->user->getUserById($_SESSION['userId']);

                // if ($user['user_id'] != $this->getUserId())
                // {
                //     header("location:/userProfile?message=Could not update");
                //     exit();
                // }

                $statusChampion = 0;
                if (isset($_POST["skipSelection"])) {
                    $statusChampion = $this->validateInput($_POST["skipSelection"]);
                }

                if ($statusChampion == "1") {
                    if (empty($valorantRank) || empty($valotantRole))
                    {
                        header("location:/signup?message=Inputs cannot be empty");
                        exit();
                    }
                } else {

                    if ($valorantMain1 === $valorantMain2 || $valorantMain1 === $valorantMain2 || $valorantMain2 === $valorantMain3) {
                        header("location:/userProfile?message=Each agents must be unique");
                        exit();
                    }

                    if ((empty($valorantMain1) || empty($valorantMain2) || empty($valorantMain3) || empty($valorantRank) || empty($valotantRole)) || $statusChampion == "1")
                    {
                        header("location:/signup?message=Inputs cannot be empty");
                        exit();
                    }
                }


                
                $testValorantAccount = $this->user->getUserById($this->getUserId());
    
                if ($testValorantAccount && $testValorantAccount['lf_id'] !== null) {
                    $updateLookingFor = $this->userlookingfor->updateLookingForDataValorant(
                        $this->getLfGender(),
                        $this->getLfKindOfGamer(),
                        $this->getLfGame(),               
                        $this->getValorantMain1(), 
                        $this->getValorantMain2(), 
                        $this->getValorantMain3(), 
                        $this->getValorantRank(), 
                        $this->getValorantRole(),
                        $statusChampion,
                        $this->getUserId());


                    if ($updateLookingFor)
                    {
                        if (!isset($_SESSION['lf_id'])) 
                        {
                                $valorantLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
                                $_SESSION['lf_id'] = $valorantLookingFor['lf_id'];
                        }
                        header("location:/userProfile?message=Updated successfully");
                        exit();  
                    }
                    else
                    {
                        header("location:/userProfile?message=Could not update");
                        exit();  
                    }
                }
    
                $createLookingFor = $this->userlookingfor->createLookingForUserValorant(
                    $this->getUserId(), 
                    $this->getLfGender(),
                    $this->getLfKindOfGamer(),
                    $this->getLfGame(),               
                    $this->getValorantMain1(), 
                    $this->getValorantMain2(), 
                    $this->getValorantMain3(), 
                    $this->getValorantRank(), 
                    $this->getValorantRole(),
                    $statusChampion);

                    if ($createLookingFor)
                    {
        
                        $valorantLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
        
                        if (session_status() == PHP_SESSION_NONE) 
                        {
                            $lifetime = 7 * 24 * 60 * 60;
                            session_set_cookie_params($lifetime);
                            session_start();
                        }
                        
                            $_SESSION['lf_id'] = $valorantLookingFor['lf_id'];
        
                        header("location:/swiping");
                        exit();
                    } else {
                        header("location:/signup?message=Could not create looking for user");
                        exit();
                    }

            }
        }

    }

    public function createLookingForUserPhone()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['lookingforData'])) 
        {
            $data = json_decode($_POST['lookingforData']);

            if (isset($data->game) && $data->game == "League of Legends") {
                $userId = $this->validateInput($data->userId);
            $this->setUserId($userId);
            $lfGender = $this->validateInput($data->gender);
            $this->setLfGender($lfGender);
            $lfKindOfGamer = $this->validateInput($data->kindOfGamer);
            $this->setLfKindOfGamer($lfKindOfGamer);
            $lfGame = $this->validateInput($data->game);
            $this->setLfGame($lfGame);
            $loLMain1 = $this->validateInput($data->main1);
            $this->setLoLMain1($loLMain1);
            $loLMain2 = $this->validateInput($data->main2);
            $this->setLoLMain2($loLMain2);
            $loLMain3 = $this->validateInput($data->main3);
            $this->setLoLMain3($loLMain3);
            $loLRank = $this->validateInput($data->rank);
            $this->setLoLRank($loLRank);
            $loLRole = $this->validateInput($data->role);
            $this->setLoLRole($loLRole);
            $statusChampion = $this->validateInput($data->skipSelection);

            if ($statusChampion == 1) {
                if (empty($loLRank) || empty($loLRole))
                {
                    $response = array('message' => 'Fill all fields');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit();  
                }
            } else {
                if (empty($loLMain1) || empty($loLMain2) || empty($loLMain3) || empty($loLRank) || empty($loLRole))
                {
                    $response = array('message' => 'Fill all fields');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit();  
                }
            }
      
            $testLeagueAccount = $this->user->getUserById($this->getUserId());

            if ($testLeagueAccount && $testLeagueAccount['lf_id'] !== null) {
                $response = array('message' => 'User already exist');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();  
            }

            $createLookingFor = $this->userlookingfor->createLookingForUser(
                $this->getUserId(), 
                $this->getLfGender(),
                $this->getLfKindOfGamer(),
                $this->getLfGame(),               
                $this->getLoLMain1(), 
                $this->getLoLMain2(), 
                $this->getLoLMain3(), 
                $this->getLoLRank(), 
                $this->getLoLRole(),
                $statusChampion);

                if ($createLookingFor)
                {
    
                    $lolLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
    
                    $lookingforUserData = array(
                        'lfId' => $lolLookingFor['lf_id'],
                        'lfGender' => $lolLookingFor['lf_gender'],
                        'lfKingOfGamer' => $lolLookingFor['lf_kindofgamer'],
                        'lfGame' => $lolLookingFor['lf_game'],
                        'main1Lf' => $lolLookingFor['lf_lolmain1'],
                        'main2Lf' => $lolLookingFor['lf_lolmain2'],
                        'main3Lf' => $lolLookingFor['lf_lolmain3'],
                        'rankLf' => $lolLookingFor['lf_lolrank'],
                        'roleLf' => $lolLookingFor['lf_lolrole']
                    );
    
                    $response = array(
                        'sessionId' => session_id(),
                        'user' => $lookingforUserData,
                        'message' => 'Success'
                    );
    
                }

            } else {

            $userId = $this->validateInput($data->userId);
            $this->setUserId($userId);
            $lfGender = $this->validateInput($data->gender);
            $this->setLfGender($lfGender);
            $lfKindOfGamer = $this->validateInput($data->kindOfGamer);
            $this->setLfKindOfGamer($lfKindOfGamer);
            $lfGame = $this->validateInput($data->game);
            $this->setLfGame($lfGame);
            $valorantMain1 = $this->validateInput($data->main1);
            $this->setValorantMain1($valorantMain1);
            $valorantMain2 = $this->validateInput($data->main2);
            $this->setValorantMain2($valorantMain2);
            $valorantMain3 = $this->validateInput($data->main3);
            $this->setValorantMain3($valorantMain3);
            $valorantRank = $this->validateInput($data->rank);
            $this->setValorantRank($valorantRank);
            $valotantRole = $this->validateInput($data->role);
            $this->setValorantRole($valotantRole);
            $statusChampion = 0;
            if (isset($_POST["skipSelection"])) {
                $statusChampion = $this->validateInput($_POST["skipSelection"]);
            }

            if ($statusChampion == 1) {
                if (empty($valorantRank) || empty($valotantRole))
                {
                    $response = array('message' => 'Fill all fields');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit();  
                }
            } else {
                if (empty($valorantMain1) || empty($valorantMain2) || empty($valorantMain3) || empty($valorantRank) || empty($valotantRole))
                {
                    $response = array('message' => 'Fill all fields');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit();  
                }
            }

            
            $testValorantAccount = $this->user->getUserById($this->getUserId());

            if ($testValorantAccount && $testValorantAccount['lf_id'] !== null) {
                $response = array('message' => 'User already exist');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();  
            }

            $createLookingFor = $this->userlookingfor->createLookingForUserValorant(
                $this->getUserId(), 
                $this->getLfGender(),
                $this->getLfKindOfGamer(),
                $this->getLfGame(),               
                $this->getValorantMain1(), 
                $this->getValorantMain2(), 
                $this->getValorantMain1(), 
                $this->getValorantRank(), 
                $this->getValorantRole(),
                $statusChampion);

                if ($createLookingFor)
                {
    
                    $valorantLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
    
                    $lookingforUserData = array(
                        'lfId' => $valorantLookingFor['lf_id'],
                        'lfGender' => $valorantLookingFor['lf_gender'],
                        'lfKingOfGamer' => $valorantLookingFor['lf_kindofgamer'],
                        'lfGame' => $valorantLookingFor['lf_game'],
                        'valmain1Lf' => $valorantLookingFor['lf_valmain1'],
                        'valmain2Lf' => $valorantLookingFor['lf_valmain2'],
                        'valmain3Lf' => $valorantLookingFor['lf_valmain3'],
                        'valrankLf' => $valorantLookingFor['lf_valrank'],
                        'valroleLf' => $valorantLookingFor['lf_valrole']
                    );
    
                    $response = array(
                        'sessionId' => session_id(),
                        'user' => $lookingforUserData,
                        'message' => 'Success'
                    );
    
                }


            }

        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();  

    }

    public function updateLookingFor()
    {
        if (isset($_POST['submit'])) 
        {
            if (isset($_POST['game']) && $_POST['game'] == "League of Legends") {
                $userId = $this->validateInput($_POST["userId"]);

                if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
                    header("location:/userProfile?message=Token not valid");
                    exit();
                }

                $this->setUserId($userId);
                $lfGender = $this->validateInput($_POST["gender"]);
                $this->setLfGender($lfGender);
                $lfKindOfGamer = $this->validateInput($_POST["kindofgamer"]);
                $this->setLfKindOfGamer($lfKindOfGamer);
                $lfGame = $this->validateInput($_POST["game"]);
                $this->setLfGame($lfGame);
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
                $filteredServer = $this->validateInputJSON($_POST["filteredServers"]);
                $filteredServerJson = json_encode($filteredServer);
                $this->setLfFilteredServer($filteredServerJson);

                $user = $this->user->getUserById($_SESSION['userId']);

                if ($user['user_id'] != $this->getUserId())
                {
                    header("location:/userProfile?message=Could not update");
                    exit();
                }

                $statusChampion = 0;
                if (isset($_POST["skipSelection"])) {
                    $statusChampion = $this->validateInput($_POST["skipSelection"]);
                }

                $user = $this->user->getUserById($this->getUserId());

                if ($statusChampion == "1") {
                    if (empty($loLRank) || empty($loLRole))
                    {
                        if ($user['lf_lolrole']) {
                            header("location:/signup?message=Inputs cannot be empty");
                            exit();
                        } else {
                            header("location:/updateLookingForGamePage?message=Inputs cannot be empty");
                            exit();
                        }
                    }
                } else {
                    if (empty($loLMain1) || empty($loLMain2) || empty($loLMain3) || empty($loLRank) || empty($loLRole))
                    {
                        if ($user['lf_lolrole']) {
                            header("location:/signup?message=Inputs cannot be empty");
                            exit();
                        } else {
                            header("location:/updateLookingForGamePage?message=Inputs cannot be empty");
                            exit();
                        }
                    }
                }

                $updateLookingFor = $this->userlookingfor->updateLookingForData(
                    $this->getLfGender(),
                    $this->getLfKindOfGamer(),     
                    $this->getLfGame(),        
                    $this->getLoLMain1(), 
                    $this->getLoLMain2(), 
                    $this->getLoLMain3(), 
                    $this->getLoLRank(), 
                    $this->getLoLRole(),
                    $statusChampion,
                    $this->getLfFilteredServer(),
                    $this->getUserId());


                if ($updateLookingFor)
                {
                    if (!isset($_SESSION['lf_id'])) 
                    {
                            $leagueLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
                            $_SESSION['lf_id'] = $leagueLookingFor['lf_id'];
                    }
                    header("location:/userProfile?message=Updated successfully");
                    exit();  
                }
                else
                {
                    header("location:/userProfile?message=Could not update");
                    exit();  
                }

                } else {

                    $userId = $this->validateInput($_POST["userId"]);

                    if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
                        header("location:/userProfile?message=Token not valid");
                        exit();
                    }
                    
                    $this->setUserId($userId);
                    $lfGender = $this->validateInput($_POST["gender"]);
                    $this->setLfGender($lfGender);
                    $lfKindOfGamer = $this->validateInput($_POST["kindofgamer"]);
                    $this->setLfKindOfGamer($lfKindOfGamer);
                    $lfGame = $this->validateInput($_POST["game"]);
                    $this->setLfGame($lfGame);
                    $valorantMain1 = $this->validateInput($_POST["main1"]);
                    $this->setValorantMain1($valorantMain1);
                    $valorantMain2 = $this->validateInput($_POST["main2"]);
                    $this->setValorantMain2($valorantMain2);
                    $valorantMain3 = $this->validateInput($_POST["main3"]);
                    $this->setValorantMain3($valorantMain3);
                    $valorantRank = $this->validateInput($_POST["rank_valorant"]);
                    $this->setValorantRank($valorantRank);
                    $valorantRole = $this->validateInput($_POST["role_valorant"]);
                    $this->setValorantRole($valorantRole);
                    $filteredServer = $this->validateInputJSON($_POST["filteredServers"]);
                    $filteredServerJson = json_encode($filteredServer);
                    $this->setLfFilteredServer($filteredServerJson);

                    $user = $this->user->getUserById($_SESSION['userId']);

                    if ($user['user_id'] != $this->getUserId())
                    {
                        header("location:/userProfile?message=Could not update");
                        exit();
                    }
                    $statusChampion = 0;
                    if (isset($_POST["skipSelection"])) {
                        $statusChampion = $this->validateInput($_POST["skipSelection"]);
                    }

                    if ($statusChampion == "1") {
                        if (empty($valorantRank) || empty($valorantRole))
                        {
                            if ($user['lf_valrole']) {
                                header("location:/signup?message=Inputs cannot be empty");
                                exit();
                            } else {
                                header("location:/updateLookingForGamePage?message=Inputs cannot be empty");
                                exit();
                            }
                        }
                    } else {
                        if (empty($valorantMain1) || empty($valorantMain2) || empty($valorantMain3) || empty($valorantRank) || empty($valorantRole))
                        {
                            if ($user['lf_valrole']) {
                                header("location:/signup?message=Inputs cannot be empty");
                                exit();
                            } else {
                                header("location:/updateLookingForGamePage?message=Inputs cannot be empty");
                                exit();
                            }
                        }
                    }

                    $updateLookingFor = $this->userlookingfor->updateLookingForDataValorant(
                        $this->getLfGender(),
                        $this->getLfKindOfGamer(),
                        $this->getLfGame(),               
                        $this->getValorantMain1(), 
                        $this->getValorantMain2(), 
                        $this->getValorantMain3(), 
                        $this->getValorantRank(), 
                        $this->getValorantRole(),
                        $statusChampion,
                        $this->getLfFilteredServer(),
                        $this->getUserId());


                    if ($updateLookingFor)
                    {
                        if (!isset($_SESSION['lf_id'])) 
                        {
                                $valorantLookingFor = $this->userlookingfor->getLookingForUserByUserId($this->getUserId());
                                $_SESSION['lf_id'] = $valorantLookingFor['lf_id'];
                        }
                        header("location:/userProfile?message=Updated successfully");
                        exit();  
                    }
                    else
                    {
                        header("location:/userProfile?message=Could not update");
                        exit();  
                    }
                    
                }


        }

    }

    public function validateTokenWebsite($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenWebsiteByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterTokenWebsite'])) {
            $storedToken = $storedTokenData['google_masterTokenWebsite'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }

    public function validateInput($input) 
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function validateInputJSON($input) 
    {
        $input = trim($input);
    
        if (is_string($input) && (strpos($input, '[') === 0 || strpos($input, '{') === 0)) {
            $decodedInput = json_decode($input, true);
    
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decodedInput;
            }
        }
    
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getLfGender()
    {
        return $this->lfGender;
    }

    public function setLfGender($lfGender)
    {
        $this->lfGender = $lfGender;
    }

    public function getLfKindOfGamer()
    {
        return $this->lfKindOfGamer;
    }

    public function setLfKindOfGamer($lfKindOfGamer)
    {
        $this->lfKindOfGamer = $lfKindOfGamer;
    }

    public function getLfGame()
    {
        return $this->lfGame;
    }

    public function setLfGame($lfGame)
    {
        $this->lfGame = $lfGame;
    }

    public function getLfFilteredServer()
    {
        return $this->lfFilteredServer;
    }

    public function setLfFilteredServer($lfFilteredServer)
    {
        $this->lfFilteredServer = $lfFilteredServer;
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

    public function getValorantMain1()
    {
        return $this->valorantMain1;
    }

    public function setValorantMain1($valorantMain1)
    {
        $this->valorantMain1 = $valorantMain1;
    }

    public function getValorantMain2()
    {
        return $this->valorantMain2;
    }

    public function setValorantMain2($valorantMain2)
    {
        $this->valorantMain2 = $valorantMain2;
    }

    public function getValorantMain3()
    {
        return $this->valorantMain3;
    }

    public function setValorantMain3($valorantMain3)
    {
        $this->valorantMain3 = $valorantMain3;
    }

    public function getValorantRank()
    {
        return $this->valorantRank;
    }

    public function setValorantRank($valorantRank)
    {
        $this->valorantRank = $valorantRank;
    }


    public function getValorantRole()
    {
        return $this->valorantRole;
    }

    public function setValorantRole($valorantRole)
    {
        $this->valorantRole = $valorantRole;
    }
}
