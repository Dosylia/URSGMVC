<?php

namespace controllers;

use models\LeagueOfLegends;
use models\GoogleUser;
use models\User;
use traits\SecurityController;

class LeagueOfLegendsController
{
    use SecurityController;

    private LeagueOfLegends $leagueOfLegends;
    private GoogleUser $googleUser;  
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
        $this -> googleUser = new GoogleUser();
        $this -> user = new User();
    }

    public function pageLeagueUser()
    {
        $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']); //ADD LATER THIS SECURITE, USER ID CHECK

        if($this->isConnectWebsite())
        {
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
        }
        
        if($this->isConnectGoogle() && !isset($googleUser['user_username']))
        {   
            $template = "views/signup/leagueoflegendsuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            require "views/layoutHome.phtml";
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
                header("location:index.php?action=lookingforuserlol");
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
