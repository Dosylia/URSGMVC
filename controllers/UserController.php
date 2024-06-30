<?php

namespace controllers;

use models\User;
use models\FriendRequest;
use models\ChatMessage;
use models\LeagueOfLegends;
use models\UserLookingFor;
use models\GoogleUser;
use models\MatchingScore;
use traits\SecurityController;

class UserController
{
    use SecurityController;

    private User $user;
    private FriendRequest $friendrequest;
    private ChatMessage $chatmessage;
    private LeagueOfLegends $leagueoflegends;
    private UserLookingFor $userlookingfor;    
    private GoogleUser $googleUser;
    private MatchingScore $matchingscore;
    private $googleUserId;
    private $username;
    private $gender;
    private $age;
    private $kindOfGamer;
    private $game;
    private $shortBio;
    private $discord;
    private $twitter;
    private $instagram;
    private $twitch;
    private $fileName;
    
    public function __construct()
    {
        $this -> user = new User();
        $this -> friendrequest = new FriendRequest();
        $this -> chatmessage = new ChatMessage();
        $this -> leagueoflegends = new LeagueOfLegends();
        $this -> userlookingfor = new UserLookingFor();
        $this -> googleUser = new GoogleUser();
        $this -> matchingscore = new MatchingScore();
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

    public function updateSocial()
    {
        if (isset($_POST['submit']))
        {
            $username = $this->validateInput($_GET["username"]);
            $this->setUsername($username);
            $discord = $this->validateInput($_POST["discord"]);
            $this->setDiscord($discord);
            $twitter = $this->validateInput($_POST["twitter"]);
            $this->setTwitter($twitter);
            $instagram = $this->validateInput($_POST["instagram"]);
            $this->setInstagram($instagram);
            $twitch = $this->validateInput($_POST["twitch"]);
            $this->setTwitch($twitch);

            $updateSocial = $this->user->updateSocial($this->getUsername(),  $this->getDiscord(), $this->getTwitter(), $this->getInstagram(), $this->getTwitch());


            if ($updateSocial)
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

    public function updateProfile()
    {
        if (isset($_POST['submit']))
        {
            $this->setGoogleUserId($googleUserId);
            $username = $this->validateInput($_SESSION["username"]);
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

            if ($this->invalidUid($this->getUsername()) !== false) {
                header("location:index.php?action=signup&message=Username is not valid");
                exit();
            }

            $updateUser = $this->user->updateUser($this->getUsername(), $this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame());


            if ($updateUser)
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

    public function updatePicture()
    {
           
        $targetDir = "public/upload/";
        $fileName = basename($_FILES["file"]["name"]);
        $this->setFileName($fileName);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        if (isset($_POST["submit"]) && !empty($_FILES["file"]["name"])) 
        {
            $username = $this->validateInput($_GET["username"]);
            $this->setUsername($username);

            $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'pdf');

            if (in_array($fileType, $allowTypes)) 
            {
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) 
                {
                    $uploadPicture = $this->user->uploadPicture($this->getUsername(), $this->getFilename());

                    if ($uploadPicture)
                    {
                        header("location:index.php?action=userProfile&message=Updated successfully");
                        exit;      
                    }
                    else 
                    {
                        header("location:index.php?action=userProfile&message=Couldn't update");
                        exit;      
                    }
                }
                else
                {
                    header("location:index.php?action=userProfile&message=Error uploading");
                    exit;        
                }

            }
            else
            {
                header("location:index.php?action=userProfile&message=Wrong type of picture"); // Not accepted format
                exit;   
            }
        }
        else 
        {
            header("location:index.php?action=userProfile&message=Nothing to upload"); // If no picture or no form
            exit;  
        }   
    }

    public function pageswiping()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $usersAll = $this-> user -> getAllUsers();
            if ($user && $usersAll) {
                echo '<script>';
                echo 'window.user = ' . json_encode($user) . ';';
                echo 'window.usersAll = ' . json_encode($usersAll) . ';';
                echo '</script>';
            }
            $unreadCount = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $usersAfterMathing = $this->matchingscore->getMatchingScore($_SESSION['userId']);
            $userFriendRequest = $this->friendrequest->skipUserSwipping($_SESSION['userId']);

            foreach ($usersAfterMathing as $match) {
                $matchedUserId = $match['match_userMatched'];
                if (!in_array($matchedUserId, $userFriendRequest)) {
                    $userMatched = $this->user->getUserById($matchedUserId);
                    break;
                }
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

    public function pageUserProfile()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $usersAll = $this-> user -> getAllUsers();
            $unreadCount = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

            $template = "views/swiping/swiping_profile";
            $page_title = "URSG - Profile";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: index.php");
            exit();
        }
    }

    public function pageAnotherUserProfile()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $username = $_GET['username'];
            $user = $this-> user -> getUserByUsername($username);
            $allUsers = $this-> user -> getAllUsers();
            $unreadCount = $this-> chatmessage -> countMessage($user['user_id']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lolUser = $this->leagueoflegends->getLeageUserByUserId($user['user_id']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

            $template = "views/swiping/swiping_profile_other";
            $page_title = "URSG - Profile " . $username;
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            $username = $_GET['username'];
            $user = $this-> user -> getUserByUsername($username);
            $lolUser = $this->leagueoflegends->getLeageUserByUserId($user['user_id']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
            $template = "views/swiping/swiping_profile_other";
            $page_title = "URSG - Profile " . $username;
            require "views/layoutSwiping_noheader.phtml";
        }
    }

    public function pageUpdateProfile()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $allUsers = $this-> user -> getAllUsers();
            $unreadCount = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);

            $template = "views/swiping/update_profile";
            $page_title = "URSG - Profile";
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

    public function getDiscord()
    {
        return $this->discord;
    }

    public function setDiscord($discord)
    {
        $this->discord = $discord;
    }

    public function getTwitter()
    {
        return $this->twitter;
    }

    public function setTwitter($twitter)
    {
        $this->twitter = $twitter;
    }

    public function getInstagram()
    {
        return $this->instagram;
    }

    public function setInstagram($instagram)
    {
        $this->instagram = $instagram;
    }

    public function getTwitch()
    {
        return $this->twitch;
    }

    public function setTwitch($twitch)
    {
        $this->twitch = $twitch;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
}
