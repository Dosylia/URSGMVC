<?php

namespace controllers;

use models\LeagueOfLegends;
use models\GoogleUser;
use traits\SecurityController;

class LeagueOfLegendsController
{
    use SecurityController;

    private LeagueOfLegends $leagueOfLegends;
    private GoogleUser $googleUser;  

    
    public function __construct()
    {
        $this -> leagueOfLegends = new LeagueOfLegends();
        $this -> googleUser = new GoogleUser();
    }

    public function pageLeagueUser()
    {
        $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']); //ADD LATER THIS SECURITE
        
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

    }


}