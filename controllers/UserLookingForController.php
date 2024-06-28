<?php

namespace controllers;

use models\UserLookingFor;
use models\GoogleUser;
use models\User;
use models\LeagueOfLegends;
use traits\SecurityController;

class UserLookingForController
{
    use SecurityController;

    private UserLookingFor $userlookingfor;
    private GoogleUser $googleUser; 
    private User $user; 
    private LeagueOfLegends $leagueoflegends;
    private $userId;
    private $lfGender;
    private $lfKindOfGamer;
    private $lfGame;
    private $loLMain1;
    private $loLMain2;
    private $loLMain3;
    private $loLRank;
    private $loLRole;


    
    public function __construct()
    {
        $this -> userlookingfor = new userLookingFor();
        $this -> googleUser = new GoogleUser();
        $this -> user = new User();
        $this -> leagueoflegends = new LeagueOfLegends();
    }

    public function pageLookingFor()
    {

        $googleUser = $this->googleUser->getGoogleUserByEmail($_SESSION['email']);
        $secondTierUser = $this->user->getUserDataByGoogleUserId($_SESSION['google_userId']);

        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague()) {
            // Code block 1: User is connected via Google, Website and has League data, need looking for
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectLeague()){
            // Code block 2: User is connected via Google, Website but not connected to LoL LATER ADD VALORANT CHECK
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
                $template = "views/signup/leagueoflegendsuser";
                $title = "More about you";
                $page_title = "URSG - Sign up";
                require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && !isset($googleUser['user_username'])) {
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

    public function createLookingFor()
    {
        if (isset($_POST['submit'])) 
        {

            $userId = $this->validateInput($_POST["userId"]);
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

            $createLookingFor = $this->userlookingfor->createLookingForUser(
                $this->getUserId(), 
                $this->getLfGender(),
                $this->getLfKindOfGamer(),
                $this->getLfGame(),               
                $this->getLoLMain1(), 
                $this->getLoLMain2(), 
                $this->getLoLMain3(), 
                $this->getLoLRank(), 
                $this->getLoLRole());


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

                header("location:index.php?action=swiping");
                exit();
            }

        }

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
}
