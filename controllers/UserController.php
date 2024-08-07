<?php

namespace controllers;

use models\User;
use models\FriendRequest;
use models\ChatMessage;
use models\LeagueOfLegends;
use models\UserLookingFor;
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
        $this -> matchingscore = new MatchingScore();
    }

    public function createUserPhone()
    {
        // Start the session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    
        // Check if the request method is POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get the raw POST data
            $postData = file_get_contents('php://input');
            // Decode the JSON data
            $data = json_decode($postData, true);
    
            // Validate and set user data
            $googleUserId = $this->validateInput($data->googleId);
            $this->setGoogleUserId($googleUserId);
            $username = $this->validateInput($data->username);
            $this->setUsername($username);
            $gender = $this->validateInput($data->gender);
            $this->setGender($gender);
            $age = $this->validateInput($data->age);
            $this->setAge($age);
            $kindofgamer = $this->validateInput($data->kindOfGamer);
            $this->setKindOfGamer($kindofgamer);
            $game = $this->validateInput($data->game);
            $this->setGame($game);
            $short_bio = $this->validateInput($data->shortBio);
            $this->setShortBio($short_bio);
    
            // Perform validation and user creation logic
            if ($this->emptyInputSignup($this->getUsername(), $this->getAge(), $this->getShortBio()) !== false) {
                echo json_encode(['message' => 'Inputs cannot be empty']);
                exit();
            }
    
            if ($this->user->getUserByUsername($this->getUsername())) {
                echo json_encode(['message' => 'Username already exists']);
                exit();
            }
    
            if ($this->invalidUid($this->getUsername()) !== false) {
                echo json_encode(['message' => 'Username is not valid']);
                exit();
            }
    
            $createUser = $this->user->createUser($this->getGoogleUserId(), $this->getUsername(), $this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame());
    
            if ($createUser) {
                $user = $this->user->getUserByUsername($this->getUsername());
    
                // Set session variables
                $_SESSION['userId'] = $user['user_id'];
                $_SESSION['username'] = $user['user_username'];
                $_SESSION['gender'] = $user['user_gender'];
                $_SESSION['age'] = $user['user_age'];
                $_SESSION['kindOfGamer'] = $user['user_kindOfGamer'];
                $_SESSION['game'] = $user['user_game'];
    
                // Return session ID and user data
                echo json_encode([
                    'sessionId' => session_id(),
                    'user' => $user
                ]);
                exit();
            }
        }
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
                header("location:/signup?message=Inputs cannot be empty");
                exit();
            }

            if ($this->user->getUserByUsername($this->getUsername())) {
                header("location:/signup?message=Username already exists");
                exit();
            }

            if ($this->invalidUid($this->getUsername()) !== false) {
                header("location:/signup?message=Username is not valid");
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

    
                if($user['user_game'] === "League of Legends" || $user['user_game'] === "LoL and Valorant") 
                { 
                    header("location:/leagueuser?user_id=".$user['user_id']);
                    exit();
                }
                else if($user['user_game'] === "valorant")
                {
                    header("location:/valorantUser?user_id=".$user['user_id']);
                    exit();
                }
                else {
                    header("location:/home");
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
                header("location:/userProfile?message=Udpated successfully");
                exit();  
            }
            else
            {
                header("location:/userProfile?message=Could not update");
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
                header("location:/signup?message=Inputs cannot be empty");
                exit();
            }

            if ($this->invalidUid($this->getUsername()) !== false) {
                header("location:/signup?message=Username is not valid");
                exit();
            }

            $updateUser = $this->user->updateUser($this->getUsername(), $this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame());


            if ($updateUser)
            {
                header("location:/userProfile?message=Udpated successfully");
                exit();  
            }
            else
            {
                header("location:/userProfile?message=Could not update");
                exit();
            }
        }

    }

    public function updatePicture() {
        $targetDir = "public/upload/";
        $fileName = basename($_FILES["file"]["name"]);
        $this->setFileName($fileName);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        if (isset($_POST["submit"]) && !empty($_FILES["file"]["name"])) {
            $username = $this->validateInput($_GET["username"]);
            $this->setUsername($username);

            $allowTypes = array('jpg', 'jpeg', 'png', 'gif');

            if (in_array($fileType, $allowTypes)) {
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                    // Check for animated images (for GIFs)
                    if ($fileType === 'gif' && $this->isAnimatedGif($targetFilePath)) {
                        header("location:/userProfile?message=Animated GIFs are not allowed");
                        exit;
                    }

                    // Resize the image to 200x200
                    $resizedFilePath = $targetDir . 'resized_' . $fileName;
                    if ($this->resizeImage($targetFilePath, $resizedFilePath, 200, 200)) {
                        // Update the database with the resized image
                        $uploadPicture = $this->user->uploadPicture($this->getUsername(), 'resized_' . $this->getFilename());

                        if ($uploadPicture) {
                            header("location:/userProfile?message=Updated successfully");
                            exit;
                        } else {
                            header("location:/userProfile?message=Couldn't update");
                            exit;
                        }
                    } else {
                        header("location:/userProfile?message=Error resizing image");
                        exit;
                    }
                } else {
                    header("location:/userProfile?message=Error uploading");
                    exit;
                }
            } else {
                header("location:/userProfile?message=Wrong type of picture"); // Not accepted format
                exit;
            }
        } else {
            header("location:/userProfile?message=Nothing to upload"); // If no picture or no form
            exit;
        }
    }
    

    public function resizeImage($sourcePath, $destPath, $newWidth, $newHeight) {
        list($width, $height, $imageType) = getimagesize($sourcePath);

        // Create a new image from file
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $src = imagecreatefromgif($sourcePath);
                break;
            default:
                return false; // Unsupported image type
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $result = imagejpeg($dst, $destPath); // Save the resized image to the destination path
        imagedestroy($src);
        imagedestroy($dst);
        return $result;
    }

    public function isAnimatedGif($filePath) {
            $file = fopen($filePath, 'rb');
            $count = 0;

            while (!feof($file) && $count < 2) {
                $chunk = fread($file, 1024 * 100); // Read 100KB chunks
                $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);
            }

            fclose($file);
            return $count > 1;
        }

    public function pageswiping()
    {     
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersAll = $this-> user -> getAllUsersExceptFriends($_SESSION['userId']);
            if ($user && $usersAll) {
                $userData = json_encode($user);
                $usersAllData = json_encode($usersAll);
            }
            $unreadCounts = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $current_url = "https://ur-sg.com/swiping";
            $template = "views/swiping/swiping_main";
            $page_title = "URSG - Swiping";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function getUserMatching()
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $user = $this->user->getUserById($userId);
            $unreadCounts = $this->chatmessage->countMessage($_SESSION['userId']);
            $pendingCount = $this->friendrequest->countFriendRequest($_SESSION['userId']);
            $usersAfterMatching = $this->matchingscore->getMatchingScore($_SESSION['userId']);
            $userFriendRequest = $this->friendrequest->skipUserSwipping($_SESSION['userId']);
            
            $data = ['success' => false, 'error' => 'No matching users found'];
            
            foreach ($usersAfterMatching as $match) {
                $matchedUserId = $match['match_userMatched'];
                if (!in_array($matchedUserId, $userFriendRequest)) {
                    $userMatched = $this->user->getUserById($matchedUserId);
    
                    if ($userMatched) {
                        $data = [
                            'success' => true,
                            'user' => [
                                'user_id' => $userMatched['user_id'],
                                'user_username' => $userMatched['user_username'],
                                'user_picture' => $userMatched['user_picture'],
                                'user_age' => $userMatched['user_age'],
                                'user_game' => $userMatched['user_game'],
                                'user_gender' => $userMatched['user_gender'],
                                'user_kindOfGamer' => $userMatched['user_kindOfGamer'],
                                'user_shortBio' => $userMatched['user_shortBio'],
                                'lol_main1' => $userMatched['lol_main1'],
                                'lol_main2' => $userMatched['lol_main2'],
                                'lol_main3' => $userMatched['lol_main3'],
                                'lol_rank' => $userMatched['lol_rank'],
                                'lol_role' => $userMatched['lol_role'],
                                'lol_account' => $userMatched['lol_account'],
                                'lol_sUsername' => $userMatched['lol_sUsername'],
                                'lol_sLevel' => $userMatched['lol_sLevel'],
                                'lol_sRank' => $userMatched['lol_sRank'],
                                'lol_sProfileIcon' => $userMatched['lol_sProfileIcon'],
                                'lol_sUsername' => $userMatched['lol_sUsername']
                            ]
                        ];
                        break;
                    }
                }
            }
            echo json_encode($data);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }
    

    public function pageUserProfile()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {
            // Get important datas
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersAll = $this-> user -> getAllUsers();
            $unreadCounts = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $current_url = "https://ur-sg.com/userProfile";
            $template = "views/swiping/swiping_profile";
            $page_title = "URSG - Profile";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            if (isset($_GET['username']))
            {
                if($_GET['username'] !== $_SESSION['username']) 
                {
                    $username = $_GET['username'];
                    header("Location: /anotherUser&username=" . $username);
                    exit();                    
                }
            }
            else 
            {
                header("Location: /");
                exit();
            }
        }
    }

    public function pageAnotherUserProfile()
    {

        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {
            // Get important datas

            $username = $_GET['username'];
            if ($_GET['username'] === $_SESSION['username'])
            {
                header("Location: /userProfile");
                exit();
            }
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $anotherUser = $this-> user -> getUserByUsername($username);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $lolUser = $this->leagueoflegends->getLeageUserByUserId($anotherUser['user_id']);
            $current_url = "https://ur-sg.com/anotherUser";
            $template = "views/swiping/swiping_profile_other";
            $page_title = "URSG - Profile " . $username;
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            $username = $_GET['username'];
            $anotherUser = $this-> user -> getUserByUsername($username);
            $lolUser = $this->leagueoflegends->getLeageUserByUserId($anotherUser['user_id']);
            $current_url = "https://ur-sg.com/anotherUser";
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
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $allUsers = $this-> user -> getAllUsers();
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);

            $kindofgamers = ["Chill" => "Chill / Normal games", "Competition" => "Competition / Ranked", "Competition and Chill" => "Competition/Ranked and chill"];
            $genders = ["Male", "Female", "Non binary", "Male and Female", "All"];
            $current_url = "https://ur-sg.com/updateProfile";
            $template = "views/swiping/update_profile";
            $page_title = "URSG - Profile";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function saveDarkMode()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $requestData = json_decode(file_get_contents('php://input'), true);
            $mode = $requestData['mode'];
          
            $_SESSION['mode'] = $mode;
          
            $darkMode = ($mode === 'dark');
            $response = array('status' => 'success', 'message' => 'Mode preference saved successfully.');
            echo json_encode($response);
          } else {
            $response = array('status' => 'error', 'message' => 'Invalid request method.');
            echo json_encode($response);
          }
          
          $darkMode = ($_SESSION['mode'] === 'dark');        
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