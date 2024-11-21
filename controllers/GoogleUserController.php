<?php

namespace controllers;

use models\GoogleUser;
use models\User;
use models\LeagueOfLegends;
use models\Valorant;
use models\UserLookingFor;
use models\MatchingScore;
use models\Partners;
use traits\SecurityController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class GoogleUserController
{
    use SecurityController;

    private GoogleUser $googleUser;
    private User $user;
    private LeagueOfLegends $leagueoflegends;
    private Valorant $valorant;
    private UserLookingFor $userlookingfor;
    private MatchingScore $matchingscore;
    private Partners $partners;
    private $googleId;
    private $googleUserId;
    private $googleFullName;
    private $googleFirstName;
    private $googleFamilyName;
    private $googleEmail;
    private $googleImageUrl;

    
    public function __construct()
    {
        $this -> googleUser = new GoogleUser();
        $this -> user = new User();
        $this -> leagueoflegends = new LeagueOfLegends();
        $this -> valorant = new Valorant();
        $this -> userlookingfor = new UserLookingFor();
        $this -> matchingscore = new MatchingScore();
        $this -> partners = new Partners();
    }

    public function signUpBypass() {
        $_SESSION['google_userId'] = 67;
        $_SESSION['full_name'] = 'Emma Montbarbon Dosylia';
        $_SESSION['google_id'] = 100980850167766474664;
        $_SESSION['email'] = "n7xemma@gmail.com";
        $_SESSION['google_firstName'] = "Emma Montbarbon";
        $_SESSION['userId'] = 57;
        $_SESSION['username'] = "GirlLFeDaddy";
        $_SESSION['gender'] = "Female";
        $_SESSION['age'] = 26;
        $_SESSION['kindOfGamer'] = "Chill";
        $_SESSION['game'] = "League of Legends";
        $_SESSION['lol_id'] = 15;
        $_SESSION['lf_id'] = 8;

        header("Location: /swiping");
        exit();
    }

    public function homePage() 
    {
        if($this->isConnectGoogle())
        {
            $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);
        }

        if($this->isConnectWebsite())
        {
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
        }
        $partners = $this -> partners -> getPartners();
        $current_url = "https://ur-sg.com/";
        $template = "views/home";
        $title = "JOIN NOW";
        $page_title = "URSG - Home";
        require "views/layoutHome.phtml";
    }

    private function getSocialNetworkLogo($social)
    {
        $logos = [
            'facebook' => 'path/to/facebook-logo.png',
            'x' => 'public/images/twitter_user.png',
            'instagram' => 'path/to/instagram-logo.png',
            'twitch' => 'public/images/twitch_user.png',
            'youtube' => 'path/to/youtube-logo.png',
        ];

        return $logos[strtolower($social)] ?? 'path/to/default-logo.png';
    }

    public function confirmMailPage() 
    {

        if (isset($_SESSION['email'])) {
            $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);
        } else {
            header("Location: /?message=No email");
            exit();
        }

        if($googleUser['google_confirmEmail'] == 0 || $googleUser['google_confirmEmail'] == NULL)
        {
            $current_url = "https://ur-sg.com/confirmMail";
            $template = "views/signup/waitingEmail";
            $title = "Confirm Mail";
            $page_title = "URSG - Confirm Mail";
            require "views/layoutSignup.phtml";
        }
        else if($googleUser['google_confirmEmail'] == 1 && !$this->isConnectWebsite())
        {
            ob_start();
            header("Location: /signup");
            exit();
        }
        else if($googleUser['google_confirmEmail'] == 1 && $this->isConnectWebsite())
        {
            ob_start();
            header("Location: /signup");
            exit();
        }
        else
        {
            ob_start();
            header("Location: /swiping");
            exit();
        }
    }

    public function confirmMailPhone() 
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    
        // Check if the request method is POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $postData = file_get_contents('php://input');
            // Decode the JSON data
            $data = json_decode($postData, true);

            if (isset($data->email)) {
                $googleUser = $this-> googleUser -> getGoogleUserByEmail($data->email);
            } else {
                echo json_encode(['message' => 'No email']);
                exit();
            }

            if($googleUser['google_confirmEmail'] == 0 || $googleUser['google_confirmEmail'] == NULL)
            {
                echo json_encode(['message' => 'Success']);
                exit();
            }
            else
            {
                echo json_encode(['message' => 'Email is not confirmed']);
                exit();
            }
        }
    }

    

    public function pageSignUp()
    {
        if (isset($_SESSION['email'])) {
            $googleUser = $this->googleUser->getGoogleUserByEmail($_SESSION['email']);
        }
        if (isset($_SESSION['google_userId'])) {
            $secondTierUser = $this->user->getUserDataByGoogleUserId($_SESSION['google_userId']);
            if ($secondTierUser) {
                $finalUser = $this->user->getUserById($secondTierUser['user_id']);
            }
        }

        if (
            $this->isConnectGoogle() && 
            $this->isConnectWebsite() && 
            (
                (
                    $this->isConnectLeague() && 
                    !$this->isConnectValorant() && 
                    $finalUser['lf_lolmain1'] !== NULL
                ) || 
                (
                    $this->isConnectValorant() && 
                    !$this->isConnectLeague() && 
                    $finalUser['lf_valmain1'] !== NULL
                )
            ) && 
            $this->isConnectLf()
        )  {
            // Code block 1: User is connected via Google, Website and has League data and looking for data
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersAll = $this-> user -> getAllUsersExceptFriends($_SESSION['userId']);
            $allUsersArcane = $this-> user -> getAllUsers();
            if ($user && $usersAll) {
                $userData = json_encode($user);
                $usersAllData = json_encode($usersAll);
            }

            
            // ARCANE EVENT
            $totalPiltoverCurrency = 0;
            $totalZaunCurrency = 0;

            foreach ($allUsersArcane as $userArcane) {
                if ($userArcane['user_arcane'] === 'Piltover') {
                    $totalPiltoverCurrency += $userArcane['user_currency'];
                } elseif ($userArcane['user_arcane'] === 'Zaun') {
                    $totalZaunCurrency += $userArcane['user_currency'];
                }
            }

            $totalCurrency = $totalPiltoverCurrency + $totalZaunCurrency;
            $piltoverPercentage = $totalCurrency > 0 ? ($totalPiltoverCurrency / $totalCurrency) * 100 : 0;
            $zaunPercentage = 100 - $piltoverPercentage; 

            $current_url = "https://ur-sg.com/swiping";
            $template = "views/swiping/swiping_main";
            $title = "Swipe test";
            $page_title = "URSG - Swiping";
            require "views/layoutSwiping.phtml";;
        } elseif (
            $this->isConnectGoogle() && 
            $this->isConnectWebsite() && 
            (
                (
                    $this->isConnectValorant() && 
                    !$this->isConnectLeague() && 
                    $finalUser['lf_valmain1'] == NULL
                    && $finalUser['user_game'] == "Valorant"
                )
            ) && 
            $this->isConnectLf()
        )  {
            // Code block 2: User is connected via Google, Website and has League data, need looking for
            $valorantUser = $this->valorant->getValorantUserByValorantId($_SESSION['valorant_id']);
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $current_url = "https://ur-sg.com/lookingforuservalorant";
            $template = "views/signup/lookingforvalorant";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif (
            $this->isConnectGoogle() && 
            $this->isConnectWebsite() && 
            (
                (
                    $this->isConnectLeague() && 
                    !$this->isConnectValorant() && 
                    $finalUser['lf_lolmain1'] == NULL && $finalUser['user_game'] == "League of Legends"
                )
            ) && 
            $this->isConnectLf()
        )  {
            // Code block 3: User is connected via Google, Website and has League data, need looking for
            $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $current_url = "https://ur-sg.com/lookingforuserlol";
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            !$this->isConnectLf()
        ) {
            // Code block 4: User is connected via Google, Website and has League data, need looking for
            $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $current_url = "https://ur-sg.com/lookingforuserlol";
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectLeague() && $secondTierUser['user_game'] === "League of Legends" && !$this->isConnectLf()) { 
            // Code block 5: User is connected via Google and username is set , but game settings not done. Redirect for LoL only
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $current_url = "https://ur-sg.com/leagueuser";
            $template = "views/signup/leagueoflegendsuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectValorant() && $secondTierUser['user_game'] === "Valorant") {
            // Code block 6: User is connected via Google and username is set , but game settings not done. Redirect for Valorant only
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $current_url = "https://ur-sg.com/valorant";
            $template = "views/signup/valorantuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite() && $googleUser['google_confirmEmail'] == 1) {
            // Code block 7: User is connected via Google but doesn't have a username
            $current_url = "https://ur-sg.com/basicinfo";
            $template = "views/signup/basicinfo";
            $title = "Sign up";
            $page_title = "URSG - Sign";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite() && $googleUser['google_confirmEmail'] == 0) {
            // Code block 8: User is connected via Google but doesn't have a username
            $current_url = "https://ur-sg.com/confirmMail";
            $template = "views/signup/waitingEmail";
            $title = "Confirm Mail";
            $page_title = "URSG - Confirm Mail";
            require "views/layoutSignup.phtml";
        
        } else {
            // Code block 9: Redirect to / if none of the above conditions are met
            header("Location: /&message=test");
            exit();
        }
    }  

    public function legalNoticePage() 
    {
        $current_url = "https://ur-sg.com/legalNotice";
        $template = "views/legalnotice";
        $title = "Legal Notice";
        $page_title = "URSG - Legal notice";
        require "views/layoutSwiping_noheader.phtml";
    }

    public function termsOfServicePage() 
    {
        $current_url = "https://ur-sg.com/termsOfService";
        $template = "views/termsofservice";
        $title = "Terms of service";
        $page_title = "URSG - Terms of service";
        require "views/layoutSwiping_noheader.phtml";
    }

    public function siteMapPage() 
    {
        $xml = simplexml_load_file('sitemap.xml');
            $current_url = "https://ur-sg.com/siteMap";
        $template = "views/sitemap";
        $title = "Site map";
        $page_title = "URSG - Site map";
        require "views/layoutSwiping_noheader.phtml";
    }

    public function notFoundPage() 
    {
        $current_url = "https://ur-sg.com/";
        $template = "views/pageNotFound";
        $title = "404 - Page not found";
        $page_title = "URSG - 404 - Page not found";
        require "views/layoutSwiping_noheader.phtml";
    }

    public function verifyIdToken($idToken) {
        $client = new GoogleClient(['client_id' => '666369513537-r75otamfu9qqsnaklgqiromr7bhiehft.apps.googleusercontent.com']);
        $payload = $client->verifyIdToken($idToken);
        return $payload;
    }

    // public function getGoogleData() 
    // {
    //     $_SESSION['google_userId'] = 67;
    //     $_SESSION['full_name'] = 'Emma Montbarbon Dosylia';
    //     $_SESSION['google_id'] = 100980850167766474664;
    //     $_SESSION['email'] = "n7xemma@gmail.com";
    //     $_SESSION['google_firstName'] = "Emma Montbarbon";
    //     $_SESSION['userId'] = 57;
    //     $_SESSION['username'] = "GirlLFeDaddy";
    //     $_SESSION['gender'] = "Female";
    //     $_SESSION['age'] = 26;
    //     $_SESSION['kindOfGamer'] = "Chill";
    //     $_SESSION['game'] = "League of Legends";
    //     $_SESSION['lol_id'] = 15;
    //     $_SESSION['lf_id'] = 8;

    //     $response = array('message' => 'Success');
    //     header('Content-Type: application/json');
    //     echo json_encode($response);
    //     exit;   
    // }

    public function getGoogleData() 
    {
        $response = array('message' => 'Error');
    
        if (isset($_POST['googleData'])) // DATA SENT BY AJAX
        {
            $googleData = json_decode($_POST['googleData']);
            $googleId = $googleData->googleId;
            $this->setGoogleId($googleId); 
            if (isset($googleData->fullName))
            {
                $googleFullName = $googleData->fullName;
                $this->setGoogleFullName($googleFullName);              
            }
            if (isset($googleData->givenName))
            {
                $googleFirstName = $googleData->givenName;
                $this->setGoogleFirstName($googleFirstName);  
            }
    
            if (isset($googleData->familyName))
            {
                $googleFamilyName = $googleData->familyName;
                $this->setGoogleFamilyName($googleFamilyName);  
            }
            $googleEmail = $googleData->email;
            $this->setGoogleEmail($googleEmail);  
            
            $testGoogleUser = $this->googleUser->userExist($this->getGoogleId());

            if($testGoogleUser) //CREATING SESSION IF USER EXISTS 
            {
                if (!$this->isConnectGoogle()) 
                {
                    
                    if (!isset($_SESSION['googleId'])) 
                    {
                        $_SESSION['google_userId'] = $testGoogleUser['google_userId'];
                        $_SESSION['full_name'] = $this->getGoogleFullName();
                        $_SESSION['google_id'] = $this->getGoogleId();
                        $_SESSION['email'] = $this->getGoogleEmail();
                        $_SESSION['google_firstName'] = $this->getGoogleFirstName();
    
                        $googleUser = $this->user->getUserDataByGoogleUserId($testGoogleUser['google_userId']);
                        if ($googleUser) {
                            $user = $this->user->getUserByUsername($googleUser['user_username']);
                            if ($user)
                            {
                                $_SESSION['userId'] = $user['user_id'];
                                $_SESSION['username'] = $user['user_username'];
                                $_SESSION['gender'] = $user['user_gender'];
                                $_SESSION['age'] = $user['user_age'];
                                $_SESSION['kindOfGamer'] = $user['user_kindOfGamer'];
                                $_SESSION['game'] = $user['user_game'];

                                if ($user['user_game'] == 'League of Legends') {
                                    $lolUser = $this->leagueoflegends->getLeageUserByUserId($user['user_id']);

                                    $response = array(
                                        'message' => 'Success',
                                        'LolUser' => $lolUser,
                                    );                    
                                    
                                    if ($lolUser)
                                    {
                                        $_SESSION['lol_id'] = $lolUser['lol_id'];
        
                                        $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
        
                                        if ($lfUser)
                                        {
                                            $_SESSION['lf_id'] = $lfUser['lf_id']; 
                                            $response = array(
                                                'message' => 'Success',
                                                'newUser' => false,
                                                'userExists' => true,
                                                'leagueUserExists' => true,
                                                'lookingForUserExists' => true,
                                                'googleUser' => $testGoogleUser,
                                                'user' => $user,
                                                'leagueUser' => $lolUser,
                                                'lookingForUser' => $lfUser
                                            );                                
                                        } else {
                                            $response = array(
                                                'message' => 'Success',
                                                'newUser' => false,
                                                'userExists' => true,
                                                'leagueUserExists' => true,
                                                'lookingForUserExists' => false,
                                                'googleUser' => $testGoogleUser,
                                                'user' => $user,
                                                'leagueUser' => $lolUser
                                            );
                                        }
                                    } else {
                                        $response = array(
                                            'message' => 'Success',
                                            'newUser' => false,
                                            'googleUser' => $testGoogleUser,
                                            'user' => $user,
                                            'userExists' => true,
                                            'leagueUserExists' => false
                                        );
                                    }
                                } else {
                                    $valorantUser = $this->valorant->getValorantUserByUserId($user['user_id']);
                                    
                                    if ($valorantUser)
                                    {
                                        $_SESSION['valorant_id'] = $valorantUser['valorant_id'];
                                
                                        $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
                                
                                        if ($lfUser)
                                        {
                                            $_SESSION['lf_id'] = $lfUser['lf_id']; 
                                            $response = array(
                                                'message' => 'Success',
                                                'newUser' => false,
                                                'userExists' => true,
                                                'leagueUserExists' => false,
                                                'lookingForUserExists' => true,
                                                'googleUser' => $testGoogleUser,
                                                'user' => $user,
                                                'valorantUser' => $valorantUser,
                                                'lookingForUser' => $lfUser,
                                                'valorantUserExists' => true
                                            );                                
                                        } else {
                                            $response = array(
                                                'message' => 'Success',
                                                'newUser' => false,
                                                'userExists' => true,
                                                'leagueUserExists' => true,
                                                'lookingForUserExists' => false,
                                                'googleUser' => $testGoogleUser,
                                                'user' => $user,
                                                'valorantUser' => $valorantUser,
                                                'valorantUserExists' => true
                                            );
                                        }
                                    } else {
                                        $response = array(
                                            'message' => 'Success',
                                            'newUser' => false,
                                            'googleUser' => $testGoogleUser,
                                            'user' => $user,
                                            'userExists' => true,
                                            'leagueUserExists' => false,
                                            'valorantUserExists' => false
                                        );
                                    }
                                }
                            } else {
                                $response = array(
                                    'message' => 'Success',
                                    'newUser' => false,
                                    'googleUser' => $testGoogleUser,
                                    'userExists' => false
                                );
                            }
                        } else {
                            $response = array(
                                'message' => 'Success',
                                'newUser' => false,
                                'googleUser' => $testGoogleUser,
                                'userExists' => false
                            );
                        }
                    }
                }
    
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }
            else // IF USER DOES NOT EXIST, INSERT IT INTO DATABASE
            {
                $createGoogleUser = $this->googleUser->createGoogleUser($this->getGoogleId(),$this->getGoogleFullName(),$this->getGoogleFirstName(),$this->getGoogleFamilyName(),$this->getGoogleEmail());
    
                if($createGoogleUser) 
                {
                    require 'keys.php';
    
                    $this->setGoogleUserId($createGoogleUser);
    
                    $lifetime = 7 * 24 * 60 * 60;
    
                    session_destroy();
    
                    session_set_cookie_params($lifetime);
    
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    if (!isset($_SESSION['googleId'])) {
                        $_SESSION['google_userId'] = $this->getGoogleUserId();
                        $_SESSION['full_name'] = $this->getGoogleFullName();
                        $_SESSION['google_id'] = $this->getGoogleId();
                        $_SESSION['email'] = $this->getGoogleEmail();
                        $_SESSION['google_firstName'] = $this->getGoogleFirstName();
                    }

                    $email = $this->getGoogleEmail();
    
                    $mail = new PHPMailer;
                    $mail->isSMTP();
                    $mail->Host = 'smtp.ionos.de';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'contact@ur-sg.com';
                    $mail->Password = $password_gmail;
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                    
                    $mail->setFrom('contact@ur-sg.com', 'UR-SG.com');
                    $mail->addAddress($this->getGoogleEmail());
                    $mail->Subject = 'Confirm your email for UR-SG.com';
                    $mail->isHTML(true);
                    
                    $mail->CharSet = 'UTF-8'; 
                    $mail->Encoding = 'quoted-printable'; 
                    
                    $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                background-color: #f4f4f4;
                                padding: 20px;
                            }
                            .container {
                                background-color: #ffffff;
                                padding: 20px;
                                border-radius: 10px;
                                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                            }
                            .header {
                                color: #333;
                                font-size: 24px;
                                margin-bottom: 20px;
                            }
                            .button {
                                display: inline-block;
                                padding: 10px 20px;
                                color: #fff !important;
                                background-color: #e74057;
                                text-decoration: none;
                                border-radius: 5px;
                            }
                            .footer {
                                margin-top: 20px;
                                font-size: 12px;
                                color: #999;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>Confirm Your Email for UR-SG.com</div>
                            <p>Thank you for registering on UR-SG.com!</p>
                            <p>Your email: {$email}</p>
                            <p>To confirm your email, please click the button below:</p>
                            <a href='https://ur-sg.com/acceptConfirm?mail={$email}' class='button'>Confirm Email</a>
                        </div>
                        <div class='footer'>If you didn't request this, please ignore this email.</div>
                    </body>
                    </html>
                    ";
    
                    if ($mail->send()) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => true,
                            'googleUser' => $createGoogleUser,
                        );
                    } else {
                        $response = array(
                            'message' => 'Failed to send email',
                            'newUser' => false,
                            'googleUser' => null,
                        );
                    }
                }
            }
        }
        else
        {
            $response = array(
                'message' => 'Contact an administrator',
            );
        }
    
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    public function getGoogleDataPhone() 
    {
        $response = array('message' => 'Error');
    
        if (isset($_POST['googleData'])) // DATA SENT BY AJAX
        {
            $googleData = json_decode($_POST['googleData']);
            $googleId = $googleData->googleId;
            $this->setGoogleId($googleId); 
            if (isset($googleData->fullName))
            {
                $googleFullName = $googleData->fullName;
                $this->setGoogleFullName($googleFullName);              
            }
            if (isset($googleData->givenName))
            {
                $googleFirstName = $googleData->givenName;
                $this->setGoogleFirstName($googleFirstName);  
            }
    
            if (isset($googleData->familyName))
            {
                $googleFamilyName = $googleData->familyName;
                $this->setGoogleFamilyName($googleFamilyName);  
            }
            $googleEmail = $googleData->email;
            $this->setGoogleEmail($googleEmail);  
            
            $testGoogleUser = $this->googleUser->userExist($this->getGoogleId());

            if($testGoogleUser) //CREATING SESSION IF USER EXISTS 
            {
            
                $googleUserData = array(
                    'googleId' => $testGoogleUser['google_id'],
                    'fullName' => $testGoogleUser['google_fullName'],
                    'firstName' => $testGoogleUser['google_firstName'],
                    'lastName' => $testGoogleUser['google_lastName'],
                    'email' => $testGoogleUser['google_email'],
                    'googleUserId' => $testGoogleUser['google_userId']
                );

                $googleUser = $this->user->getUserDataByGoogleUserId($testGoogleUser['google_userId']);
                if ($googleUser) {

                    $user = $this->user->getUserByUsername($googleUser['user_username']);
                    if ($user)
                    {
                        $userData = array(
                            'userId' => $user['user_id'],
                            'username' => $user['user_username'],
                            'gender' => $user['user_gender'],
                            'age' => $user['user_age'],
                            'kindOfGamer' => $user['user_kindOfGamer'],
                            'game' => $user['user_game'],
                            'shortBio' => $user['user_shortBio'],
                            'picture' => $user['user_picture'] ?? null,
                            'discord' => $user['user_discord'] ?? null,
                            'twitch' => $user['user_twitch'] ?? null,
                            'instagram' => $user['user_instagram'] ?? null,
                            'twitter' => $user['user_twitter'] ?? null,
                            'currency' => $user['user_currency'] ?? null,
                            'isVip' => $user['user_isVip'] ?? null,
                            'hasChatFilter' => ['user_hasChatFilter'] ?? null,
                            'arcane' => $user['user_arcane'] ?? null,
                            'arcaneIgnore' => $user['user_ignore'] ?? null
                        );

                        if ($user['user_game'] == 'League of Legends') {
                            $lolUser = $this->leagueoflegends->getLeageUserByUserId($user['user_id']);
                        
                            if ($lolUser)
                            {
                                $lolUserData = array(
                                    'lolId' => $lolUser['lol_id'],
                                    'main1' => $lolUser['lol_main1'],
                                    'main2' => $lolUser['lol_main2'],
                                    'main3' => $lolUser['lol_main3'],
                                    'rank' => $lolUser['lol_rank'],
                                    'role' => $lolUser['lol_role'],
                                    'server' => $lolUser['lol_server'],
                                    'account' => $lolUser['lol_account'],
                                    'sUsername' => $lolUser['lol_sUsername'],
                                    'sLevel' => $lolUser['lol_sLevel'],
                                    'sRank' => $lolUser['lol_sRank'],
                                    'sProfileIcon' => $lolUser['lol_sProfileIcon'],
                                    'skipSelectionLol' => $lolUser['lol_noChamp']
                                );
                        
                                $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
                        
                                if ($lfUser)
                                {
                                    $lookingforUserData = array(
                                        'lfId' => $lfUser['lf_id'],
                                        'lfGender' => $lfUser['lf_gender'],
                                        'lfKingOfGamer' => $lfUser['lf_kindofgamer'],
                                        'lfGame' => $lfUser['lf_game'],
                                        'main1Lf' => $lfUser['lf_lolmain1'],
                                        'main2Lf' => $lfUser['lf_lolmain2'],
                                        'main3Lf' => $lfUser['lf_lolmain3'],
                                        'rankLf' => $lfUser['lf_lolrank'],
                                        'roleLf' => $lfUser['lf_lolrole'],
                                        'skipSelectionLf' => $lfUser['lf_lolNoChamp']
                                    );
                        
                                    
                                    $response = array(
                                        'message' => 'Success',
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => true,
                                        'lookingForUserExists' => true,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'leagueUser' => $lolUserData,
                                        'lookingForUser' => $lookingforUserData
                                    );                                
                                } else {
                                    $response = array(
                                        'message' => 'Success',
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => true,
                                        'lookingForUserExists' => false,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'leagueUser' => $lolUserData
                                    );
                                }
                            } else {
                                $response = array(
                                    'message' => 'Success',
                                    'newUser' => false,
                                    'googleUser' => $googleUserData,
                                    'user' => $userData,
                                    'userExists' => true,
                                    'leagueUserExists' => false
                                );
                            }
                        } else {
                            $valorantUser = $this->valorant->getValorantUserByUserId($user['user_id']);
                        
                            if ($valorantUser)
                            {
                                $valorantUserData = array(
                                    'valorantId' => $valorantUser['valorant_id'],
                                    'main1' => $valorantUser['valorant_main1'],
                                    'main2' => $valorantUser['valorant_main2'],
                                    'main3' => $valorantUser['valorant_main3'],
                                    'rank' => $valorantUser['valorant_rank'],
                                    'role' => $valorantUser['valorant_role'],
                                    'server' => $valorantUser['valorant_server'],
                                    'skipSelectionVal' => $valorantUser['valorant_noChamp']
                                );
                        
                                $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
                        
                                if ($lfUser)
                                {
                                    $lookingforUserData = array(
                                        'lfId' => $lfUser['lf_id'],
                                        'lfGender' => $lfUser['lf_gender'],
                                        'lfKingOfGamer' => $lfUser['lf_kindofgamer'],
                                        'lfGame' => $lfUser['lf_game'],
                                        'valmain1Lf' => $lfUser['lf_valmain1'],
                                        'valmain2Lf' => $lfUser['lf_valmain2'],
                                        'valmain3Lf' => $lfUser['lf_valmain3'],
                                        'valrankLf' => $lfUser['lf_valrank'],
                                        'valroleLf' => $lfUser['lf_valrole'],
                                        'skipSelectionLf' => $lfUser['lf_valNoChamp']
                                    );
                        
                                    
                                    $response = array(
                                        'message' => 'Success',
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => false,
                                        'lookingForUserExists' => true,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'valorantUser' => $valorantUserData,
                                        'lookingForUser' => $lookingforUserData,
                                        'valorantUserExists' => true
                                    );                                
                                } else {
                                    $response = array(
                                        'message' => 'Success',
                                        'newUser' => false,
                                        'userExists' => true,
                                        'leagueUserExists' => false,
                                        'lookingForUserExists' => false,
                                        'googleUser' => $googleUserData,
                                        'user' => $userData,
                                        'valorantUser' => $valorantUserData,
                                        'valorantUserExists' => true
                                    );
                                }
                            } else {
                                $response = array(
                                    'message' => 'Success',
                                    'newUser' => false,
                                    'googleUser' => $googleUserData,
                                    'user' => $userData,
                                    'userExists' => true,
                                    'leagueUserExists' => false,
                                    'valorantUserExists' => false
                                );
                            }
                        }

                        
                    } else {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'googleUser' => $googleUserData,
                            'userExists' => false
                        );
                    }
                } else {
                    
                    $response = array(
                        'message' => 'Success',
                        'newUser' => false,
                        'googleUser' => $googleUserData,
                        'userExists' => false
                    );
                }
    
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }
            else // IF USER DOES NOT EXIST, INSERT IT INTO DATABASE
            {
                $createGoogleUser = $this->googleUser->createGoogleUserPhone($this->getGoogleId(),$this->getGoogleFullName(),$this->getGoogleFirstName(),$this->getGoogleFamilyName(),$this->getGoogleEmail());
    
                if($createGoogleUser) 
                {
                    $googleData = array(
                        'googleId' => $this->getGoogleId(),
                        'fullName' => $this->getGoogleFullName(),
                        'firstName' => $this->getGoogleFirstName(),
                        'lastName' => $this->getGoogleFamilyName(),
                        'email' => $this->getGoogleEmail(),
                        'googleUserId' => $createGoogleUser
                    );
    
                    $response = array(
                        'message' => 'Success',
                        'newUser' => true,
                        'googleUser' => $googleData,
                    );
                }
            }
        }
        else
        {
            $response = array(
                'message' => 'Contact an administrator', // No google data
            );
        }
    
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    

    public function logOut() {
        if ($this->isConnectGoogle() || $this->isConnectWebsite()) {
            if (isset($_COOKIE['googleId'])) {
                setcookie('googleId', "", time() - 42000, COOKIEPATH);
                unset($_COOKIE['googleId']);
            }
    
            session_unset();
            session_destroy();
    
            header("location:/?message=You are now offline");
            exit();
        } else {
            header("location:/?message=You are now offline");
            exit();
        }
    }


    public function emailConfirmDb()
    {
        if(isset($_GET['mail']))
        {

            $email = ($_GET['mail']);
            $testEmail = $this->googleUser->getGoogleUserByEmail($email);
            if($testEmail) 
            {
                $confirmEmail = $this->googleUser->updateEmailStatus($email);
                if($confirmEmail)
                {
                    header("location:/signup?message=Email confirmed");
                    exit();                   
                }
                else 
                {
                    header("location:/?message=Couldnt confirm email");
                    exit();                    
                }
            }
            else
            {
                header("location:/?message=Email does not exists");
                exit();
            }

        }
    }  


    public function sendEmail() 
    {
        require 'keys.php';
        
        if (isset($_POST['email_confirm'])) {
            $email = filter_var($_POST['email_confirm'], FILTER_SANITIZE_EMAIL);
    
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header("Location: /signup?message=Invalid email address");
                exit();
            }
    
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@ur-sg.com';
            $mail->Password = $password_gmail;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
    
            $mail->setFrom('contact@ur-sg.com', 'UR-SG.com');
            $mail->addAddress($email);
            $mail->Subject = 'Confirm your email for UR-SG.com';
            $mail->isHTML(true);
            
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
            
            $mail->Body = "
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f4f4f4;
                        padding: 20px;
                    }
                    .container {
                        background-color: #ffffff;
                        padding: 20px;
                        border-radius: 10px;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    }
                    .header {
                        color: #333;
                        font-size: 24px;
                        margin-bottom: 20px;
                    }
                    .button {
                        display: inline-block;
                        padding: 10px 20px;
                        color: #fff !important;
                        background-color: #e74057;
                        text-decoration: none;
                        border-radius: 5px;
                    }
                    .footer {
                        margin-top: 20px;
                        font-size: 12px;
                        color: #999;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>Confirm Your Email for UR-SG.com</div>
                    <p>Thank you for registering on UR-SG.com!</p>
                    <p>Your email: {$email}</p>
                    <p>To confirm your email, please click the button below:</p>
                    <a href='https://ur-sg.com/acceptConfirm?mail={$email}' class='button'>Confirm Email</a>
                </div>
                <div class='footer'>If you didn't request this, please ignore this email.</div>
            </body>
            </html>
            ";
    
            if ($mail->send()) {
                $this->confirmMailPage($mail);
            } else {
                header("Location: /signup?message=Could not send mail");
                exit();
            }
        } 
    }
    

    public function sendEmailPhone() 
    {
        require 'keys.php';
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    
        // Check if the request method is POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get the raw POST data
            $postData = file_get_contents('php://input');
            // Decode the JSON data
            $data = json_decode($postData, true);
            $email = $data->email;
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@ur-sg.com';
            $mail->Password = $password_gmail;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
        
            $mail->setFrom('contact@ur-sg.com', 'UR-SG.com');
            $mail->addAddress($email);
            $mail->Subject = 'Confirm your email for UR-SG.com';
            $mail->isHTML(true);
        
            $boundary = md5(uniqid());
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
        
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f4f4f4;
                            padding: 20px;
                        }
                        .container {
                            background-color: #ffffff;
                            padding: 20px;
                            border-radius: 10px;
                            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                        }
                        .header {
                            color: #333;
                            font-size: 24px;
                            margin-bottom: 20px;
                        }
                        .button {
                            display: inline-block;
                            padding: 10px 20px;
                            color: #fff !important;
                            background-color: #e74057;
                            text-decoration: none;
                            border-radius: 5px;
                        }
                        .footer {
                            margin-top: 20px;
                            font-size: 12px;
                            color: #999;
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>Confirm Your Email for UR-SG.com</div>
                        <p>Thank you for registering on UR-SG.com!</p>
                        <p>Your email: {$email}</p>
                        <p>To confirm your email, please click the button below:</p>
                        <a href='https://ur-sg.com/acceptConfirm?mail={$email}' class='button'>Confirm Email</a>
                    </div>
                    <div class='footer'>If you didn't request this, please ignore this email.</div>
                </body>
                </html>
                ";

            if ($mail->send()) {
                echo json_encode(['message' => 'Mail sent']);
                exit();
            } else {
                echo json_encode(['message' => "Mail couldn't be sent"]);
                exit();

            }
        } 
    }

    public function deleteAccountPage()
    {
        $current_url = "https://ur-sg.com/deleteAccount";
        $template = "views/swiping/delete_account";
        $page_title = "URSG - Delete account";
        require "views/layoutSwiping_noheader.phtml";
    }

    public function deleteAccountRequest()
    {
        if (isset($_POST['submit']))
        {
            $email = $this->validateInput($_POST["email"]);
    
            require 'keys.php';
    
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@ur-sg.com';
            $mail->Password = $password_gmail;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
        
            $mail->setFrom('contact@ur-sg.com', 'UR-SG.com');
            $mail->addAddress($email);
            $mail->Subject = 'Confirm deleting your URSG account';
            $mail->isHTML(true);
        
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
        
            // Set up the HTML content
            $mail->Body = "
            <html>
            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-color: #f4f4f4;
                        padding: 20px;
                    }
                    .container {
                        background-color: #ffffff;
                        padding: 20px;
                        border-radius: 10px;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    }
                    .header {
                        color: #333;
                        font-size: 24px;
                        margin-bottom: 20px;
                    }
                    .button {
                        display: inline-block;
                        padding: 10px 20px;
                        color: #fff !important;
                        background-color: #e74057;
                        text-decoration: none;
                        border-radius: 5px;
                    }
                    .footer {
                        margin-top: 20px;
                        font-size: 12px;
                        color: #999;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>Confirm Deleting Your URSG Account</div>
                    <p>We are sad to lose you!</p>
                    <p>Your email: {$email}</p>
                    <p>Confirm deleting your account by clicking the link below:</p>
                    <a href='https://ur-sg.com/deleteAccountConfirm?mail={$email}' class='button'>Confirm Deletion</a>
                </div>
                <div class='footer'>If you didn't request this, please ignore this email.</div>
            </body>
            </html>";
        
            if (!$mail->send()) {
                header("location:/?message=Could not send mail");
            } else {
                header("location:/?message=You received a mail to confirm your choice");
            }
        }
    }
    

    public function deleteAccountConfirm()
    {
        if (isset($_GET['mail']))
        {
            $email = $_GET['mail'];
            $deleteAccount = $this-> googleUser -> deleteAccount($email);
            if ($deleteAccount)
            {
                if($this->isConnectGoogle() || $this->isConnectWebsite()) {
                    if (isset($_COOKIE['googleId'])) {
                        setcookie('googleId', "", time() - 42000, COOKIEPATH);
                    }
                    unset($_COOKIE['googleId']);
                    session_unset();
                    session_destroy();
                    header("location:/?message=Account deleted");
                    exit();
                }
                header("location:/?message=Account deleted");
                exit();
            }
            else
            {
                header("location:/?message=Account not found");
                exit();
            }
        }
        else
        {
            header("location:/?message=Invalid request");
            exit();
        }

    }

    public function validateInput($input) 
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function getGoogleId()
    {
        return $this->googleId;
    }

    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;
    }

    public function getGoogleUserId()
    {
        return $this->googleUserId;
    }

    public function setGoogleUserId($googleUserId)
    {
        $this->googleUserId = $googleUserId;
    }

    public function getGoogleFullName()
    {
        return $this->googleFullName;
    }

    public function setGoogleFullName($googleFullName)
    {
        $this->googleFullName = $googleFullName;
    }

    public function getGoogleFirstName()
    {
        return $this->googleFirstName;
    }

    public function setGoogleFirstName($googleFirstName)
    {
        $this->googleFirstName = $googleFirstName;
    }

    public function getGoogleFamilyName()
    {
        return $this->googleFamilyName;
    }

    public function setGoogleFamilyName($googleFamilyName)
    {
        $this->googleFamilyName = $googleFamilyName;
    }

    public function getGoogleEmail()
    {
        return $this->googleEmail;
    }

    public function setGoogleEmail($googleEmail)
    {
        $this->googleEmail = $googleEmail;
    }
}