<?php

namespace controllers;

use models\UserLookingFor;
use models\GoogleUser;
use models\User;
use traits\SecurityController;

class UserLookingForController
{
    use SecurityController;

    private UserLookingFor $userLookingFor;
    private GoogleUser $googleUser; 
    private User $user; 

    
    public function __construct()
    {
        $this -> userLookingFor = new userLookingFor();
        $this -> googleUser = new GoogleUser();
        $this -> user = new User();
    }

    public function pageLookingFor()
    {
        $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']); //ADD LATER THIS SECURITE, USER ID CHECK

        if($this->isConnectWebsite())
        {
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
        }
        
        if($this->isConnectGoogle() && !isset($googleUser['user_username']))
        {   
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutHome.phtml";
        }
        else
        {
            header("Location: index.php");
            exit();
        }
    }

}
