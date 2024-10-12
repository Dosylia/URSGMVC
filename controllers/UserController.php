<?php

namespace controllers;

use models\User;
use models\FriendRequest;
use models\ChatMessage;
use models\LeagueOfLegends;
use models\Valorant;
use models\UserLookingFor;
use models\MatchingScore;
use models\Items;
use traits\SecurityController;

class UserController
{
    use SecurityController;

    private User $user;
    private FriendRequest $friendrequest;
    private ChatMessage $chatmessage;
    private LeagueOfLegends $leagueoflegends;
    private Valorant $valorant;
    private UserLookingFor $userlookingfor;    
    private MatchingScore $matchingscore;
    private Items $items;
    private $googleUserId;
    private $userId;
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
    private $loLMain1;
    private $loLMain2;
    private $loLMain3;
    private $loLRank;
    private $loLRole;
    private $loLServer;
    private $lfGender;
    private $lfKindOfGamer;
    private $lfGame;
    private $loLMain1Lf;
    private $loLMain2Lf;
    private $loLMain3Lf;
    private $loLRankLf;
    private $loLRoleLf;
    private $valorantMain1;
    private $valorantMain2;
    private $valorantMain3;
    private $valorantRank;
    private $valorantRole;
    private $valorantMain1Lf;
    private $valorantMain2Lf;
    private $valorantMain3Lf;
    private $valorantRankLf;
    private $valorantRoleLf;
    
    public function __construct()
    {
        $this -> user = new User();
        $this -> friendrequest = new FriendRequest();
        $this -> chatmessage = new ChatMessage();
        $this -> leagueoflegends = new LeagueOfLegends();
        $this -> valorant = new Valorant();
        $this -> userlookingfor = new UserLookingFor();
        $this -> matchingscore = new MatchingScore();
        $this -> items = new Items();
    }

    public function getAllUsers()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['allUsers'])) 
        {
            $allUsers = $this->user->getAllUsers();

            if ($allUsers)
            {
                $response = array(
                    'allUsers' => $allUsers,
                    'message' => 'Success'
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } else {
                $response = array('message' => 'Couldnt get all users');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }

        } else {
            $response = array('message' => 'Cant access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;  
        }
    }

    public function pageLeaderboard()
    {

        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {

            // Get important datas
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $allUsers = $this-> user -> getAllUsers();
            $usersPerPage = 50;
            $totalUsers = count($allUsers);
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $totalPages = ceil($totalUsers / $usersPerPage);

            if ($page < 1) {
                $page = 1;
            } elseif ($page > $totalPages) {
                $page = $totalPages;
            }
            

            $offset = ($page - 1) * $usersPerPage;

            usort($allUsers, function($a, $b) {
                return $b['user_currency'] - $a['user_currency'];
            });
            
            $usersOnPage = array_slice($allUsers, $offset, $usersPerPage);

            $current_url = "https://ur-sg.com/leaderboard";
            $template = "views/swiping/leaderboard";
            $page_title = "URSG - Leaderboard";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function createUserPhone()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['userData'])) // DATA SENT BY AJAX
        {
            $data = json_decode($_POST['userData']);
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
                $response = array('message' => 'Inputs cannot be empty');
                echo json_encode($response);
                exit;
            }

            if ($this->user->getUserByUsername($this->getUsername())) {
                $response = array('message' => 'Username already exists');
                echo json_encode($response);
                exit;
            }

            if ($this->invalidUid($this->getUsername()) !== false) {
                $response = array('message' => 'Username is not valid');
                echo json_encode($response);
                exit;
            }

            if ($this->getAge() > 99)
            {
                $response = array('message' => 'Age is not valid');
                echo json_encode($response);
                exit;
            }

            if (strlen($this->getShortBio()) > 200) {
                $response = array('message' => 'Short bio is too long');
                echo json_encode($response);
                exit;
            }

            $createUser = $this->user->createUser($this->getGoogleUserId(), $this->getUsername(), $this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame());

            if ($createUser) {
                $user = $this->user->getUserByUsername($this->getUsername());

                $userData = array(
                    'userId' => $user['user_id'],
                    'username' => $user['user_username'],
                    'gender' => $user['user_gender'],
                    'age' => $user['user_age'],
                    'kindOfGamer' => $user['user_kindOfGamer'],
                    'game' => $user['user_game'],
                    'shortBio' => $user['user_shortBio'],
                    'currency' => $user['user_currency'],
                    'isVip' => $user['user_isVip'],
                    'hasChatFilter' => ['user_hasChatFilter'] ?? null
                );

                $response = array(
                    'sessionId' => session_id(),
                    'user' => $userData,
                    'message' => 'Success'
                );
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;  
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

            if ($this->getAge() > 99)
            {
                header("location:/signup?message=Age is not valid");
                exit();
            }

            if (strlen($this->getShortBio()) > 200) {
                header("location:/signup?message=Short bio is too long");
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
                else if($user['user_game'] === "Valorant")
                {
                    header("location:/valorantuser?user_id=".$user['user_id']);
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
        $response = array('message' => 'Error');
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

    public function updateSocialPhone()
    {
        if (isset($_POST['userData'])) // DATA SENT BY AJAX
        {
            $data = json_decode($_POST['userData']);
            $username = $this->validateInput($data->username);
            $this->setUsername($username);
            $discord = $this->validateInput($data->discord);
            $this->setDiscord($discord);
            $twitter = $this->validateInput($data->twitter);
            $this->setTwitter($twitter);
            $instagram = $this->validateInput($data->instagram);
            $this->setInstagram($instagram);
            $twitch = $this->validateInput($data->twitch);
            $this->setTwitch($twitch);

            $updateSocial = $this->user->updateSocial($this->getUsername(),  $this->getDiscord(), $this->getTwitter(), $this->getInstagram(), $this->getTwitch());


            if ($updateSocial)
            {
                $response = array('message' => 'Success');
                echo json_encode($response);
                exit();  
            }
            else
            {
                $response = array('message' => 'Couldnt update user');
                echo json_encode($response);
                exit();
            }
        } else {
            $response = array('message' => 'Couldnt update user');
            echo json_encode($response);
            exit();
        }

    }

    public function updateProfile()
    {
        if (isset($_POST['submit']))
        {
            $userId = $this->validateInput($_POST["userId"]);
            $this->setUserId($userId);
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

            if ($this->emptyInputSignupUpdate($this->getAge(), $this->getShortBio()) !== false) {
                header("location:/signup?message=Inputs cannot be empty");
                exit();
            }

            if ($this->invalidUid($this->getUsername()) !== false) {
                header("location:/signup?message=Username is not valid");
                exit();
            }

            if ($this->getAge() > 99)
            {
                header("location:/signup?message=Age is not valid");
                exit();
            }

            if (!empty($userId))
            {
                $user = $this->user->getUserById($this->getUserId());
            }

            $updateUser = $this->user->updateUser($this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame(), $this->getUserId());


            if ($updateUser)
            {

                if ($user['user_game'] !== $this->getGame())
                {
                    if ($user['user_game'] == "League of Legends")
                    {
                        unset($_SESSION['lol_id']);
                        unset($_SESSION['lf_id']);
                        if ($user['valorant_id']) {
                            $_SESSION['valorant_id'] = $user['valorant_id'];

                            if($user['lf_valmain1'] !== NULL)
                            {
                                $_SESSION['lf_id'] = $user['lf_id']; 
                                header("location:/userProfile?message=Udpated successfully");
                                exit();  
                            } else {
                                header("location:/updateLookingForGame?message=Udpated successfully");
                                exit();  
                            }
                        } else {
                            header("location:/valorantuser?user_id=".$user['user_id']);
                            exit();  
                        }
                    }
                    else 
                    {
                        unset($_SESSION['valorant_id']);
                        unset($_SESSION['lf_id']);

                        if ($user['lol_id']) {
                            $_SESSION['lol_id'] = $user['lol_id'];

                            if($user['lf_lolmain1'] !== NULL)
                            {
                                $_SESSION['lf_id'] = $user['lf_id']; 
                                header("location:/userProfile?message=Udpated successfully");
                                exit();  
                            } else {
                                header("location:/updateLookingForGamePage?message=Udpated successfully");
                                exit();  
                            }
                        } else {
                            header("location:/leagueuser?user_id=".$user['user_id']);
                            exit();  
                        }

                        header("location:/updateLookingForGamePage?message=Udpated successfully");
                        exit();  
                    }
                }
                else 
                {
                    header("location:/userProfile?message=Udpated successfully");
                    exit();  
                }
            }
            else
            {
                header("location:/userProfile?message=Could not update");
                exit();
            }
        }

    }

    public function getUserData()
    {
        $response = array('message' => 'Error');
    
        if (isset($_POST['userId'])) {
            // Directly get the userId from POST data
            $userId = $this->validateInput($_POST['userId']); // No need for json_decode here
    
            // Assuming validateInput returns an integer or valid user ID
            $user = $this->user->getUserById($userId);
    
            if ($user) {
                $response = array(
                    'user' => $user,
                    'message' => 'Success'
                );
            } else {
                $response = array('message' => 'Could not get user');
            }
        } else {
            $response = array('message' => 'Proper data not sent');
        }
    
        echo json_encode($response); // Make sure to output the JSON response
    }

    public function registerToken()
    {
        $response = array('message' => 'Error');
    
        if (isset($_POST['userId']) && isset($_POST['token'])) {
            $userId = $this->validateInput($_POST['userId']);
            $token = $this->validateInput($_POST['token']);
    
            $registerToken = $this->user->registerToken($userId, $token);
    
            if ($registerToken) {
                $response = array('message' => 'Success');
            } else {
                $response = array('message' => 'Could not register token');
            }
        } else {
            $response = array('message' => 'Proper data not sent');
        }
    
        echo json_encode($response);
    }

    public function updateUserPhone()
    {
        $response = array('message' => 'Error');
        
        if (isset($_POST['userData'])) // Check if data is sent via AJAX
        {
            $data = json_decode($_POST['userData']);

            if ($data->userId) {
                $user = $this->user->getUserById($data->userId);
            }

            if ($data->game == "League of Legends") 
            {
            // Validate and set user data
            $userId = $this->validateInput($data->userId);
            $username = $this->validateInput($data->username);
            $gender = $this->validateInput($data->gender);
            $age = $this->validateInput($data->age);
            $kindOfGamer = $this->validateInput($data->kindOfGamer);
            $game = $this->validateInput($data->game);
            $shortBio = $this->validateInput($data->shortBio);
            $main1 = $this->validateInput($data->main1);
            $main2 = $this->validateInput($data->main2);
            $main3 = $this->validateInput($data->main3);
            $rank = $this->validateInput($data->rank);
            $role = $this->validateInput($data->role);
            $server = $this->validateInput($data->server);
            $genderLf = $this->validateInput($data->genderLf);
            $kindOfGamerLf = $this->validateInput($data->kindOfGamerLf);
            $main1Lf = $this->validateInput($data->main1Lf);
            $main2Lf = $this->validateInput($data->main2Lf);
            $main3Lf = $this->validateInput($data->main3Lf);
            $rankLf = $this->validateInput($data->rankLf);
            $roleLf = $this->validateInput($data->roleLf);
    
            $this->setUserId($userId);
            $this->setUsername($username);
            $this->setGender($gender);
            $this->setAge($age);
            $this->setKindOfGamer($kindOfGamer);
            $this->setGame($game);
            $this->setShortBio($shortBio);
            $this->setLoLMain1($main1);
            $this->setLoLMain2($main2);
            $this->setLoLMain3($main3);
            $this->setLoLRank($rank);
            $this->setLoLRole($role);
            $this->setLoLServer($server);
            $this->setLfGender($genderLf);
            $this->setLfKindOfGamer($kindOfGamerLf);
            $this->setLoLMain1Lf($main1Lf);
            $this->setLoLMain2Lf($main2Lf);
            $this->setLoLMain3Lf($main3Lf);
            $this->setLoLRankLf($rankLf);
            $this->setLoLRoleLf($roleLf);

            if ($this->getAge() > 99)
            {
                $response = array('message' => 'Age is not valid');
                echo json_encode($response);
                exit;
            }

            $updateLeague = false;
            $createLoLUser = false;

            $updateUser = $this->user->updateUser(
                $this->getGender(),
                $this->getAge(),
                $this->getKindOfGamer(),
                $this->getShortBio(),
                $this->getGame(),
                $this->getUserId());

            if (!empty($user['lol_id'])) {
                $updateLeague = $this->leagueoflegends->updateLeagueData(
                    $this->getUserId(), 
                    $this->getLoLMain1(), 
                    $this->getLoLMain2(), 
                    $this->getLoLMain3(), 
                    $this->getLoLRank(), 
                    $this->getLoLRole(), 
                    $this->getLoLServer());
            } else {
                $createLoLUser = $this->leagueoflegends->createLoLUser(
                    $this->getUserId(), 
                    $this->getLoLMain1(), 
                    $this->getLoLMain2(), 
                    $this->getLoLMain3(), 
                    $this->getLoLRank(), 
                    $this->getLoLRole(), 
                    $this->getLoLServer());
            }

            $updateLookingFor = $this->userlookingfor->updateLookingForData(
                $this->getLfGender(),
                $this->getLfKindOfGamer(), 
                $this->getGame(),            
                $this->getLoLMain1Lf(), 
                $this->getLoLMain2Lf(), 
                $this->getLoLMain3Lf(), 
                $this->getLoLRankLf(), 
                $this->getLoLRoleLf(),
                $this->getUserId());

                if(($updateLeague || $createLoLUser) && $updateUser && $updateLookingFor)
                {
                    $response = array('message' => 'Success');
                }
                else
                {
                    $response = array('message' => 'Could not update');
                }
            } 
            else
            {
                            // Validate and set user data
            $userId = $this->validateInput($data->userId);
            $username = $this->validateInput($data->username);
            $gender = $this->validateInput($data->gender);
            $age = $this->validateInput($data->age);
            $kindOfGamer = $this->validateInput($data->kindOfGamer);
            $game = $this->validateInput($data->game);
            $shortBio = $this->validateInput($data->shortBio);
            $main1 = $this->validateInput($data->main1);
            $main2 = $this->validateInput($data->main2);
            $main3 = $this->validateInput($data->main3);
            $rank = $this->validateInput($data->rank);
            $role = $this->validateInput($data->role);
            $server = $this->validateInput($data->server);
            $genderLf = $this->validateInput($data->genderLf);
            $kindOfGamerLf = $this->validateInput($data->kindOfGamerLf);
            $main1Lf = $this->validateInput($data->main1Lf);
            $main2Lf = $this->validateInput($data->main2Lf);
            $main3Lf = $this->validateInput($data->main3Lf);
            $rankLf = $this->validateInput($data->rankLf);
            $roleLf = $this->validateInput($data->roleLf);
    
            $this->setUserId($userId);
            $this->setUsername($username);
            $this->setGender($gender);
            $this->setAge($age);
            $this->setKindOfGamer($kindOfGamer);
            $this->setGame($game);
            $this->setShortBio($shortBio);
            $this->setValorantMain1($main1);
            $this->setValorantMain2($main2);
            $this->setValorantMain3($main3);
            $this->setValorantRank($rank);
            $this->setValorantRole($role);
            $this->setValorantServer($server);
            $this->setLfGender($genderLf);
            $this->setLfKindOfGamer($kindOfGamerLf);
            $this->setValorantMain1Lf($main1Lf);
            $this->setValorantMain2Lf($main2Lf);
            $this->setValorantMain3Lf($main3Lf);
            $this->setValorantRankLf($rankLf);
            $this->setValorantRoleLf($roleLf);

            if ($this->getAge() > 99)
            {
                $response = array('message' => 'Age is not valid');
                echo json_encode($response);
                exit;
            }

            $updateValorant = false;
            $createValorantUser = false;


            $updateUser = $this->user->updateUser(
                $this->getGender(),
                $this->getAge(),
                $this->getKindOfGamer(),
                $this->getShortBio(),
                $this->getGame(),
                $this->getUserId());

                if (!empty($user['valorant_id'])) {
                    $updateValorant = $this->valorant->updateValorantData(
                        $this->getUserId(), 
                        $this->getValorantMain1(), 
                        $this->getValorantMain2(), 
                        $this->getValorantMain3(), 
                        $this->getValorantRank(), 
                        $this->getValorantRole(), 
                        $this->getValorantServer());
                } else {
                    $createValorantUser = $this->valorant->createValorantUser(
                        $this->getUserId(), 
                        $this->getValorantMain1(), 
                        $this->getValorantMain2(), 
                        $this->getValorantMain3(), 
                        $this->getValorantRank(), 
                        $this->getValorantRole(), 
                        $this->getValorantServer()); 
                }

            $updateLookingFor = $this->userlookingfor->updateLookingForDataValorant(
                $this->getLfGender(),
                $this->getLfKindOfGamer(),
                $this->getGame(),             
                $this->getValorantMain1Lf(), 
                $this->getValorantMain2Lf(), 
                $this->getValorantMain3Lf(), 
                $this->getValorantRankLf(), 
                $this->getValorantRoleLf(),
                $this->getUserId());

                if(($updateValorant || $createValorantUser) && $updateUser && $updateLookingFor)
                {
                    $response = array('message' => 'Success');
                }
                else
                {
                    $response = array('message' => 'Could not update');
                }
            }

        }
    
        // Return the response in JSON format
        echo json_encode($response);
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

    public function updatePicturePhone() {
        $targetDir = "public/upload/";
    
        // Validate if the file is received
        if (isset($_FILES["picture"]) && !empty($_FILES["picture"]["name"])) {
            $fileName = basename($_FILES["picture"]["name"]);
            $this->setFileName($fileName);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
            // Ensure the file type is allowed
            $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
    
            if (in_array($fileType, $allowTypes)) {
                // Attempt to move the uploaded file to the target directory
                if (move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFilePath)) {
                    // Check for animated GIFs
                    if ($fileType === 'gif' && $this->isAnimatedGif($targetFilePath)) {
                        echo json_encode(['message' => 'Animated GIFs are not allowed']);
                        exit;
                    }
    
                    // Resize the image to 200x200
                    $resizedFilePath = $targetDir . 'resized_' . $fileName;
                    if ($this->resizeImage($targetFilePath, $resizedFilePath, 200, 200)) {
                        // Retrieve userId from POST data
                        $username = isset($_POST["username"]) ? $this->validateInput($_POST["username"]) : null;
    
                        if ($username) {
                            // Update the database with the resized image
                            $uploadPicture = $this->user->uploadPicture($username, 'resized_' . $this->getFilename());
    
                            if ($uploadPicture) {
                                echo json_encode(['message' => 'Success']);
                            } else {
                                echo json_encode(['message' => 'Database update failed']);
                            }
                        } else {
                            echo json_encode(['message' => 'User ID is missing']);
                        }
                    } else {
                        echo json_encode(['message' => 'Error resizing image']);
                    }
                } else {
                    echo json_encode(['message' => 'Error uploading file']);
                }
            } else {
                echo json_encode(['message' => 'Invalid file type']);
            }
        } else {
            echo json_encode(['message' => 'No file uploaded']);
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
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
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
            $usersAfterMatching = $this->matchingscore->getMatchingScore($userId);
            // $userFriendRequest = $this->friendrequest->skipUserSwipping($_SESSION['userId']); Fonction already done in previous one
            
            $data = ['success' => false, 'error' => 'No matching users found.', 'matching' => $usersAfterMatching];
            if ($usersAfterMatching) {
                foreach ($usersAfterMatching as $match) {
                    $matchedUserId = $match['match_userMatched'];
                    // if (!in_array($matchedUserId, $userFriendRequest)) {
                        $userMatched = $this->user->getUserById($matchedUserId);               
                        if ($userMatched && $userMatched['user_game'] === $user['user_game']) {
                            if ($userMatched['user_game'] == "League of Legends") {
                                $data = [
                                    'success' => true,
                                    'user' => [
                                        'error' => 'No error',
                                        'user_id' => $userMatched['user_id'],
                                        'user_username' => $userMatched['user_username'],
                                        'user_picture' => $userMatched['user_picture'],
                                        'user_age' => $userMatched['user_age'],
                                        'user_game' => $userMatched['user_game'],
                                        'user_gender' => $userMatched['user_gender'],
                                        'user_kindOfGamer' => $userMatched['user_kindOfGamer'],
                                        'user_shortBio' => $userMatched['user_shortBio'],
                                        'user_isVip' => $userMatched['user_isVip'],
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
                                        'lol_sUsername' => $userMatched['lol_sUsername'],
                                        'lol_server' => $userMatched['lol_server'],
                                    ]
                                ];
                                break;
                            } else {
                                $data = [
                                    'success' => true,
                                    'user' => [
                                        'error' => 'No error',
                                        'user_id' => $userMatched['user_id'],
                                        'user_username' => $userMatched['user_username'],
                                        'user_picture' => $userMatched['user_picture'],
                                        'user_age' => $userMatched['user_age'],
                                        'user_game' => $userMatched['user_game'],
                                        'user_gender' => $userMatched['user_gender'],
                                        'user_kindOfGamer' => $userMatched['user_kindOfGamer'],
                                        'user_shortBio' => $userMatched['user_shortBio'],
                                        'user_isVip' => $userMatched['user_isVip'],
                                        'valorant_main1' => $userMatched['valorant_main1'],
                                        'valorant_main2' => $userMatched['valorant_main2'],
                                        'valorant_main3' => $userMatched['valorant_main3'],
                                        'valorant_rank' => $userMatched['valorant_rank'],
                                        'valorant_role' => $userMatched['valorant_role'],
                                        'valorant_account' => $userMatched['valorant_account'],
                                    ]
                                ];
                                break;
                            }
                        } else {
                            $data = ['success' => false, 'error' => 'No matching users found..', 'matching2' => $usersAfterMatching];
                        }
                    // }
                }
                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No matching users found...', 'matching3' => $usersAfterMatching]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }
    

    public function pageUserProfile()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {
            // Get important datas
            require_once 'keys.php';
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersAll = $this-> user -> getAllUsers();
            $unreadCounts = $this-> chatmessage -> countMessage($_SESSION['userId']);
            if ($user['user_game'] == "League of Legends")
            {
                $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            }
            else 
            {
                $valorantUser = $this->valorant->getValorantUserByValorantId($_SESSION['valorant_id']);
            }
            $ownedItems = $this->items->getOwnedItems($_SESSION['userId']);
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

        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
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
            if ($anotherUser['user_game'] == "League of Legends")
            {
                $lolUser = $this->leagueoflegends->getLeageUserByUserId($anotherUser['user_id']);
            }
            else 
            {
                $valorantUser = $this->valorant->getValorantUserByUserId($anotherUser['user_id']);
            }
            $ownedItems = $this->items->getOwnedItems($anotherUser['user_id']);
            $current_url = "https://ur-sg.com/anotherUser";
            $template = "views/swiping/swiping_profile_other";
            $page_title = "URSG - Profile " . $username;
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            $username = $_GET['username'];
            $anotherUser = $this-> user -> getUserByUsername($username);
            if ($anotherUser['user_game'] == "League of Legends")
            {
                $lolUser = $this->leagueoflegends->getLeageUserByUserId($anotherUser['user_id']);
            }
            else 
            {
                $valorantUser = $this->valorant->getValorantUserByUserId($anotherUser['user_id']);
            }
            $ownedItems = $this->items->getOwnedItems($anotherUser['user_id']);
            $current_url = "https://ur-sg.com/anotherUser";
            $template = "views/swiping/swiping_profile_other";
            $page_title = "URSG - Profile " . $username;
            require "views/layoutSwiping_noheader.phtml";
        }
    }

    public function pageUpdateProfile()
    {

        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
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

    public function pageSettings()
    {

        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {

            // Get important datas
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $allUsers = $this-> user -> getAllUsers();

            $current_url = "https://ur-sg.com/settings";
            $template = "views/swiping/settings";
            $page_title = "URSG - Settings";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function chatFilterSwitch()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            
            $userId = $data->userId;
            $status = $data->status;


                $updateFilter = $this->user->updateFilter($status, $userId);

                if ($updateFilter) {
                    $response = array('message' => 'Success');
                    echo json_encode($response);
                    exit;
                } else {
                    $response = array('message' => 'Couldnt update status');
                    echo json_encode($response);
                    exit;
                }
        }
    }

    public function getCurrency() {
        $response = array('message' => 'Error');
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

            $currencyData = $this->user->getCurrencyByUserId($this->getUserId());
            if ($currencyData) {
                $response = array('message' => 'Success', 'currency' => $currencyData);
            } else {
                $response = array('message' => 'Could not get currency');
            }
        } else {
            $response = array('message' => 'Proper data not sent');
        }
        echo json_encode($response);
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

    public function emptyInputSignupUpdate($age, $short_bio) 
    {
        $result;
        if (empty($age) || empty($short_bio))
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

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
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

    public function getLoLMain1Lf()
    {
        return $this->loLMain1Lf;
    }

    public function setLoLMain1Lf($loLMain1Lf)
    {
        $this->loLMain1Lf = $loLMain1Lf;
    }

    public function getLoLMain2Lf()
    {
        return $this->loLMain2Lf;
    }

    public function setLoLMain2Lf($loLMain2Lf)
    {
        $this->loLMain2Lf = $loLMain2Lf;
    }

    public function getLoLMain3Lf()
    {
        return $this->loLMain3Lf;
    }

    public function setLoLMain3Lf($loLMain3Lf)
    {
        $this->loLMain3Lf = $loLMain3Lf;
    }

    public function getLoLRankLf()
    {
        return $this->loLRankLf;
    }

    public function setLoLRankLf($loLRankLf)
    {
        $this->loLRankLf = $loLRankLf;
    }


    public function getLoLRoleLf()
    {
        return $this->loLRoleLf;
    }

    public function setLoLRoleLf($loLRoleLf)
    {
        $this->loLRole = $loLRoleLf;
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

    public function getValorantServer()
    {
        return $this->valorantServer;
    }

    public function setValorantServer($valorantServer)
    {
        $this->valorantServer = $valorantServer;
    }

    public function getValorantAccount()
    {
        return $this->valorantAccount;
    }

    public function setValorantAccount($valorantAccount)
    {
        $this->valorantAccount = $valorantAccount;
    }

    public function getValorantMain1Lf()
    {
        return $this->valorantMain1Lf;
    }

    public function setValorantMain1Lf($valorantMain1Lf)
    {
        $this->valorantMain1Lf = $valorantMain1Lf;
    }

    public function getValorantMain2Lf()
    {
        return $this->valorantMain2Lf;
    }

    public function setValorantMain2Lf($valorantMain2Lf)
    {
        $this->valorantMain2Lf = $valorantMain2Lf;
    }

    public function getValorantMain3Lf()
    {
        return $this->valorantMain3Lf;
    }

    public function setValorantMain3Lf($valorantMain3Lf)
    {
        $this->valorantMain3Lf = $valorantMain3Lf;
    }

    public function getValorantRankLf()
    {
        return $this->valorantRankLf;
    }

    public function setValorantRankLf($valorantRankLf)
    {
        $this->valorantRankLf = $valorantRankLf;
    }


    public function getValorantRoleLf()
    {
        return $this->valorantRoleLf;
    }

    public function setValorantRoleLf($valorantRoleLf)
    {
        $this->valorantRoleLf = $valorantRoleLf;
    }
}