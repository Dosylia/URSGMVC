<?php
session_start();

// USE CONTROLLERS
use controllers\UserController;
use controllers\GoogleUserController;
use controllers\LeagueOfLegendsController;
use controllers\UserLookingForController;

function loadClass($class)
{
    $classe=str_replace('\\','/',$class);      
    require $classe.'.php'; 
}

spl_autoload_register('loadClass');

// NEW INSTANCE OF EACH CONTROLLER
$userController = new UserController();
$googleUserController = new GoogleUserController();
$leagueOfLegendsController = new LeagueOfLegendsController();
$userLookingForController = new UserLookingForController();


// IF AND SWITCH CASE

if (isset($_GET['action']))
{

    switch($_GET['action'])
    {
        case "home":
            //MAIN PAGE
            $googleUserController->homePage();
            break;
        case "logout":
            $googleUserController->logOut();
            break;
        case "usepage":
            // USER PAGE
            break;
        case "acceptConfirm":
            // Confirm the email of user in database
            $googleUserController->emailConfirmDb();
            break;
        case "confirmMail":
            // Confirm email
            $googleUserController->confirmMailPage();
            break;
        case "newMail":
                // Send new mail
            $googleUserController->sendEmail();
            break;
        case "googleTest":
            // test Ajex
            $googleUserController->getGoogleData();
            break;
        case "signup":
            // Show first page
            $googleUserController->pageSignUp();
            break;
        case "basicinfo":
            // Handle what first page sends back
            $userController->createUser();
            break;
        case "leagueuser":
            // Page of sign up for league
            $leagueOfLegendsController->pageLeagueUser();
             break;
        case "createleagueuser":
            // Handle League informations
            $leagueOfLegendsController->createLeagueUser();
                break;
        case "lookingforuserlol":
            // Handle Looking for League informations
            $userLookingForController->pageLookingFor();
                break;
        default;
        break;
    } 
}
else
{
    $googleUserController->homePage();
}