<?php
session_start();

// USE CONTROLLERS
use controllers\UserController;
use controllers\GoogleUserController;
use controllers\LeagueOfLegendsController;
use controllers\UserLookingForController;
use controllers\FriendRequestController;
use controllers\ChatMessageController;
use controllers\BlockController;

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
$friendRequestController = new FriendRequestController();
$chatmessageController = new ChatMessageController();
$blockController = new BlockController();

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
            // Page looking for
            $userLookingForController->pageLookingFor();
                break;
        case "createLookingFor":
            // Handle Looking for League informations
            $userLookingForController->createLookingFor();
                break;
        case "swiping":
            // Page swiping
            $userController->pageswiping();
                break;
        case "userProfile":
            // Perso page of each user
            $userController->pageUserProfile();
                 break;
        case "anotherUser":
            // See a profile but not from main user
            $userController->pageAnotherUserProfile();
                break;
        case "updateProfilePage":
            // Page Update main data on profile
            $userController->pageUpdateProfile();
                break;
        case "updateProfile":
            // Change profile data
            $userController->UpdateProfile();
                break;
        case "updateLeaguePage":
            // Page Update main league data
            $leagueOfLegendsController->pageUpdateLeague();
                break;
        case "updateLeague":
            // Change league data
            $leagueOfLegendsController->UpdateLeague();
                break;
        case "updateLookingForPage":
            // Page Update looking for data
            $userLookingForController->pageUpdateLookingFor();
                break;
        case "updateLookingFor":
            // Change Looking for data
            $userLookingForController->UpdateLookingFor();
                break;
        case "updateSocial":
            // Update social data
            $userController->updateSocial();
                break;
        case "updatePicture":
            // Update picture
            $userController->updatePicture();
                break;
        case "requestAccepted":
            // Accept friend request
            $friendRequestController->acceptFriendRequest();
                break;
        case "requestRejected":
            // Refuse friend request
            $friendRequestController->rejectFriendRequest();
                break;
        case "friendlistPage":
            // Refuse friend request
            $friendRequestController->pageFriendlist();
                break;
        case "blockPerson":
            // Block someone
            $blockController->blockPerson();
                break;
        case "unblockPerson":
            // Unblock someone
            $blockController->unblockPerson();
                break;
            default;
        break;
    } 
}
else
{
    $googleUserController->homePage();
}