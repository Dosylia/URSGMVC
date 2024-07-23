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
    'legalNotice' => [GoogleUserController::class, 'legalNoticePage'],
    'siteMap' => [GoogleUserController::class, 'siteMapPage'],
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
    'updateLeagueAccount' => [LeagueOfLegendsController::class, 'pageUpdateLeagueAccount'],
    'sendAccountToPhp' => [LeagueOfLegendsController::class, 'sendAccountToPhp'],
    'verifyLeagueAccount' => [LeagueOfLegendsController::class, 'verifyLeagueAccount'],
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
    'sendMessageData' => [ChatMessageController::class, 'sendMessageData'],
    'getMessageData' => [ChatMessageController::class, 'getMessageData'],
    'persoChat' => [ChatMessageController::class, 'pagePersoMessage'],
    'getUserMatching' => [UserController::class, 'getUserMatching'],
    'getUnreadMessage' => [ChatMessageController::class, 'getUnreadMessage'],
    'getFriendRequest' => [FriendRequestController::class, 'getFriendRequest'],
    'refreshRiotData' => [LeagueOfLegendsController::class, 'refreshRiotData'],
    'deleteOldMessage' => [ChatMessageController::class, 'deleteOldMessage'],
    'deleteFriendRequestAfterWeek' => [FriendRequestController::class, 'deleteFriendRequestAfterWeek'],
    'notFound' => [GoogleUserController::class, 'notFoundPage'],
];

    $action = "home";


    if (isset($_GET['action'])) {
        $action = $_GET['action'];
    } else {
        $delimiter = "&";
        $URL = $_SERVER['REQUEST_URI'];
        $parsedURL = parse_url($URL);
        $path = $parsedURL['path'] ?? '';
        $query = $parsedURL['query'] ?? '';
        
        if ($path == '/' || $path == '') {
        } else {
            if (strpos($path, $delimiter) === false) {
                $result = str_replace(['/', '?'], '', $path);
                $action = htmlspecialchars($result);
            } else {
                $pos = strpos($path, $delimiter);
                $action = substr($path, 0, $pos);
                $action = str_replace(['/', '?'], '', $action);
                $action = htmlspecialchars($action);
            }
        }
    }

if (isset($actionMap[$action])) {
    [$controllerClass, $method] = $actionMap[$action];
    $controller = new $controllerClass();
    $controller->$method();
} else {
    [$controllerClass, $method] = $actionMap['notFound'];
    $controller = new $controllerClass();
    $controller->$method();
}
