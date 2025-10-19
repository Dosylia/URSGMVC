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
use models\GoogleUser;
use models\Report;
use models\RatingGames;
use traits\SecurityController;
use traits\Translatable;

class UserController
{
    use SecurityController;
    use Translatable;

    private User $user;
    private FriendRequest $friendrequest;
    private ChatMessage $chatmessage;
    private LeagueOfLegends $leagueoflegends;
    private Valorant $valorant;
    private UserLookingFor $userlookingfor;    
    private MatchingScore $matchingscore;
    private Items $items;
    private GoogleUser $googleUser;
    private Report $report;
    private RatingGames $rating;
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
    private $bluesky;
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
    private $lfFilteredServer;
    
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
        $this -> googleUser = new GoogleUser();
        $this -> report = new Report();
        $this -> rating = new RatingGames();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
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

    public function getAllUsersPhone()
    {
        require 'keys.php';
        $response = array('message' => 'Error');
    
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        // Check if the provided token matches the valid token
        if ($token !== $validToken) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        // Check if the 'allUsers' POST parameter is set
        if (isset($_POST['allUsers'])) {
            $allUsers = $this->user->getAllUsers();
    
            if ($allUsers) {
                $response = array(
                    'allUsers' => $allUsers,
                    'message' => 'Success'
                );
    
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            } else {
                $response = array('message' => 'Could not get all users');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
    
        } else {
            $response = array('message' => 'Cannot access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    public function getLeaderboardUsers()
    {
        require 'keys.php';
        $response = array('message' => 'Error');
    
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        // Check if the provided token matches the valid token
        if ($token !== $validToken) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        // Check if the 'allUsers' POST parameter is set
        if (isset($_POST['allUsers'])) {
            $allUsers = $this->user->getLeaderboardUsers();
    
            if ($allUsers) {
                $response = array(
                    'allUsers' => $allUsers,
                    'message' => 'Success'
                );
    
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            } else {
                $response = array('message' => 'Could not get all users');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
    
        } else {
            $response = array('message' => 'Cannot access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }        
    }

    public function pageLeaderboard()
    {
        $this->requireUserSessionOrRedirect($redirectUrl = '/');
        // Get important datas
        $this->initializeLanguage();
        $user = $this-> user -> getUserById($_SESSION['userId']);
        $userRank = $this->user->getUserRank($_SESSION['userId']);
        $allUsers = $this-> user -> getTopUsers();

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
        $page_css = ['store_leaderboard'];
        $current_url = "https://ur-sg.com/leaderboard";
        $template = "views/swiping/leaderboard";
        $page_title = "URSG - Leaderboard";
        $picture = "ursg-preview-small";
        require "views/layoutSwiping.phtml";
    }

    public function createAccountSkipPreferences()
    {
        if (!isset($_POST['param'])) {
            echo json_encode(['success' => false, 'message' => 'Missing parameter']);
            return;
        }

        $data = json_decode($_POST['param']);
        if (!isset($data->googleId)) {
            echo json_encode(['success' => false, 'message' => 'Google ID is required']);
            return;
        }

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        $authToken = $_COOKIE['auth_token'] ?? null;

        if (!$authToken || $token !== $authToken) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized token']);
            return;
        }

        if (!isset($data->username, $data->gender, $data->age, $data->kindOfGamer, $data->game, $data->shortBio)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

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
        $shortBio = $this->validateInput($data->shortBio);
        $this->setShortBio($shortBio);

        if ($this->emptyInputSignup($username, $age, $shortBio)) {
            echo json_encode(['success' => false, 'message' => 'Inputs cannot be empty']);
            return;
        }

        if ($this->user->getUserByUsername($username)) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            return;
        }

        if ($this->invalidUid($username)) {
            echo json_encode(['success' => false, 'message' => 'Username is not valid']);
            return;
        }

        if ($age < 13 || $age > 99) {
            echo json_encode(['success' => false, 'message' => 'Age must be between 13 and 99']);
            return;
        }

        if (strlen($shortBio) > 200) {
            echo json_encode(['success' => false, 'message' => 'Short bio exceeds 200 characters']);
            return;
        }

        $createUser = $this->user->createUser($googleUserId, $username, $gender, $age, $kindofgamer, $shortBio, $game);

        if (!$createUser) {
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            return;
        }

        $user = $this->user->getUserByUsername($username);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User was created but cannot be fetched']);
            return;
        }

        if (session_status() == PHP_SESSION_NONE) {
            $lifetime = 7 * 24 * 60 * 60;
            session_set_cookie_params($lifetime);
            session_start();
        }

        $_SESSION['userId'] = $user['user_id'];
        $_SESSION['username'] = $user['user_username'];

        // Game-specific setup
        $statusChampion = 1;
        $statusChampionLf = 1;
        $main1 = $main2 = $main3 = "";
        $main1Lf = $main2Lf = $main3Lf = "";
        $rank = $role = $server = "Unknown";
        $genderLf = $rankLf = $roleLf = "Any";
        $kindOfGamerLf = "Competition and Chill";

        if ($user['user_game'] === "League of Legends") {
            $createGameAccount = $this->leagueoflegends->createLoLUser($user['user_id'], $main1, $main2, $main3, $rank, $role, $server, $statusChampion);
            $createGameAccountLf = $this->userlookingfor->createLookingForUser($user['user_id'], $genderLf, $kindOfGamerLf, $user['user_game'], $main1Lf, $main2Lf, $main3Lf, $rankLf, $roleLf, $statusChampionLf);

            if (!$createGameAccount || !$createGameAccountLf) {
                echo json_encode(['success' => false, 'message' => 'Could not create League of Legends account or preferences']);
                return;
            }

            $lolUser = $this->leagueoflegends->getLeageAccountByLeagueId($createGameAccount);
            $lolLookingFor = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

            if (!$lolUser || !$lolLookingFor) {
                echo json_encode(['success' => false, 'message' => 'Could not retrieve League of Legends account or preferences']);
                return;
            }

            $_SESSION['lol_id'] = $lolUser['lol_id'];
            $_SESSION['lf_id'] = $lolLookingFor['lf_id'];
            echo json_encode(['success' => true]);
            return;
        }

        if ($user['user_game'] === "Valorant") {
            $createGameAccount = $this->valorant->createValorantUser($user['user_id'], $main1, $main2, $main3, $rank, $role, $server, $statusChampion);
            $createGameAccountLf = $this->userlookingfor->createLookingForUserValorant($user['user_id'], $genderLf, $kindOfGamerLf, $user['user_game'], $main1Lf, $main2Lf, $main3Lf, $rankLf, $roleLf, $statusChampionLf);

            if (!$createGameAccount || !$createGameAccountLf) {
                echo json_encode(['success' => false, 'message' => 'Could not create Valorant account or preferences']);
                return;
            }

            $valorantUser = $this->valorant->getValorantAccountByValorantId($createGameAccount);
            $valorantLookingFor = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);

            if (!$valorantUser || !$valorantLookingFor) {
                echo json_encode(['success' => false, 'message' => 'Could not retrieve Valorant account or preferences']);
                return;
            }

            $_SESSION['valorant_id'] = $valorantUser['valorant_id'];
            $_SESSION['lf_id'] = $valorantLookingFor['lf_id'];
            echo json_encode(['success' => true]);
            return;
        }

        // Fallback catch if game is unknown
        echo json_encode(['success' => false, 'message' => 'Unsupported game']);
        return;
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

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenGoogleUserId($token, $googleUserId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

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

            if ($age < 13 || $age > 99) {
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

            // if ($_SESSION['google_id'] != $this->getGoogleUserId())
            // {
            //     header("location:/signup?message=Unauthorized");
            //     exit();
            // }

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

            if ($age < 13 || $age > 99)
            {
                header("location:/signup?message=Age is not valid");
                exit();
            }

            if (strlen($this->getShortBio()) > 200) {
                header("location:/signup?message=Short bio is too long");
                exit();
            }

            if ($this->isUsernameForbidden($this->getUsername())) { 
                header("location:/signup?message=Username is forbidden");
                exit();
            }       

            $createUser = $this->user->createUser($this->getGoogleUserId(), $this->getUsername(), $this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame());

            if($createUser)
            {
                if (!empty($_POST['friendsUsername'])) {
                    $friendUsername = $_POST['friendsUsername'];
                    $friend = $this->user->getUserByUsername($friendUsername);
                    $amount = 1000;

                    if ($friend['user_friendsInvited'] < 6) {
                        $this->user->updateFriendsInvited($friend['user_id']);
                        $addCurrency = $this->user->addCurrency($friend['user_id'], $amount);
                    }
                }
                
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
            $bluesky = $this->validateInput($_POST["bluesky"]);
            $this->setBluesky($bluesky);

            $user = $this->googleUser->getUserByEmail($_SESSION['email']);

            if ($user['user_username'] != $this->getUsername())
            {
                header("location:/userProfile?message=Could not update");
                exit();
            }

            $updateSocial = $this->user->updateSocial2($this->getUsername(),  $this->getDiscord(), $this->getTwitter(), $this->getInstagram(), $this->getTwitch(), $this->getBluesky());


            if ($updateSocial)
            {
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

    public function updateSocialsWebsite()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['param']))
        {
            $data = json_decode($_POST['param']);

            if (isset($data->discord) && isset($data->userId) && isset($data->twitter) && isset($data->instagram) && isset($data->twitch) && isset($data->bluesky))
            {
                $userId = $this->validateInput($data->userId);
                $this->setUserId($userId);
                $discord = $this->validateInput($data->discord);
                $this->setDiscord($discord);
                $twitter = $this->validateInput($data->twitter);
                $this->setTwitter($twitter);
                $instagram = $this->validateInput($data->instagram);
                $this->setInstagram($instagram);
                $twitch = $this->validateInput($data->twitch);
                $this->setTwitch($twitch);
                $bluesky = $this->validateInput($data->bluesky);
                $this->setBluesky($bluesky);

                $user = $this->user->getUserById($this->getUserId());

                $token = $this->getBearerTokenOrJsonError();
                if (!$token) {
                    return;
                }
            
                // Validate Token for User
                if (!$this->validateTokenWebsite($token, $this->getUserId())) {
                    echo json_encode(['success' => false, 'message' => 'Invalid token']);
                    return;
                }

                $updateSocial = $this->user->updateSocial2($user['user_username'],  $this->getDiscord(), $this->getTwitter(), $this->getInstagram(), $this->getTwitch(), $this->getBluesky());


                if ($updateSocial)
                {
                    $response = array('message' => 'Success');
                    echo json_encode($response);
                    exit();  
                }
                else
                {
                    $response = array('message' => 'Token not valid');
                    echo json_encode($response);
                    exit();
                }
            } else {
                $response = array('message' => 'Could not update user');
                echo json_encode($response);
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
            $user = $this->user->getUserByUsername($this->getUsername());

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
 
             if (!$this->validateToken($token, $user['user_id'])) {
                 echo json_encode(['success' => false, 'error' => 'Invalid token']);
                 return;
             }

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

    public function personalityTestPage()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {

            // Get important datas
            $this->initializeLanguage();
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $page_css = ['personalitytest'];
            $current_url = "https://ur-sg.com/personalityTest";
            $template = "views/swiping/personality_test";
            $page_title = "URSG - What kind of League player";
            $picture = "ursg-quiz";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            $this->initializeLanguage();
            $current_url = "https://ur-sg.com/personalityTest";
            $template = "views/swiping/personality_test";
            $page_title = "URSG - What kind of League player";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping_noheader.phtml";
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

            $user = $this->user->getUserById($_SESSION['userId']);

            if ($user['user_id'] != $this->getUserId())
            {
                header("location:/userProfile?message=Could not update");
                exit();
            }

            if (isset($_POST['username']) && $user['user_isBoost'] == 1)
            {
                $username = $this->validateInput($_POST["username"]);
                $this->setUsername($username);
            } else {
                $this->setUsername($user['user_username']);
            }

            if ($this->emptyInputSignupUpdate($this->getAge(), $this->getShortBio()) !== false) {
                header("location:/signup?message=Inputs cannot be empty");
                exit();
            }

            if ($this->invalidUid($this->getUsername()) !== false) {
                header("location:/signup?message=Username is not valid");
                exit();
            }

            if ($age < 13 || $age > 99)
            {
                header("location:/userProfile?message=Age is not valid");
                exit();
            }

            if (!empty($userId))
            {
                $user = $this->user->getUserById($this->getUserId());
            }

            $updateUser = $this->user->updateUser($this->getUsername(), $this->getGender(), $this->getAge(), $this->getKindOfGamer(), $this->getShortBio(), $this->getGame(), $this->getUserId());


            if ($updateUser)
            {
                if (isset($_POST['username']) && $user['user_isBoost'] == 1)
                {
                    $currentMonth = date('Y-m');
                    if ($user['user_usernameChangeMonth'] !== $currentMonth) {
                        $this->user->setUsernameChanges($this->getUserId(), 1);
                        $this->user->setUsernameChangeMonth($this->getUserId(), $currentMonth);
                    } else {
                        $newNumber = $user['user_numberChangedUsername'] + 1;
                        $this->user->setUsernameChanges($this->getUserId(), $newNumber);
                    }
                }

                if ($user['user_game'] !== $this->getGame())
                {
                    if ($user['user_game'] == "League of Legends")
                    {
                        unset($_SESSION['lol_id']);
                        unset($_SESSION['lf_id']);
                        if ($user['valorant_id']) {
                            $_SESSION['valorant_id'] = $user['valorant_id'];

                            if($user['lf_valrole'] !== NULL)
                            {
                                $_SESSION['lf_id'] = $user['lf_id']; 
                                header("location:/userProfile?message=Updated successfully");
                                exit();  
                            } else {
                                header("location:/updateLookingForGame?message=Updated successfully");
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

                            if($user['lf_lolrole'] !== NULL)
                            {
                                $_SESSION['lf_id'] = $user['lf_id']; 
                                header("location:/userProfile?message=Updated successfully");
                                exit();  
                            } else {
                                header("location:/updateLookingForGamePage?message=Updated successfully");
                                exit();  
                            }
                        } else {
                            header("location:/leagueuser?user_id=".$user['user_id']);
                            exit();  
                        }

                        header("location:/updateLookingForGamePage?message=Updated successfully");
                        exit();  
                    }
                }
                else 
                {
                    header("location:/userProfile?message=Updated successfully");
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

    public function addBonusPicture() 
    {
        $targetDir = "public/upload/";
        $originalFileName = basename($_FILES["file"]["name"]);
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        
        // Generate a unique file name
        $uniqueFileName = uniqid('img_', true) . '.' . $fileExtension;
        $this->setFileName($uniqueFileName);
        $targetFilePath = $targetDir . $uniqueFileName;
    
        if (!isset($_POST["submit"]) || empty($_FILES["file"]["name"])) {
            header("location:/userProfile?message=Nothing to upload");
            exit;
        }
    
        // Retrieve current user info
        $user = $this->user->getUserById($_SESSION['userId']);
        $username = $this->validateInput($_GET["username"]);
        $this->setUsername($username);
    
        if ($user['user_username'] !== $this->getUsername()) {
            header("location:/userProfile?message=Unauthorized");
            exit;
        }
    
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
    
        if (!in_array($fileExtension, $allowTypes)) {
            header("location:/userProfile?message=Wrong type of picture");
            exit;
        }
    
        try {
            // Move uploaded file
            if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                header("location:/userProfile?message=Error uploading file. Picture was probably too big");
                exit;
            }
    
            // Check for animated GIFs
            if ($fileExtension === 'gif' && $this->isAnimatedGif($targetFilePath)) {
                unlink($targetFilePath); // Delete the uploaded GIF immediately
                header("location:/userProfile?message=Animated Gifs are not allowed");
                exit;
            }
    
            // Resize image
            $resizedFilePath = $targetDir . 'resized_' . $uniqueFileName;
            if (!$this->resizeImage($targetFilePath, $resizedFilePath, 500, 500)) {
                unlink($targetFilePath); // Clean up original if resize fails
                header("location:/userProfile?message=Error resizing image");
                exit;
            }
    
            // Get current bonus pictures
            $bonusPictures = $this->user->getBonusPictures($this->getUsername());
    
            // Check if bonusPictures is an array
            if (!is_array($bonusPictures)) {
                $bonusPictures = [];
            }
    
            // Check if the number of pictures exceeds 10
            if (count($bonusPictures) >= 10) {
                unlink($targetFilePath); // Delete the uploaded file if limit exceeded
                header("location:/userProfile?message=You cannot upload more than 10 pictures.");
                exit;
            }
    
            // Add new picture to the list
            $bonusPictures[] = 'resized_' . $this->getFilename();
    
            // Update the bonus pictures in the database
            if (!$this->user->updateBonusPictures($this->getUsername(), $bonusPictures)) {
                header("location:/userProfile?message=Couldn't update profile picture");
                exit;
            }
    
            // Delete original uploaded file after resizing
            unlink($targetFilePath);
    
            header("location:/userProfile?message=Updated successfully");
        } catch (Exception $e) {
            header("location:/userProfile?message=" . urlencode($e->getMessage()));
        }
        exit;
    }
    

    public function deleteBonusPicture()
    {
        $response = array('message' => 'Error');

        if (isset($_POST['fileName']) && isset($_POST['userId'])) 
        {
            $userId = $this->validateInput($_POST['userId']);
            $this->setUserId($userId);
            $filename = $this->validateInput($_POST['fileName']);
            $this->setFileName($filename);

            $user = $this->user->getUserById($this->getUserId());

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
        
            if (!isset($_POST['userId'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid request']);
                return;
            }
        
            $userId = (int)$_POST['userId'];
        
            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }


            $bonusPictures = $this->user->getBonusPictures($user['user_username']);

            if (!is_array($bonusPictures)) {
                $bonusPictures = [];
            }

            if (!in_array($this->getFileName(), $bonusPictures)) {
                echo json_encode(['success' => false, 'error' => 'Picture not found in user\'s collection']);
                return;
            }

            $key = array_search($this->getFileName(), $bonusPictures);

            if ($key !== false) {
                unset($bonusPictures[$key]);
                $bonusPictures = array_values($bonusPictures);
            }

            if (!$this->user->updateBonusPictures($user['user_username'], $bonusPictures)) {
                $response = array('message' => 'Could not delete picture');
                echo json_encode($response);
                exit;
            }

            $filePath = "public/upload/" . $this->getFileName();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $response = array('message' => 'Success');
            echo json_encode($response);
            exit;
        } else {
            $response = array('message' => 'Could not delete picture');
            echo json_encode($response);
            exit;
        }
    }

    public function deleteBonusPicturePhone()
    {
        $response = array('message' => 'Error');

        if (isset($_POST['fileName']) && isset($_POST['userId'])) 
        {
            $userId = $this->validateInput($_POST['userId']);
            $this->setUserId($userId);
            $filename = $this->validateInput($_POST['fileName']);
            $this->setFileName($filename);

            $user = $this->user->getUserById($this->getUserId());

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
        
            if (!isset($_POST['userId'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
                return;
            }
        
            $userId = (int)$_POST['userId'];
        
            // Validate Token for User
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }


            $bonusPictures = $this->user->getBonusPictures($user['user_username']);

            if (!is_array($bonusPictures)) {
                $bonusPictures = [];
            }

            if (!in_array($this->getFileName(), $bonusPictures)) {
                echo json_encode(['success' => false, 'message' => 'Picture not found in user\'s collection']);
                return;
            }

            $key = array_search($this->getFileName(), $bonusPictures);

            if ($key !== false) {
                unset($bonusPictures[$key]);
                $bonusPictures = array_values($bonusPictures);
            }

            if (!$this->user->updateBonusPictures($user['user_username'], $bonusPictures)) {
                $response = array('message' => 'Could not delete picture');
                echo json_encode($response);
                exit;
            }

            $filePath = "public/upload/" . $this->getFileName();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $response = array('message' => 'Success', 'bonusPictures' => $bonusPictures);
            echo json_encode($response);
            exit;
        } else {
            $response = array('message' => 'Could not delete picture, invalid data sent');
            echo json_encode($response);
            exit;
        }
    }

    public function getUserData()
    {
        // require 'keys.php';
        $response = array('message' => 'Error');
    
        if (isset($_POST['userId'])) {

            // $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
            // if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            //     return;
            // }
        
            // $token = $matches[1];
        
            // // Check if the provided token matches the valid token
            // if ($token !== $validToken) {
            //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            //     return;
            // }

            // Directly get the userId from POST data
            $userId = $this->validateInput($_POST['userId']); 
    
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
        require 'keys.php';
        $response = array('message' => 'Error');
    
        if (isset($_POST['userId']) && isset($_POST['token'])) {

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
        
            // Check if the provided token matches the valid token
            if ($token !== $validToken) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

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

            $userId = $this->validateInput($data->userId);

            
            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            if ($data->game == "League of Legends") 
            {

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
            $statusChampion = $this->validateInput($data->skipSelection);
            $statusChampionLf = $this->validateInput($data->skipSelectionLf);
            
            $validRegions = [
                "Europe West", "North America", "Europe Nordic & East", "Brazil", 
                "Latin America North", "Latin America South", "Oceania", 
                "Russia", "Turkey", "Japan", "Korea"
            ];

            $filteredServer = !empty($data->filteredServerLf) ? $this->validateInputJSON($data->filteredServerLf) : $validRegions;

            if (!empty($filteredServer)) {
                foreach ($filteredServer as $serverTest) {
                    if (!in_array($serverTest, $validRegions)) {
                        echo json_encode(['success' => false, 'error' => 'Filtered region not valid']);
                        return;
                    }
                }

            } else {
                $filteredServer = $validRegions;
            }

            $filteredServerJson = json_encode($filteredServer);
            $this->setLfFilteredServer($filteredServerJson);
    
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
                $this->getUsername(),
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
                    $this->getLoLServer(),
                    $statusChampion);
            } else {
                $createLoLUser = $this->leagueoflegends->createLoLUser(
                    $this->getUserId(), 
                    $this->getLoLMain1(), 
                    $this->getLoLMain2(), 
                    $this->getLoLMain3(), 
                    $this->getLoLRank(), 
                    $this->getLoLRole(), 
                    $this->getLoLServer(),
                    $statusChampion);
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
                $statusChampionLf,
                $this->getLfFilteredServer(),
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
            $statusChampion = $this->validateInput($data->skipSelection);
            $statusChampionLf = $this->validateInput($data->skipSelectionLf);

            $validRegions = [
                "Europe West", "North America", "Europe Nordic & East", "Brazil", 
                "Latin America North", "Latin America South", "Oceania", 
                "Russia", "Turkey", "Japan", "Korea"
            ];
            
            $filteredServer = !empty($data->filteredServerLf) ? $this->validateInputJSON($data->filteredServerLf) : $validRegions;

            if (!empty($filteredServer)) {
                foreach ($filteredServer as $serverTest) {
                    if (!in_array($serverTest, $validRegions)) {
                        echo json_encode(['success' => false, 'error' => 'Filtered region not valid']);
                        return;
                    }
                }

            } else {
                $filteredServer = $validRegions;
            }

            $filteredServerJson = json_encode($filteredServer);
            $this->setLfFilteredServer($filteredServerJson);
    
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
                $this->getUsername(),
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
                        $this->getValorantServer(),
                        $statusChampion);
                } else {
                    $createValorantUser = $this->valorant->createValorantUser(
                        $this->getUserId(), 
                        $this->getValorantMain1(), 
                        $this->getValorantMain2(), 
                        $this->getValorantMain3(), 
                        $this->getValorantRank(), 
                        $this->getValorantRole(), 
                        $this->getValorantServer(),
                        $statusChampion); 
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
                $statusChampionLf,
                $this->getLfFilteredServer(),
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
        $originalFileName = basename($_FILES["fileProfile"]["name"]);
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        
        // Generate a unique file name
        $uniqueFileName = uniqid('img_', true) . '.' . $fileExtension;
        $this->setFileName($uniqueFileName);
        $targetFilePath = $targetDir . $uniqueFileName;
    
        if (!isset($_POST["submit"]) || empty($_FILES["fileProfile"]["name"])) {
            header("location:/userProfile?message=Nothing to upload");
            exit;
        }
    
        // Retrieve current user info
        $user = $this->user->getUserById($_SESSION['userId']);
        $username = $this->validateInput($_GET["username"]);
        $this->setUsername($username);
    
        if ($user['user_username'] !== $this->getUsername()) {
            header("location:/userProfile?message=Unauthorized");
            exit;
        }
    
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
    
        if (!in_array($fileExtension, $allowTypes)) {
            header("location:/userProfile?message=Wrong type of picture");
            exit;
        }
    
        try {
            // Move uploaded file
            if (!move_uploaded_file($_FILES["fileProfile"]["tmp_name"], $targetFilePath)) {
                header("location:/userProfile?message=Error uploading file");
                exit;
            }
    
            // Check for animated GIFs, only allow if user_isBoost === 1 
            if ($fileExtension === 'gif' && $this->isAnimatedGif($targetFilePath) && $user['user_isBoost'] !== 1) {
                unlink($targetFilePath); // Delete the uploaded GIF immediately
                header("location:/userProfile?message=Animated GIFs are not allowed");
                exit;
            }
    
            // Resize image
            $resizedFilePath = $targetDir . 'resized_' . $uniqueFileName;
            if (!$this->resizeImage($targetFilePath, $resizedFilePath, 200, 200)) {
                unlink($targetFilePath); // Clean up original if resize fails
                header("location:/userProfile?message=Error resizing image");
                exit;
            }
    
            // Update database with resized image
            if (!$this->user->uploadPicture($this->getUsername(), 'resized_' . $this->getFilename())) {
                header("location:/userProfile?message=Couldn't update profile picture");
                exit;
            }
    
            // ✅ **Now delete old images only after everything succeeds**
            if (!empty($user['user_picture'])) {
                $oldPicture = $targetDir . $user['user_picture'];
                $oldResizedPicture = $targetDir . str_replace('resized_', '', $user['user_picture']);
    
                if (file_exists($oldPicture)) {
                    unlink($oldPicture);
                }
                if (file_exists($oldResizedPicture)) {
                    unlink($oldResizedPicture);
                }
            }
    
            // Delete original uploaded file after resizing
            unlink($targetFilePath);
    
            header("location:/userProfile?message=Updated successfully");
        } catch (Exception $e) {
            header("location:/userProfile?message=" . urlencode($e->getMessage()));
        }
        exit;
    }
    

    public function updatePicturePhone() {

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['username'])) {
            echo json_encode(['message' => 'Invalid request']);
            return;
        }

        $user = $this->user->getUserByUsername($_POST['username']);
    
        // Validate Token for User
        if (!$this->validateToken($token, $user['user_id'])) {
            echo json_encode(['message' => 'Invalid token']);
            return;
        }

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

                                if (!empty($user['user_picture'])) {
                                    $oldPicture = $targetDir . $user['user_picture'];
                                    $oldResizedPicture = $targetDir . str_replace('resized_', '', $user['user_picture']);
                        
                                    if (file_exists($oldPicture)) {
                                        unlink($oldPicture);
                                    }
                                    if (file_exists($oldResizedPicture)) {
                                        unlink($oldResizedPicture);
                                    }
                                }
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

    public function updateBonusPicturePhone() {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['username'])) {
            echo json_encode(['message' => 'Invalid request']);
            return;
        }
    
        $user = $this->user->getUserByUsername($_POST['username']);
    
        // Validate Token for User
        if (!$this->validateToken($token, $user['user_id'])) {
            echo json_encode(['message' => 'Invalid token']);
            return;
        }
    
        $targetDir = "public/upload/";
    
        // Validate if the file is received
        if (!isset($_FILES["picture"]) || empty($_FILES["picture"]["name"])) {
            echo json_encode(['message' => 'No file uploaded']);
            return;
        }
    
        $originalFileName = basename($_FILES["picture"]["name"]);
        $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
    
        // Generate a unique file name
        $uniqueFileName = uniqid('img_', true) . '.' . $fileExtension;
        $this->setFileName($uniqueFileName);
        $targetFilePath = $targetDir . $uniqueFileName;
    
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
    
        if (!in_array($fileExtension, $allowTypes)) {
            echo json_encode(['message' => 'Invalid file type']);
            return;
        }
    
        // Move uploaded file
        if (!move_uploaded_file($_FILES["picture"]["tmp_name"], $targetFilePath)) {
            echo json_encode(['message' => 'Error uploading file. Picture might be too big.']);
            return;
        }
    
        // Check for animated GIFs
        if ($fileExtension === 'gif' && $this->isAnimatedGif($targetFilePath)) {
            unlink($targetFilePath); // Delete the uploaded GIF immediately
            echo json_encode(['message' => 'Animated GIFs are not allowed']);
            return;
        }
    
        // Resize image
        $resizedFilePath = $targetDir . 'resized_' . $uniqueFileName;
        if (!$this->resizeImage($targetFilePath, $resizedFilePath, 500, 500)) {
            unlink($targetFilePath); // Clean up original if resize fails
            echo json_encode(['message' => 'Error resizing image']);
            return;
        }
    
        // Get current bonus pictures
        $bonusPictures = $this->user->getBonusPictures($_POST['username']);
    
        // Ensure it's an array
        if (!is_array($bonusPictures)) {
            $bonusPictures = [];
        }
    
        // Enforce max 10 bonus pictures
        if (count($bonusPictures) >= 10) {
            unlink($targetFilePath); // Delete new file if limit exceeded
            echo json_encode(['message' => 'You cannot upload more than 10 pictures.']);
            return;
        }
    
        // Add new picture to the list
        $bonusPictures[] = 'resized_' . $this->getFilename();
    
        // Update bonus pictures in the database
        if (!$this->user->updateBonusPictures($_POST['username'], $bonusPictures)) {
            echo json_encode(['message' => 'Database update failed']);
            return;
        }
    
        // Delete original uploaded file after resizing
        unlink($targetFilePath);
    
        echo json_encode(['message' => 'Success', 'bonusPictures' => $bonusPictures]);
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
        $this->requireUserSessionOrRedirect($redirectUrl = '/');
        // Get important datas
        $this->initializeLanguage();
        $user = $this-> user -> getUserById($_SESSION['userId']);
        $usersAll = $this-> user -> getAllUsersExceptFriends($_SESSION['userId']);
        $page_css = ['swiping'];
        $current_url = "https://ur-sg.com/swiping";
        $template = "views/swiping/swiping_main";
        $page_title = "URSG - Swiping";
        $picture = "ursg-preview-small";
        require "views/layoutSwiping.phtml";
    }

    public function updateNotificationPermission(): void
    {
        if (isset($_POST['userId'])) {
            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
    
            if (!isset($_POST['userId'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid request']);
                return;
            }
        
            $userId = (int)$_POST['userId'];
        
            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            $updatePermission = $this->user->updatePermission($_POST['userId']);

            if ($updatePermission) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Could not update preferences']);
            }
    
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    // PHP controller method to handle saving the subscription
    public function saveNotificationSubscription() 
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        // Step 2: Retrieve and validate POST parameters
        $param = $_POST['param'] ?? null;
        $userId = $_POST['userId'] ?? null;
    
        if (!$param || !$userId) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }
    
        // Step 3: Decode subscription data
        $subscriptionData = json_decode($param, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
            return;
        }
    
        // Step 4: Validate subscription structure
        if (!isset($subscriptionData['endpoint'], $subscriptionData['keys']['p256dh'], $subscriptionData['keys']['auth'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid subscription data']);
            return;
        }
    
        // Step 5: Extract subscription details
        $endpoint = $subscriptionData['endpoint'];
        $p256dh = $subscriptionData['keys']['p256dh'];
        $auth = $subscriptionData['keys']['auth'];
    
        // Step 6: Validate token
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token', 'userId' => $userId]);
            return;
        }
    
        // Step 7: Save the subscription
        $saveSubscription = $this->user->saveSubscription($userId, $endpoint, $p256dh, $auth);
    
        if ($saveSubscription) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save subscription']);
        }
    }

    public function fetchNotificationEndpoint()
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        // Step 2: Retrieve and validate userId
        $userId = $_POST['userId'] ?? null;
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Missing userId']);
            return;
        }
    
        // Step 3: Validate token
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        // Step 4: Fetch the subscription endpoint
        $subscriptionEndpoint = $this->user->fetchSubscriptionEndpoint($userId);
    
        if ($subscriptionEndpoint) {
            echo json_encode(['success' => true, 'endpoint' => $subscriptionEndpoint]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to fetch subscription endpoint']);
        }
    }

    public function markInactiveUsersOffline()
    {
        require_once 'keys.php';
    
        $tokenAdmin = $_GET['token'] ?? null;
    
        if (!isset($tokenAdmin) || $tokenAdmin !== $tokenRefresh) { 
            http_response_code(401); // Return Unauthorized for cron logs
            echo "❌ Unauthorized.\n";
            exit();
        } 

        echo "✅ Token is valid.\n";

        $this->user->markInactiveUsersOffline();
    }


    public function getUserMatching()
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $user = $this->user->getUserById($userId);

            // Determine which field to check for filtering
            $postServer = isset($_POST['server']) ? json_decode($_POST['server'], true) : [];
            $filteredServer = !empty($postServer) ? $postServer : json_decode($user['lf_filteredServer'], true);
    
            // Determine server column based on game
            $serverColumn = ($user['user_game'] == "League of Legends") ? "lol_server" : "valorant_server";
    
            // Define all servers if no filters
            $allServers = ["Europe West", "North America", "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia", "Turkey", "Japan", "Korea", "Unknown"];
            $serverList = empty($filteredServer) ? $allServers : $filteredServer;

            $postGender = isset($_POST['gender']) ? json_decode($_POST['gender'], true) : [];
            $filteredGender = !empty($postGender) ? $postGender : [];
            
            if (is_array($filteredGender)) {
                $genderConditions = [];
                foreach ($filteredGender as $gender) {
                    switch ($gender) {
                        case 'Male':
                            $genderConditions[] = 'Male';
                            break;
                        case 'Female':
                            $genderConditions[] = 'Female';
                            break;
                        case 'Trans Female':
                            $genderConditions[] = 'Trans Female';
                            $genderConditions[] = 'Trans';
                            break;
                        case 'Trans Male':
                            $genderConditions[] = 'Trans Male';
                            $genderConditions[] = 'Trans';
                            break;
                        case 'Non Binary':
                            $genderConditions[] = 'Non Binary';
                            $genderConditions[] = 'Non binary';
                            break;
                        default:
                            $genderConditions[] = 'All';
                            break;
                    }
                }
                // Remove duplicates and handle empty cases
                $genderConditions = array_unique($genderConditions);
                $genderConditions = !empty($genderConditions) ? $genderConditions : null;
            } else {
                // Handle single string value (if not using array)
                switch ($gender) {
                    case 'Male':
                        $genderConditions[] = 'Male';
                        break;
                    case 'Female':
                        $genderConditions[] = 'Female';
                        break;
                    case 'Trans Female':
                        $genderConditions[] = 'Trans Female';
                        break;
                    case 'Trans Male':
                        $genderConditions[] = 'Trans Male';
                        break;
                    case 'Non Binary':
                        $genderConditions[] = 'Non Binary';
                        $genderConditions[] = 'Non binary';
                        break;
                    case 'Non binary':
                        $genderConditions[] = 'Non Binary';
                        break;
                    default:
                        $genderConditions[] = 'All';
                        break;
                }
            } 
    
            $postGameMode = isset($_POST['gamemode']) ? json_decode($_POST['gamemode'], true) : [];
            $filteredGameMode = !empty($postGameMode) ? $postGameMode : [];
            
            if (is_array($filteredGameMode)) {
                $gameModeCondition = [];
                foreach ($filteredGameMode as $mode) {
                    if ($mode !== 'All') {
                        switch ($mode) {
                            case 'Aram':
                            case 'Normal Draft':
                                $gameModeCondition[] = 'Chill';
                                break;
                            case 'Ranked':
                                $gameModeCondition[] = 'Competition';
                                break;
                        }
                    }
                }
                // Remove duplicates and handle empty cases
                $gameModeCondition = array_unique($gameModeCondition);
                $gameModeCondition = !empty($gameModeCondition) ? $gameModeCondition : null;
            } else {
                // Handle single string value (if not using array)
                if ($filteredGameMode && $filteredGameMode !== 'All') {
                    switch ($filteredGameMode) {
                        case 'Aram':
                        case 'Normal Draft':
                            $gameModeCondition = 'Chill';
                            break;
                        case 'Ranked':
                            $gameModeCondition = 'Competition';
                            break;
                        default:
                            $gameModeCondition = "Competition and Chill";
                    }
                } else {
                    $gameModeCondition = "Competition and Chill";
                }
            }      
            
            // Fetch users with applied filters
            $usersAfterMatching = $this->user->getAllUsersExceptFriendsLimit(
                $userId,
                $user['user_game'],
                $serverList,
                $genderConditions,
                $gameModeCondition
            );
                
            $data = ['success' => false, 'error' => 'No matching users found.', 'matching' => $usersAfterMatching];
            if ($usersAfterMatching) {
                foreach ($usersAfterMatching as $match) {
                    $matchedUserId = $match['user_id'];
            
                    // Check if the matched user is not the current user
                    if ($matchedUserId != $userId) {
                        // if ($_SESSION['userId'] == 157) {
                        //     $matchedUserId = 157;
                        // }
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
                                        'user_bonusPicture' => $userMatched['user_bonusPicture'],
                                        'user_age' => $userMatched['user_age'],
                                        'user_game' => $userMatched['user_game'],
                                        'user_gender' => $userMatched['user_gender'],
                                        'user_kindOfGamer' => $userMatched['user_kindOfGamer'],
                                        'user_shortBio' => $userMatched['user_shortBio'],
                                        'user_isVip' => $userMatched['user_isVip'],
                                        'user_isPartner' => $userMatched['user_isPartner'],
                                        'user_isCertified' => $userMatched['user_isCertified'],
                                        'user_rating' => $userMatched['user_rating'],
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
                                        'lol_server' => $userMatched['lol_server'],
                                        'lol_noChamp' => $userMatched['lol_noChamp'],
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
                                        'user_isPartner' => $userMatched['user_isPartner'],
                                        'user_isCertified' => $userMatched['user_isCertified'],
                                        'valorant_main1' => $userMatched['valorant_main1'],
                                        'valorant_main2' => $userMatched['valorant_main2'],
                                        'valorant_main3' => $userMatched['valorant_main3'],
                                        'valorant_rank' => $userMatched['valorant_rank'],
                                        'valorant_role' => $userMatched['valorant_role'],
                                        'valorant_account' => $userMatched['valorant_account'],
                                        'valorant_server' => $userMatched['valorant_server'],
                                        'valorant_noChamp' => $userMatched['valorant_noChamp'],
                                    ]
                                ];
                                break;
                            }
                        } else {
                            $data = ['success' => false, 'error' => 'No matching users found..', 'matching2' => $usersAfterMatching];
                        }
                    }
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
            if (isset($_GET['username']))
            {
                if($_GET['username'] !== $_SESSION['username']) 
                {
                    $username = $_GET['username'];
                    header("Location: /anotherUser&username=" . $username);
                    exit();                    
                }
            }

            // Get important datas
            require_once 'keys.php';
            $this->initializeLanguage();
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $userRating = 0;
            $badges = $this->items->getBadges();
            $personalColor = '';
            $personalButtonDesign = "";
            $personalAddPicture = "";
            if ($user['user_personalColor']) {
                $personalColor = $user['user_personalColor'];
                $personalButtonDesign = "style='background-color: " . htmlspecialchars($personalColor) . "; border-color: " . htmlspecialchars($personalColor) . ";'";
                $personalAddPicture = "style='color: " . htmlspecialchars($personalColor) . ";'";
            }
            if ($user['user_isBoost']) {
                $colors = ['#4A90E2', '#50E3C2', '#9013FE', '#F5A623', '#7ED321', '#D0021B', '#F8E71C'];
            }
            if ($user['user_game'] == "League of Legends")
            {
                $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
                if ($lolUser['lol_verified'] == 1) {
                    $userRating = $this->rating->getAverageRatingForUser($user['user_id']);
                }
            }
            else 
            {
                $valorantUser = $this->valorant->getValorantUserByValorantId($_SESSION['valorant_id']);
            }
            $ownedItems = $this->items->getOwnedItems($_SESSION['userId']);
            $additionalBadges = array_filter(is_array($ownedItems) ? $ownedItems : [], 
                    function($item) {
                        return $item['items_category'] === 'badge' && $item['userItems_isUsed'] == 1;
            });
            $additionalBadges = array_slice($additionalBadges, 0, 3);

            $activeBanner = array_filter(is_array($ownedItems) ? $ownedItems : [], 
                    function($item) {
                        return $item['items_category'] === 'Banner' && $item['userItems_isUsed'] == 1;
            });
            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
            $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $maxStars = 5;
            $fullStars = intval($userRating);
            $emptyStars = $maxStars - $fullStars;
            $page_css = ['profile'];
            $current_url = "https://ur-sg.com/userProfile";
            $template = "views/swiping/swiping_profile";
            $page_title = "URSG - Profile";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            if (isset($_GET['username']))
            {
                $username = $_GET['username'];
                header("Location: /anotherUser&username=" . $username);
                exit();                    
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
            if (isset($_GET['username'])) 
            {
                $this->initializeLanguage();
                $username = $_GET['username'];
                if ($_GET['username'] === $_SESSION['username'])
                {
                    header("Location: /userProfile");
                    exit();
                }
                $user = $this-> user -> getUserById($_SESSION['userId']);
                $anotherUser = $this-> user -> getUserByUsername($username);
                $userRating = 0;
                $badges = $this->items->getBadges();
                $personalColor = '';
                if ($anotherUser) 
                {
                    if ($anotherUser['user_personalColor']) {
                        $personalColor = $anotherUser['user_personalColor'];
                    }
                    $lfUser = $this->userlookingfor->getLookingForUserByUserId($anotherUser['user_id']);
                    if ($anotherUser['user_game'] == "League of Legends")
                    {
                        $lolUser = $this->leagueoflegends->getLeageUserByUserId($anotherUser['user_id']);
                        if ($lolUser['lol_verified'] == 1) {
                            $userRating = $this->rating->getAverageRatingForUser($anotherUser['user_id']);
                        }
                    }
                    else 
                    {
                        $valorantUser = $this->valorant->getValorantUserByUserId($anotherUser['user_id']);
                    }

                    if (isset($user) && $user != null) {
                        $checkIfFriend = $this->friendrequest->checkIfFriend($user['user_id'], $anotherUser['user_id']);
                    }
                    $maxStars = 5;
                    $fullStars = intval($userRating);
                    $emptyStars = $maxStars - $fullStars;
                    $ownedItems = $this->items->getOwnedItems($anotherUser['user_id']);
                    $additionalBadges = array_filter(
                        is_array($ownedItems) ? $ownedItems : [],
                        function($item) {
                            return $item['items_category'] === 'badge' && $item['userItems_isUsed'] == 1;
                        }
                    );
                    $additionalBadges = array_slice($additionalBadges, 0, 3);
                    $activeBanner = array_filter(is_array($ownedItems) ? $ownedItems : [], 
                    function($item) {
                        return $item['items_category'] === 'Banner' && $item['userItems_isUsed'] == 1;
                    });
                    $page_css = ['tools/offline_modal', 'profile'];
                    $current_url = "https://ur-sg.com/anotherUser";
                    $template = "views/swiping/swiping_profile_other";
                    $page_title = "URSG - Profile " . $username;
                    $picture = "ursg-preview-small";
                    require "views/layoutSwiping.phtml";
                } else {
                    header("Location: /userProfile?message=No user found");
                    exit();
                }
            }
            else 
            {
                header("Location: /userProfile?message=No user found");
                exit();
            }
        } 
        else
        {
            if (isset($_GET['username']))
            {
                $this->initializeLanguage();
                $username = $_GET['username'];
                $anotherUser = $this-> user -> getUserByUsername($username);
                $lfUser = $this->userlookingfor->getLookingForUserByUserId($anotherUser['user_id']);
                $userRating = 0;
                $badges = $this->items->getBadges();
                $personalColor = '';
                if ($anotherUser)
                {
                    if ($anotherUser['user_personalColor']) {
                        $personalColor = $anotherUser['user_personalColor'];
                    }
                    if ($anotherUser['user_game'] == "League of Legends")
                    {
                        $lolUser = $this->leagueoflegends->getLeageUserByUserId($anotherUser['user_id']);
                        if ($lolUser['lol_verified'] == 1) {
                            $userRating = $this->rating->getAverageRatingForUser($anotherUser['user_id']);
                        }
                    }
                    else 
                    {
                        $valorantUser = $this->valorant->getValorantUserByUserId($anotherUser['user_id']);
                    }
                    $maxStars = 5;
                    $fullStars = intval($userRating);
                    $emptyStars = $maxStars - $fullStars;
                    $ownedItems = $this->items->getOwnedItems($anotherUser['user_id']);
                    $additionalBadges = array_filter($ownedItems, function($item) {
                        return $item['items_category'] === 'badge' && $item['userItems_isUsed'] == 1;
                    });
                    $additionalBadges = array_slice($additionalBadges, 0, 3);
                    $page_css = ['tools/offline_modal', 'profile'];
                    $current_url = "https://ur-sg.com/anotherUser";
                    $template = "views/swiping/swiping_profile_other";
                    $page_title = "URSG - Profile " . $username;
                    $picture = "ursg-preview-small";
                    require "views/layoutSwiping_noheader.phtml";
                } else {
                    header("Location: /?message=No user found");
                    exit();
                }
            }
            else 
            {
                header("Location: /No user found");
                exit();
            }
        }
    }

    public function pageUpdateProfile()
    {
        $this->requireUserSessionOrRedirect($redirectUrl = '/');
        // Get important datas
        $this->initializeLanguage();
        $user = $this-> user -> getUserById($_SESSION['userId']);
        $allUsers = $this-> user -> getAllUsers();
        $friendRequest = $this-> friendrequest -> getFriendRequest($_SESSION['userId']);

        $kindofgamers = ["Chill" => "Chill / Normal games", "Competition" => "Competition / Ranked", "Competition and Chill" => "Competition/Ranked and chill"];
        $genders = ["Male", "Female", "Non binary", "Trans Man", "Trans Woman"];
        $current_url = "https://ur-sg.com/updateProfile";
        $template = "views/swiping/update_profile";
        $page_title = "URSG - Profile";
        $picture = "ursg-preview-small";
        require "views/layoutSwiping.phtml";
    }

    public function pageSettings()
    {
            $this->requireUserSessionOrRedirect($redirectUrl = '/');
            // Get important datas
            $this->initializeLanguage();
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $allUsers = $this-> user -> getAllUsers();
            $page_css = ['settings'];
            $current_url = "https://ur-sg.com/settings";
            $template = "views/swiping/settings";
            $page_title = "URSG - Settings";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping.phtml";
    }

    public function chatFilterSwitch()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            
            $userId = $data->userId;
            $status = $data->status;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

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

    public function chatFilterSwitchWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            
            $userId = $data->userId;
            $status = $data->status;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            if (isset($_SESSION)) {
                $user = $this-> user -> getUserById($userId);

                if ($user['user_id'] != $userId)
                {
                    echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                    return;
                }
            }


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

    public function arcaneSide()
    {
        if (isset($_POST['pick'])) {
            $side = $_POST['side'];
            $userId = $_POST['userId'];
            $user = $this->user->getUserById($userId);

            if (in_array($side, ["Piltover", "Zaun", "none"])) 
            {
                // if they pick Piltover
                if ($side === "Piltover") {
                    $updateSide = $this->user->updateSide($side, $userId, $user['user_currency']);

                    if ($updateSide) {
                        $response = array('success' => true, 'side' => 'Piltover');
                    } else {
                        $response = array('success' => false, 'error' => 'No side could be picked');
                    }
                    echo json_encode($response);
                    exit;
                }

                // if they pick Zaun
                if ($side === "Zaun") {
                    $updateSide = $this->user->updateSide($side, $userId, $user['user_currency']);

                    if ($updateSide) {
                        $response = array('success' => true, 'side' => 'Zaun');
                    } else {
                        $response = array('success' => false, 'error' => 'No side could be picked');
                    }
                    echo json_encode($response);
                    exit;
                }

                //if they wanna ignore
                if ($side === "none") {
                    $updateSide = $this->user->ignoreSide($userId);

                    if ($updateSide) {
                        $response = array('success' => true, 'side' => 'Ignored');
                    } else {
                        $response = array('success' => false, 'error' => 'No side could be picked');
                    }
                    echo json_encode($response);
                    exit;
                }
            }
        }
    }

    public function arcaneSideWebsite()
    {
        if (isset($_POST['pick'])) {
            $side = $_POST['side'];
            $userId = $_POST['userId'];
            $user = $this->user->getUserById($userId);


                if (isset($_SESSION)) {

                    if ($user['user_id'] != $userId)
                    {
                        echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                        return;
                    }
                }

            if (in_array($side, ["Piltover", "Zaun", "none"])) 
            {
                // if they pick Piltover
                if ($side === "Piltover") {
                    $updateSide = $this->user->updateSide($side, $userId, $user['user_currency']);

                    if ($updateSide) {
                        $response = array('success' => true, 'side' => 'Piltover');
                    } else {
                        $response = array('success' => false, 'error' => 'No side could be picked');
                    }
                    echo json_encode($response);
                    exit;
                }

                // if they pick Zaun
                if ($side === "Zaun") {
                    $updateSide = $this->user->updateSide($side, $userId, $user['user_currency']);

                    if ($updateSide) {
                        $response = array('success' => true, 'side' => 'Zaun');
                    } else {
                        $response = array('success' => false, 'error' => 'No side could be picked');
                    }
                    echo json_encode($response);
                    exit;
                }

                //if they wanna ignore
                if ($side === "none") {
                    $updateSide = $this->user->ignoreSide($userId);

                    if ($updateSide) {
                        $response = array('success' => true, 'side' => 'Ignored');
                    } else {
                        $response = array('success' => false, 'error' => 'No side could be picked');
                    }
                    echo json_encode($response);
                    exit;
                }
            }
        }
    }

    public function getCurrencyWebsite() {
        $response = array('message' => 'Error');
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            if (!isset($_POST['userId'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid request']);
                return;
            }
        
            $userId = (int)$_POST['userId'];
        
            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

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

    public function reportUserWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            $userId = $data->userId;
            $reportedId = $data->reportedId;
            $reason = $data->reason;
            $status = $data->status;
            $content = $data->content;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            if (isset($_SESSION)) {
                $user = $this-> user -> getUserById($userId);

                if ($user['user_id'] != $userId)
                {
                    echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                    return;
                }
            }

            $reportUser = $this->report->reportUser($userId, $reportedId, $content, $status, $reason);

            if ($reportUser) {
                $response = array('success' => true, 'message' => 'Reported successfully');
            } else {
                $response = array('success' => false, 'message' => 'Could not report user');
            }
            echo json_encode($response);
        }
    }

    public function reportUserPhone()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            $userId = $data->userId;
            $reportedId = $data->reportedId;
            $reason = $data->reason;
            $status = $data->status;
            $content = $data->content;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            if (isset($_SESSION)) {
                $user = $this-> user -> getUserById($userId);

                if ($user['user_id'] != $userId)
                {
                    echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                    return;
                }
            }

            $reportUser = $this->report->reportUser($userId, $reportedId, $content, $status, $reason);

            if ($reportUser) {
                $response = array('success' => true, 'message' => 'Reported successfully');
            } else {
                $response = array('success' => false, 'message' => 'Could not report user');
            }
            echo json_encode($response);
        }
    }

    public function userIsLookingForGameWebsite() {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $this->setUserId($userId);
        $user = $this->user->getUserById($userId);
        $oldTime = $user['user_requestIsLooking'];

        $setStatus = $this->user->userIsLookingForGame($this->getUserId());

        if($setStatus) {
            $response = array('success' => true, 'message' => 'Status updated', 'oldTime' => $oldTime);
        } else {
            $response = array('success' => false, 'message' => 'Could not update status');
        }

        echo json_encode($response);
    }

    public function userIsLookingForGamePhone() {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $this->setUserId($userId);

        $setStatus = $this->user->userIsLookingForGame($this->getUserId());

        if($setStatus) {
            $response = array('success' => true, 'message' => 'Status updated');
        } else {
            $response = array('success' => false, 'message' => 'Could not update status');
        }

        echo json_encode($response);
    }

    public function getPersonalityTestResult() {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $personalityTestResult = $this->user->getPersonalityTestResult($userId);
    
        if ($personalityTestResult !== false) {
            // Decode the JSON before sending it back
            $decodedResult = json_decode($personalityTestResult, true);
    
            echo json_encode(['success' => true, 'result' => $decodedResult]);
        } else {
            echo json_encode(['success' => true, 'error' => 'Could not get personality test result', 'result' => false]);
        }
    }

    public function savePersonalityTestResult() {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        // Decode raw JSON input
        $input = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($input['userId']) || !isset($input['result'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int) $input['userId'];
        $result = $input['result'];
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        // Save full result as JSON
        $savePersonalityTestResult = $this->user->savePersonalityTestResult($userId, json_encode($result));
    
        if ($savePersonalityTestResult) {
            echo json_encode(['success' => true, 'userFitting' => $savePersonalityTestResult]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database save failed']);
        }
    }

    public function getMatchingPersonalityUser()
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId']) || !isset($_POST['personalityType'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
        $personalityType = $_POST['personalityType'] ?? null;
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $matchingPersonalityUser = $this->user->getMatchingPersonalityUser($personalityType);
    
        if ($matchingPersonalityUser !== false) {
            echo json_encode(['success' => true, 'result' => $matchingPersonalityUser]);
        } else {
            echo json_encode(['success' => true, 'error' => 'Could not get matching personality user', 'result' => false]);
        }
    }

    public function rateFriendWebsite()
    {
        // Check POST parameters
        if (!isset($_POST['friendId'], $_POST['matchId'], $_POST['rating'], $_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        $friendId = intval($_POST['friendId']);
        $matchId = trim($_POST['matchId']);
        $rating = intval($_POST['rating']);

        // Validate rating range (e.g. 1 to 5)
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'error' => 'Invalid rating value']);
            return;
        }

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        // Validate token and get rater's userId
        $userId = intval($_POST['userId']);
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        // Prevent rating yourself
        if ($userId === $friendId) {
            echo json_encode(['success' => false, 'error' => 'Cannot rate yourself']);
            return;
        }

        // Check friend exists
        $friend = $this->user->getUserById($friendId);
        if (!$friend) {
            echo json_encode(['success' => false, 'error' => 'Friend not found']);
            return;
        }

        // Check if rating already exists
        $existingRating = $this->rating->getRatingByUserAndFriend($userId, $friendId, $matchId);
        $result = false;

        if ($existingRating) {
            // Update existing rating
            $result = $this->rating->updateRating($userId, $friendId, $matchId, $rating);
            if (!$result) {
                echo json_encode(['success' => false, 'error' => 'Database error']);
                return;
            }
        } else {
            // Insert new rating
            $result = $this->rating->insertFirstRating($userId, $friendId, $matchId, $rating);
            if (!$result) {
                echo json_encode(['success' => false, 'error' => 'Database error']);
                return;
            }
        }

        if ($result) {
            // Return updated average rating & count
            $avgData = $this->rating->getAverageRatingForUser($friendId);
            echo json_encode([
                'success' => true,
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }

    public function switchPersonalColorWebsite()
    {
        if (!isset($_POST['color']) || !isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            return;
        }

        $userId = $this->validateInput($_POST['userId']);
        $color = $this->validateInput($_POST['color']);

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $updateColor = $this->user->updatePersonalColor($color, $userId);

        if ($updateColor) {
            echo json_encode(['success' => true, 'message' => 'Color updated successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not update color']);
            exit;
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

    public function validateInputJSON($input) 
    {
        if (is_string($input)) {
            $input = trim($input);
        }
    
        if (is_string($input) && (strpos($input, '[') === 0 || strpos($input, '{') === 0)) {
            $decodedInput = json_decode($input, true);
    
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decodedInput;
            }
        }
    
        return is_string($input) ? htmlspecialchars($input, ENT_QUOTES, 'UTF-8') : $input;
    }

    public function isUsernameForbidden($username) {
        require 'bannedUsernames.php';
        $username = strtolower(trim($username));
    
        if (in_array($username, $bannedUsernames)) {
            return true;
        }
    
        foreach ($bannedUsernames as $banned) {
            if (preg_match('/' . preg_quote($banned, '/') . '/i', $username)) {
                return true;
            }
        }
    
        return false;
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

    public function getBluesky()
    {
        return $this->bluesky;
    }

    public function setBluesky($bluesky)
    {
        $this->bluesky = $bluesky;
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

    public function getLfFilteredServer()
    {
        return $this->lfFilteredServer;
    }

    public function setLfFilteredServer($lfFilteredServer)
    {
        $this->lfFilteredServer = $lfFilteredServer;
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
        $this->loLRoleLf = $loLRoleLf; 
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