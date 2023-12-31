<?php

namespace controllers;

use models\GoogleUser;
use models\User;
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
    }

    public function homePage() {

        if($this->isConnectGoogle())
        {
            $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);
        }

        if($this->isConnectWebsite())
        {
            $googleUser = $this-> user -> getUserByUsername($_SESSION['username']);
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
        else
        {
            ob_start();
            header("Location: index.php?action=swippingmain");
            exit();
        }
    }

    public function pageSignUp()
    {

        $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);
        
        if($this->isConnectGoogle() && !isset($googleUser['user_username']))  //ADD LATER THIS SECURITE
        {   
            $template = "views/signup/basicinfo";
            $title = "Sign up";
            $page_title = "URSG - Sign";
            require "views/layoutHome.phtml";
        }
        else
        {
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

                if (!$this->isConnectGoogle()) {

                    $lifetime = 7 * 24 * 60 * 60;

                    session_destroy();

                    session_set_cookie_params($lifetime);

                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    if (!isset($_SESSION['googleId'])) {
                        $_SESSION['google_userId'] = $testGoogleUser['google_userId'];
                        $_SESSION['full_name'] = $this->getGoogleFullName();
                        $_SESSION['google_id'] = $this->getGoogleId();
                        $_SESSION['email'] = $this->getGoogleEmail();
                        $_SESSION['google_firstName'] = $this->getGoogleFirstName();
                    }
    
                }

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