<?php

namespace controllers;

use models\LeagueOfLegends;
use models\User;
use models\FriendRequest;
use models\ChatMessage;
use traits\SecurityController;

class LeagueOfLegendsController
{
    use SecurityController;

    private LeagueOfLegends $leagueOfLegends;
    private FriendRequest $friendrequest;
    private ChatMessage $chatmessage;
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
        $this -> chatmessage = new ChatMessage();
    }

    public function pageLeagueUser()
    {

        if (isset($_SESSION['mode'])) {
            $mode = $_SESSION['mode'];
          } else {
            $mode = 'light';
          }
          
          $darkMode = ($mode === 'dark');

        if ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectLeague()) {
            // Code block 1: User is connected via Google, Website and has League data, need looking for
            $lolUser = $this->leagueOfLegends->getLeageUserByUsername($_SESSION['lol_account']);
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectLeague()){
            // Code block 2: User is connected via Google, Website but not connected to LoL LATER ADD VALORANT CHECK
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $template = "views/signup/leagueoflegendsuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite()) {
            // Code block 3: User is connected via Google but doesn't have a username
            $template = "views/signup/basicinfo";
            $title = "Sign up";
            $page_title = "URSG - Sign";
            require "views/layoutHome.phtml";
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
            $unreadCount = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lolUser = $this->leagueOfLegends->getLeageUserByLolId($_SESSION['lol_id']);

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
