<?php

namespace controllers;

use models\User;
use models\FriendRequest;
use models\ChatMessage;
use traits\SecurityController;

class UserController
{
    use SecurityController;

    private User $user;
    private FriendRequest $friendrequest;
    private ChatMessage $chatmessage;
    private $googleUserId;
    private $username;
    private $gender;
    private $age;
    private $kindOfGamer;
    private $game;
    private $shortBio;

    
    public function __construct()
    {
        $this -> user = new User();
        $this -> friendrequest = new FriendRequest();
        $this -> chatmessage = new ChatMessage();
    }

    public function createUser()
    {
        if (isset($_POST['submit']))
        {

            $googleUserId = $this->validateInput($_POST["googleId"]);
            $this->setGoogleUserId($googleUserId);
            $username = $this->validateInput($_POST["username"]);
            $this->setUsername($username);
            $gender = $this->validateInput($_POST["gender"]);
            $this->setGender($gender);
            $age = $this->validateInput($_POST["age"]);
            $this->setAge($age);
            $kindofgamer = $this->validateInput($_POST["kindofgamer"]);
            $this->setKindOfGamer($kindofgamer);
            $game = $this->validateInput($_POST["game"]);
            $this->setGame($game);
            $short_bio = $this->validateInput($_POST["short_bio"]);
            $this->setShortBio($short_bio);

            if ($this->emptyInputSignup($this->getUsername(), $this->getAge(), $this->getShortBio()) !== false) {
                header("location:index.php?action=signup&message=Inputs cannot be empty");
                exit();
            }

            if ($this->user->getUserByUsername($this->getUsername())) {
                header("location:index.php?action=signup&message=Username already exists");
                exit();
            }

            if ($this->invalidUid($this->getUsername()) !== false) {
                header("location:index.php?action=signup&message=Username is not valid");
                exit();
            }

            $createUser = $this->user->createUser($this->getGoogleUserId(), $this->getUsername(), $this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame());

            if($createUser)
            {
                $user = $this->user->getUserByUsername($this->getUsername());

                    if (session_status() == PHP_SESSION_NONE) 
                    {
                        $lifetime = 7 * 24 * 60 * 60;
                        session_set_cookie_params($lifetime);
                        session_start();
                    }
                    
                        $_SESSION['userId'] = $user['user_id'];
                        $_SESSION['username'] = $user['user_username'];
                        $_SESSION['gender'] = $user['user_gender'];
                        $_SESSION['age'] = $user['user_age'];
                        $_SESSION['kindOfGamer'] = $user['user_kindOfGamer'];
                        $_SESSION['game'] = $user['user_game'];

    
                if($user['user_game'] === "leagueoflegends" || $user['user_game'] === "both") 
                { 
                    header("location:index.php?action=leagueuser&user_id=".$user['user_id']);
                    exit();
                }
                else if($user['user_game'] === "valorant")
                {
                    header("location:index.php?action=valorantUser&user_id=".$user['user_id']);
                    exit();
                }
            }
        }
    }

    public function pageswiping()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $allUsers = $this-> user -> getAllUsers();
            $unreadCount = $this-> chatmessage -> countMessage($user['user_id']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($user['user_id']);


            // Profile picture
            if (isset($user['user_picture']) && $user['user_picture'] !== null) 
            {
                $picture = $user['user_picture'];
            }
            else
            {
                $picture = 'public/images/defaultprofilepicture.jpg';
            }


            $template = "views/swiping/swiping_main";
            $page_title = "URSG - Swiping";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: index.php");
            exit();
        }
    }

    public function emptyInputSignup($username, $age, $short_bio) 
    {
        $result;
        if (empty($username) || empty($age) || empty($short_bio))
        {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    public function invalidUid($username) 
    {
        $result;
        if (strlen($username) > 20 || !preg_match("/^[a-zA-Z0-9]*$/", $username)) {
            $result = true;
        } 
        else {
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

    public function getGoogleUserId()
    {
        return $this->googleUserId;
    }

    public function setGoogleUserId($googleUserId)
    {
        $this->googleUserId = $googleUserId;
    }


    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        $this->age = $age;
    }

    public function getKindOfGamer()
    {
        return $this->kindOfGamer;
    }

    public function setKindOfGamer($kindOfGamer)
    {
        $this->kindOfGamer = $kindOfGamer;
    }

    public function getGame()
    {
        return $this->game;
    }

    public function setGame($game)
    {
        $this->game = $game;
    }

    public function getShortBio()
    {
        return $this->shortBio;
    }

    public function setShortBio($shortBio)
    {
        $this->shortBio = $shortBio;
    }
}