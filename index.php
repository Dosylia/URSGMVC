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
use controllers\MatchingScoreController;

function loadClass($class)
{
    $classe = str_replace('\\', '/', $class);      
    require_once $classe . '.php'; 
}

spl_autoload_register('loadClass');

// Define a map of actions to controller classes and methods
$actionMap = [
    'home' => [GoogleUserController::class, 'homePage'],
    'logout' => [GoogleUserController::class, 'logOut'],
    'usepage' => [GoogleUserController::class, 'usePage'],
    'acceptConfirm' => [GoogleUserController::class, 'emailConfirmDb'],
    'confirmMail' => [GoogleUserController::class, 'confirmMailPage'],
    'newMail' => [GoogleUserController::class, 'sendEmail'],
    'googleTest' => [GoogleUserController::class, 'getGoogleData'],
    'signup' => [GoogleUserController::class, 'pageSignUp'],
    'basicinfo' => [UserController::class, 'createUser'],
    'leagueuser' => [LeagueOfLegendsController::class, 'pageLeagueUser'],
    'createleagueuser' => [LeagueOfLegendsController::class, 'createLeagueUser'],
    'lookingforuserlol' => [UserLookingForController::class, 'pageLookingFor'],
    'createLookingFor' => [UserLookingForController::class, 'createLookingFor'],
    'swiping' => [UserController::class, 'pageswiping'],
    'userProfile' => [UserController::class, 'pageUserProfile'],
    'anotherUser' => [UserController::class, 'pageAnotherUserProfile'],
    'updateProfilePage' => [UserController::class, 'pageUpdateProfile'],
    'updateProfile' => [UserController::class, 'UpdateProfile'],
    'updateLeaguePage' => [LeagueOfLegendsController::class, 'pageUpdateLeague'],
    'updateLeague' => [LeagueOfLegendsController::class, 'UpdateLeague'],
    'updateLookingForPage' => [UserLookingForController::class, 'pageUpdateLookingFor'],
    'updateLookingFor' => [UserLookingForController::class, 'UpdateLookingFor'],
    'updateSocial' => [UserController::class, 'updateSocial'],
    'updatePicture' => [UserController::class, 'updatePicture'],
    'requestAccepted' => [FriendRequestController::class, 'acceptFriendRequest'],
    'requestRejected' => [FriendRequestController::class, 'rejectFriendRequest'],
    'friendlistPage' => [FriendRequestController::class, 'pageFriendlist'],
    'blockPerson' => [BlockController::class, 'blockPerson'],
    'unblockPerson' => [BlockController::class, 'unblockPerson'],
    'swipeDone' => [FriendRequestController::class, 'swipeStatus'],
    'algoData' => [MatchingScoreController::class, 'getAlgoData'],
    'chat' => [ChatMessageController::class, 'pageChat'],
    'sendMessageData' => [ChatMessageController::class, 'sendMessageData'],
    'getMessageData' => [ChatMessageController::class, 'getMessageData'],
    'persoChat' => [ChatMessageController::class, 'pagePersoMessage'],
    'saveDarkMode' => [UserController::class, 'saveDarkMode'],
    'getUserMatching' => [UserController::class, 'getUserMatching'],
];

// Get the action from the request
$action = $_GET['action'] ?? 'home';

if (isset($actionMap[$action])) {
    [$controllerClass, $method] = $actionMap[$action];
    $controller = new $controllerClass();
    $controller->$method();
} else {
    http_response_code(404);
    echo "Page not found";
}
