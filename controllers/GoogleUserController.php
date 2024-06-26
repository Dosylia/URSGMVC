<?php

namespace controllers;

use models\GoogleUser;
use models\User;
use models\LeagueOfLegends;
use traits\SecurityController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'plugins/PHPMailer/src/Exception.php';
require 'plugins/PHPMailer/src/PHPMailer.php';
require 'plugins/PHPMailer/src/SMTP.php';

class GoogleUserController
{
    use SecurityController;

    private GoogleUser $googleUser;
    private User $user;
    private LeagueOfLegends $leagueoflegends;
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
    }

    public function homePage() {

        if($this->isConnectGoogle())
        {
            $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);
        }

        if($this->isConnectWebsite())
        {
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
        }
        
        $template = "views/home";
        $title = "JOIN NOW";
        $page_title = "URSG - Home";
        require "views/layoutHome.phtml";
    }

    public function confirmMailPage() 
    {

        $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);

        if($googleUser['google_confirmEmail'] == 0 || $googleUser['google_confirmEmail'] == NULL)
        {
            $template = "views/waitingEmail";
            $title = "Confirm Mail";
            $page_title = "URSG - Confirm Mail";
            require "views/layoutHome.phtml";
        }
        else if($googleUser['google_confirmEmail'] == 1 && !$this->isConnectWebsite())
        {
            ob_start();
            header("Location: index.php?action=signup");
            exit();
        }
        else if($googleUser['google_confirmEmail'] == 1 && $this->isConnectWebsite())
        {
            ob_start();
            header("Location: index.php?action=signup");
            exit();
        }
        else
        {
            ob_start();
            header("Location: index.php?action=swippingmain");
            exit();
        }
    }

    public function pageSignUp()
    {

        $googleUser = $this->googleUser->getGoogleUserByEmail($_SESSION['email']);
        $secondTierUser = $this->user->getUserDataByGoogleId($_SESSION['google_userId']);

        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague()) {
            // Code block 1: User is connected via Google, Website and has League data, need looking for
            $lolUser = $this->leagueoflegends->getLeageUserByUsername($_SESSION['lol_account']);
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && $secondTierUser['user_game'] === "leagueoflegends") { 
            // Code block 2: User is connected via Google and username is set , but game settings not done. Redirect for LoL only
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $template = "views/signup/leagueoflegendsuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && $secondTierUser['user_game'] === "valorant") {
                // Code block 3: User is connected via Google and username is set , but game settings not done. Redirect for Valorant only
                $template = "views/signup/valorant";
                $title = "More about you";
                $page_title = "URSG - Sign up";
                require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !isset($googleUser['user_username']) && $secondTierUser['user_game'] === "both"){
                // Code block 4: User is connected via Google and username is set , but game settings not done. Redirect for both games
                $template = "views/signup/both";
                $title = "More about you";
                $page_title = "URSG - Sign up";
                require "views/layoutHome.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite()) {
            // Code block 5: User is connected via Google but doesn't have a username
            $template = "views/signup/basicinfo";
            $title = "Sign up";
            $page_title = "URSG - Sign";
            require "views/layoutHome.phtml";
        } else {
            // Code block 6: Redirect to index.php if none of the above conditions are met
            header("Location: index.php");
            exit();
        }
    }  

    public function getGoogleData() {

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
            $googleImageUrl = $googleData->imageUrl;
            $this->setGoogleImageUrl($googleImageUrl);  
            
            $testGoogleUser = $this->googleUser->userExist($this->getGoogleId());

            if($testGoogleUser) //CREATING SESSION IF USER EXISTS 
            {

                if (!$this->isConnectGoogle()) 
                {

                    $lifetime = 7 * 24 * 60 * 60;

                    session_destroy();

                    session_set_cookie_params($lifetime);

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

                        $googleUser = $this->user->getUserDataByGoogleId($this->getGoogleUserId());
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
                                    $_SESSION['lol_account'] = $lolUser['lol_account'];
                                }
                            }
                        }

                    }
    
                }

                $response = array(
                    'message' => 'Success',
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;

            }
            else // IF USER DOES NOT EXIST, INSERT IT INTO DATABASE
            {
                $createGoogleUser = $this->googleUser->createGoogleUser($this->getGoogleId(),$this->getGoogleFullName(),$this->getGoogleFirstName(),$this->getGoogleFamilyName(),$this->getGoogleEmail(),$this->getGoogleImageUrl());

                if($createGoogleUser) 
                {

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
                    $mail->Username = 'dosylia@ur-sg.com';
                    $mail->Password = 'Zangetsu_Serano1';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                
                    $mail->setFrom('dosylia@ur-sg.com', 'URSG.com');
                    $mail->addAddress($this->getGoogleEmail());
                    $mail->Subject = 'Confirm your email for URSG.com';
                    $mail->isHTML(true);
                
                    $boundary = md5(uniqid());
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'quoted-printable';
                
                    $mail->Body = "URSG.com - Email Confirmation\r\n";
                    $mail->Body .= "Your email: " . $this->getGoogleEmail() . "\r\n";
                    $mail->Body .= "Confirm your email by clicking on the link below:\r\n";
                    $mail->Body .= "Link: https://ur-sg.com/index.php?action=acceptConfirm&mail=" . $this->getGoogleEmail();
                
                    $mail->send();
                }
            }
        } 

        $response = array(
            'message' => 'Success',
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
            header("location:index.php?message=You are now offline");
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
                    header("location:index.php?action=signup&message=Email confirmed");
                    exit();                   
                }
                else 
                {
                    header("location:index.php?message=Couldnt confirm email");
                    exit();                    
                }
            }
            else
            {
                header("location:index.php?message=Email does not exists");
                exit();
            }

        }
    }  


    public function sendEmail() 
    {

        if(isset($_POST['email_confirm']))
        {
            $email = ($_POST['email_confirm']);

            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'dosylia@ur-sg.com';
            $mail->Password = 'Zangetsu_Serano1';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
        
            $mail->setFrom('dosylia@ur-sg.com', 'URSG.com');
            $mail->addAddress($email);
            $mail->Subject = 'Confirm your email for URSG.com';
            $mail->isHTML(true);
        
            $boundary = md5(uniqid());
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'quoted-printable';
        
            $mail->Body = "URSG.com - Email Confirmation\r\n";
            $mail->Body .= "Your email: " . $email . "\r\n";
            $mail->Body .= "Confirm your email by clicking on the link below:\r\n";
            $mail->Body .= "Link: https://ur-sg.com/index.php?action=acceptConfirm&mail=" . $email;
        
            $mail->send();

            $this->confirmMailPage();
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

    public function getGoogleImageUrl()
    {
        return $this->googleImageUrl;
    }

    public function setGoogleImageUrl($googleImageUrl)
    {
        $this->googleImageUrl = $googleImageUrl;
    }
}