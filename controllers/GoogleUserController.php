<?php

namespace controllers;

use models\GoogleUser;
use models\User;
use models\LeagueOfLegends;
use models\UserLookingFor;
use models\MatchingScore;
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
    private UserLookingFor $userlookingfor;
    private MatchingScore $matchingscore;
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
        $this -> userlookingfor = new UserLookingFor();
        $this -> matchingscore = new MatchingScore();
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
        $current_url = "https://ur-sg.com/";
        $template = "views/home";
        $title = "JOIN NOW";
        $page_title = "URSG - Home";
        require "views/layoutHome.phtml";
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
            header("Location: /swippingmain");
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
        }

        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf()) {
            // Code block 1: User is connected via Google, Website and has League data and looking for data
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersAll = $this-> user -> getAllUsersExceptFriends($_SESSION['userId']);

            if ($user && $usersAll) {
                $userData = json_encode($user);
                $usersAllData = json_encode($usersAll);
            }
            $current_url = "https://ur-sg.com/swiping";
            $template = "views/swiping/swiping_main";
            $title = "Swipe test";
            $page_title = "URSG - Swiping";
            require "views/layoutSwiping.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague()) {
            // Code block 2: User is connected via Google, Website and has League data, need looking for
            $lolUser = $this->leagueoflegends->getLeageUserByLolId($_SESSION['lol_id']);
            $current_url = "https://ur-sg.com/lookingforuserlol";
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $secondTierUser['user_game'] === "leagueoflegends") { 
            // Code block 3: User is connected via Google and username is set , but game settings not done. Redirect for LoL only
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $current_url = "https://ur-sg.com/leagueuser";
            $template = "views/signup/leagueoflegendsuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && $secondTierUser['user_game'] === "valorant") {
                // Code block 4: User is connected via Google and username is set , but game settings not done. Redirect for Valorant only
            $current_url = "https://ur-sg.com/valorantuser";
                $template = "views/signup/valorant";
                $title = "More about you";
                $page_title = "URSG - Sign up";
                require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !isset($googleUser['user_username']) && $secondTierUser['user_game'] === "both"){
                // Code block 5: User is connected via Google and username is set , but game settings not done. Redirect for both games
            $current_url = "https://ur-sg.com/bothgame";
                $template = "views/signup/both";
                $title = "More about you";
                $page_title = "URSG - Sign up";
                require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite() && $googleUser['google_confirmEmail'] == 1) {
            // Code block 6: User is connected via Google but doesn't have a username
            $current_url = "https://ur-sg.com/basicinfo";
            $template = "views/signup/basicinfo";
            $title = "Sign up";
            $page_title = "URSG - Sign";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite() && $googleUser['google_confirmEmail'] == 0) {
            // Code block 6: User is connected via Google but doesn't have a username
            $current_url = "https://ur-sg.com/confirmMail";
            $template = "views/signup/waitingEmail";
            $title = "Confirm Mail";
            $page_title = "URSG - Confirm Mail";
            require "views/layoutSignup.phtml";
        
        } else {
            // Code block 7: Redirect to / if none of the above conditions are met
            header("Location: /");
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

    public function getGoogleData() 
    {

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

                    $lifetime = 7 * 24 * 60 * 60;

                    session_destroy();

                    $cookieParams = session_get_cookie_params();
                    session_set_cookie_params([
                        'lifetime' => $lifetime,
                        'path' => $cookieParams['path'],
                        'domain' => $cookieParams['domain'],
                        'secure' => $cookieParams['secure'],
                        'httponly' => $cookieParams['httponly'],
                        'domain' => 'ur-sg.com'
                    ]);

                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    if (!isset($_SESSION['googleId'])) 
                    {

                        
                        $_SESSION['google_userId'] = $testGoogleUser['google_userId'];
                        $_SESSION['full_name'] = $this->getGoogleFullName();
                        $_SESSION['google_id'] = $this->getGoogleId();
                        $_SESSION['email'] = $this->getGoogleEmail();
                        $_SESSION['google_firstName'] = $this->getGoogleFirstName();

                        $googleUser = $this->user->getUserDataByGoogleUserId($testGoogleUser['google_userId']);
                        if ($googleUser) {


                            $user = $this-> user -> getUserByUsername($googleUser['user_username']);

                            if ($user)
                            {

                                $_SESSION['userId'] = $user['user_id'];
                                $_SESSION['username'] = $user['user_username'];
                                $_SESSION['gender'] = $user['user_gender'];
                                $_SESSION['age'] = $user['user_age'];
                                $_SESSION['kindOfGamer'] = $user['user_kindOfGamer'];
                                $_SESSION['game'] = $user['user_game'];

                                $lolUser = $this->leagueoflegends->getLeageUserByUserId($user['user_id']);

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
;                                    }
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

                    $mail = new PHPMailer;
                    $mail->isSMTP();
                    $mail->Host = 'smtp.ionos.de';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'contact@ur-sg.com';
                    $mail->Password = $password_gmail;
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                
                    $mail->setFrom('contact@ur-sg.com', 'URSG.com');
                    $mail->addAddress($this->getGoogleEmail());
                    $mail->Subject = 'Confirm your email for URSG.com';
                    $mail->isHTML(true);
                
                    $boundary = md5(uniqid());
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'quoted-printable';
                
                    $mail->Body = "URSG.com - Email Confirmation\r\n";
                    $mail->Body .= "Your email: " . $this->getGoogleEmail() . "\r\n";
                    $mail->Body .= "Confirm your email by clicking on the link below:\r\n";
                    $mail->Body .= "Link: https://ur-sg.com/acceptConfirm?mail=" . $this->getGoogleEmail();
                
                    $mail->send();
                }
            }
        } else {
            $response = array(
                'message' => 'Contact an administrator', // No google data
            );
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    

        $response = array(
            'message' => 'Success',
            'newUser' => true,
            'googleUser' => $createGoogleUser,
        );
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } 

    public function logOut() {

        if($this->isConnectGoogle() || $this->isConnectWebsite()) {
            if (isset($_COOKIE['googleId'])) {
                setcookie('googleId', "", time() - 42000, COOKIEPATH);
            }
            unset($_COOKIE['googleId']);
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
        if(isset($_POST['email_confirm']))
        {
            $email = ($_POST['email_confirm']);
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@ur-sg.com';
            $mail->Password = $password_gmail;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
        
            $mail->setFrom('contact@ur-sg.com', 'URSG.com');
            $mail->addAddress($email);
            $mail->Subject = 'Confirm your email for URSG.com';
            $mail->isHTML(true);
        
            $boundary = md5(uniqid());
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
        
            $mail->Body = "URSG.com - Email Confirmation\r\n";
            $mail->Body .= "Your email: " . $email . "\r\n";
            $mail->Body .= "Confirm your email by clicking on the link below:\r\n";
            $mail->Body .= "Link: https://ur-sg.com/acceptConfirm?mail=" . $email;
        
            $mail->send();

            if (!$mail->send()) {
                header("location:/signup?message=Could not send mail");
                exit();
            } else {
                $this->confirmMailPage($mail);
            }

            // if (!$mail->send()) {
            //     echo 'Mailer Error: ' . $mail->ErrorInfo;
            // } else {
            //     echo 'Message sent!';
            // }
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
        
            $mail->setFrom('contact@ur-sg.com', 'URSG.com');
            $mail->addAddress($email);
            $mail->Subject = 'Confirm your email for URSG.com';
            $mail->isHTML(true);
        
            $boundary = md5(uniqid());
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
        
            $mail->Body = "URSG.com - Email Confirmation\r\n";
            $mail->Body .= "Your email: " . $email . "\r\n";
            $mail->Body .= "Confirm your email by clicking on the link below:\r\n";
            $mail->Body .= "Link: https://ur-sg.com/acceptConfirm?mail=" . $email;
        
            $mail->send();
            echo json_encode(['message' => 'Mail sent']);
            exit();

            if (!$mail->send()) {
                echo json_encode(['message' => "Mail couldn't be sent"]);
                exit();
            } 
        } 
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