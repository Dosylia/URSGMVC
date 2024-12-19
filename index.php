<?php

// Set session cookie parameters
$cookieParams = session_get_cookie_params();
$lifetime = 7 * 24 * 60 * 60;

session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => true,  
    'httponly' => true,
    'samesite' => 'None' 
]);

// Start the session
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
use controllers\ItemsController;
use controllers\ValorantController;
use controllers\RiotController;

function loadClass($class)
{
    $classe = str_replace('\\', '/', $class);      
    require_once $classe . '.php'; 
}

spl_autoload_register('loadClass');

// Define a map of actions to controller classes and methods
$actionMap = [
    'home' => [GoogleUserController::class, 'homePage'],
    'getAllUsersPhone' => [UserController::class, 'getAllUsersPhone'],
    'riotAccount' => [RiotController::class, 'riotAccount'],
    'riotAccountPhone' => [RiotController::class, 'riotAccountPhone'],
    'RiotCodePhone' => [RiotController::class, 'RiotCodePhone'],
    'legalNotice' => [GoogleUserController::class, 'legalNoticePage'],
    'termsOfService' => [GoogleUserController::class, 'termsOfServicePage'],
    'siteMap' => [GoogleUserController::class, 'siteMapPage'],
    'logout' => [GoogleUserController::class, 'logOut'],
    'usepage' => [GoogleUserController::class, 'usePage'],
    'acceptConfirm' => [GoogleUserController::class, 'emailConfirmDb'],
    'confirmMail' => [GoogleUserController::class, 'confirmMailPage'],
    'newMail' => [GoogleUserController::class, 'sendEmail'],
    'newMailPhone' => [GoogleUserController::class, 'sendEmailPhone'],
    'googleTest' => [GoogleUserController::class, 'getGoogleData'],
    'googleDataPhone' => [GoogleUserController::class, 'getGoogleDataPhone'],
    'signup' => [GoogleUserController::class, 'pageSignUp'],
    'basicinfo' => [UserController::class, 'createUser'],
    'createUserPhone' => [UserController::class, 'createUserPhone'],
    'leagueuser' => [LeagueOfLegendsController::class, 'pageLeagueUser'],
    'valorantuser' => [ValorantController::class, 'pageValorantUser'],
    'createvalorantuser' => [ValorantController::class, 'createValorantUser'],
    'createleagueuser' => [LeagueOfLegendsController::class, 'createLeagueUser'],
    'createValorantUserPhone' => [ValorantController::class, 'createValorantUserPhone'],
    'createLeagueUserPhone' => [LeagueOfLegendsController::class, 'createLeagueUserPhone'],
    'lookingforuserlol' => [UserLookingForController::class, 'pageLookingFor'],
    'lookingforuservalorant' => [UserLookingForController::class, 'pageLookingForValorant'],
    'createLookingFor' => [UserLookingForController::class, 'createLookingFor'],
    'createLookingForUserPhone' => [UserLookingForController::class, 'createLookingForUserPhone'],
    'swiping' => [UserController::class, 'pageswiping'],
    'userProfile' => [UserController::class, 'pageUserProfile'],
    'updateUserPhone' => [UserController::class, 'updateUserPhone'],
    'anotherUser' => [UserController::class, 'pageAnotherUserProfile'],
    'updateProfilePage' => [UserController::class, 'pageUpdateProfile'],
    'updateProfile' => [UserController::class, 'UpdateProfile'],
    'updateValorantPage' => [ValorantController::class, 'pageUpdateValorant'],
    'updateValorant' => [ValorantController::class, 'UpdateValorant'],
    'updateLeaguePage' => [LeagueOfLegendsController::class, 'pageUpdateLeague'],
    'updateLeague' => [LeagueOfLegendsController::class, 'UpdateLeague'],
    'updateLeagueAccount' => [LeagueOfLegendsController::class, 'pageUpdateLeagueAccount'],
    'sendAccountToPhp' => [LeagueOfLegendsController::class, 'sendAccountToPhp'],
    'bindAccount' => [LeagueOfLegendsController::class, 'bindAccount'],
    'verifyLeagueAccount' => [LeagueOfLegendsController::class, 'verifyLeagueAccount'],
    'verifyLeagueAccountPhone' => [LeagueOfLegendsController::class, 'verifyLeagueAccountPhone'],
    'updateLookingForPage' => [UserLookingForController::class, 'pageUpdateLookingFor'],
    'updateLookingForGamePage' => [UserLookingForController::class, 'pageUpdateLookingForGame'],
    'settings' => [UserController::class, 'pageSettings'],
    'updateLookingFor' => [UserLookingForController::class, 'updateLookingFor'],
    'updateSocial' => [UserController::class, 'updateSocial'],
    'updateSocialPhone' => [UserController::class, 'updateSocialPhone'],
    'updatePicture' => [UserController::class, 'updatePicture'],
    'updatePicturePhone' => [UserController::class, 'updatePicturePhone'],
    'getUserData' => [UserController::class, 'getUserData'],
    'friendlistPage' => [FriendRequestController::class, 'pageFriendlist'],
    'blockPerson' => [BlockController::class, 'blockPerson'],
    'unfriendPerson' => [FriendRequestController::class, 'unfriendPerson'],
    'unfriendPersonPhone' => [FriendRequestController::class, 'unfriendPersonPhone'],
    'blockPersonPhone' => [BlockController::class, 'blockPersonPhone'],
    'unblockPerson' => [BlockController::class, 'unblockPerson'],
    'swipeDone' => [FriendRequestController::class, 'swipeStatus'],
    'swipeDoneWebsite' => [FriendRequestController::class, 'swipeStatusWebsite'],
    'swipeDonePhone' => [FriendRequestController::class, 'swipeStatusPhone'],
    'algoData' => [MatchingScoreController::class, 'getAlgoData'],
    'sendMessageDataWebsite' => [ChatMessageController::class, 'sendMessageDataWebsite'],
    'sendMessageDataPhone' => [ChatMessageController::class, 'sendMessageDataPhone'],
    'getMessageDataWebsite' => [ChatMessageController::class, 'getMessageDataWebsite'],
    'getMessageDataPhone' => [ChatMessageController::class, 'getMessageDataPhone'],
    'getFriendlist' => [FriendRequestController::class, 'getFriendlist'],
    'getFriendlistWebsite' => [FriendRequestController::class, 'getFriendlistWebsite'],
    'getFriendlistPhone' => [FriendRequestController::class, 'getFriendlistPhone'],
    'acceptFriendRequestPhone' => [FriendRequestController::class, 'acceptFriendRequestPhone'],
    'refuseFriendRequestPhone' => [FriendRequestController::class, 'refuseFriendRequestPhone'],
    'persoChat' => [ChatMessageController::class, 'pagePersoMessage'],
    'getUserMatching' => [UserController::class, 'getUserMatching'],
    'getCurrency' => [UserController::class, 'getCurrency'],
    'leaderboard' => [UserController::class, 'pageleaderboard'],
    'store' => [ItemsController::class, 'pageStore'],
    'getItems' => [ItemsController::class, 'getItems'],
    'buyItemPhone' => [ItemsController::class, 'buyItemPhone'],
    'buyRolePhone' => [ItemsController::class, 'buyRolePhone'],
    'buyItemWebsite' => [ItemsController::class, 'buyItemWebsite'],
    'buyRoleWebsite' => [ItemsController::class, 'buyRoleWebsite'],
    'getItemsWebsite' => [ItemsController::class, 'getItemsWebsite'],
    'getOwnedItems' => [ItemsController::class, 'getOwnedItems'],
    'usePictureFrame' => [ItemsController::class, 'usePictureFrame'],
    'usePictureFrameWebsite' => [ItemsController::class, 'usePictureFrameWebsite'],
    'removePictureFrame' => [ItemsController::class, 'removePictureFrame'],
    'removePictureFrameWebsite' => [ItemsController::class, 'removePictureFrameWebsite'],
    'getUnreadMessagePhone' => [ChatMessageController::class, 'getUnreadMessagePhone'],
    'getUnreadMessageWebsite' => [ChatMessageController::class, 'getUnreadMessageWebsite'],
    'getFriendRequest' => [FriendRequestController::class, 'getFriendRequest'],
    'getFriendRequestPhone' => [FriendRequestController::class, 'getFriendRequestPhone'], // Add security on that one for app
    'getFriendRequestReact' => [FriendRequestController::class, 'getFriendRequestReact'],
    'getFriendRequestWebsite' => [FriendRequestController::class, 'getFriendRequestWebsite'],
    'registerToken' => [UserController::class, 'registerToken'],
    'arcaneSide' => [UserController::class, 'arcaneSide'],
    'arcaneSideWebsite' => [UserController::class, 'arcaneSideWebsite'],
    'chatFilterSwitch' => [UserController::class, 'chatFilterSwitch'],
    'chatFilterSwitchWebsite' => [UserController::class, 'chatFilterSwitchWebsite'],
    'deleteAccount' => [GoogleUserController::class, 'deleteAccountPage'],
    'deleteAccountRequest' => [GoogleUserController::class, 'deleteAccountRequest'],
    'deleteAccountConfirm' => [GoogleUserController::class, 'deleteAccountConfirm'],
    'refreshRiotData' => [LeagueOfLegendsController::class, 'refreshRiotData'],
    'deleteOldMessage' => [ChatMessageController::class, 'deleteOldMessage'],
    'deleteFriendRequestAfterWeek' => [FriendRequestController::class, 'deleteFriendRequestAfterWeek'],
    'updateFriendWebsite' => [FriendRequestController::class, 'updateFriendWebsite'],
    'signUpBypass' => [GoogleUserController::class, 'signUpBypass'],
    'notFound' => [GoogleUserController::class, 'notFoundPage'],
    'reportUserWebsite' => [UserController::class, 'reportUserWebsite'],
    'reportUserPhone' => [UserController::class, 'reportUserPhone'],
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
    http_response_code(404);
    [$controllerClass, $method] = $actionMap['notFound'];
    $controller = new $controllerClass();
    $controller->$method();
}
