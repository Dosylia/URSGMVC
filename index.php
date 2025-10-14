<?php

use Dotenv\Dotenv;
require 'vendor/autoload.php';

// Detect which env file to load
$env = getenv('APP_ENV') ?: 'local';
switch ($env) {
    case 'production':
        $envFile = '.env.prod';
        break;
    case 'development':
        $envFile = '.env.dev';
        break;
    default:
        $envFile = '.env.local';
        break;
};

$dotenv = Dotenv::createImmutable(__DIR__, $envFile);
$dotenv->load();

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
use controllers\GameController;
use controllers\AdminController;
use controllers\WebSocketController;
use controllers\DiscordController;
use controllers\PlayerFinderController;
use controllers\PaymentController;

function loadClass($class)
{
    $classe = str_replace('\\', '/', $class);      
    require_once $classe . '.php'; 
}

spl_autoload_register('loadClass');

// Define a map of actions to controller classes and methods
$actionMap = [
    'home' => [GoogleUserController::class, 'homePage'],
    'changeLanguage' => [GoogleUserController::class, 'changeLanguage'],
    'getAllUsersPhone' => [UserController::class, 'getAllUsersPhone'],
    'riotAccount' => [RiotController::class, 'riotAccount'],
    'riotAccountPhone' => [RiotController::class, 'riotAccountPhone'],
    'RiotCodePhone' => [RiotController::class, 'RiotCodePhone'],
    'getGameStatusLoL' => [RiotController::class, 'getGameStatusLoL'],
    'legalNotice' => [GoogleUserController::class, 'legalNoticePage'],
    'CSAE' => [GoogleUserController::class, 'CSAEPage'],
    'termsOfService' => [GoogleUserController::class, 'termsOfServicePage'],
    'siteMap' => [GoogleUserController::class, 'siteMapPage'],
    'logout' => [GoogleUserController::class, 'logOut'],
    'usepage' => [GoogleUserController::class, 'usePage'],
    'acceptConfirm' => [GoogleUserController::class, 'emailConfirmDb'],
    'confirmMail' => [GoogleUserController::class, 'confirmMailPage'],
    'newMail' => [GoogleUserController::class, 'sendEmail'],
    // 'newMailPhone' => [GoogleUserController::class, 'sendEmailPhone'],
    'googleTest' => [GoogleUserController::class, 'getGoogleData'],
    'googleDataPhone' => [GoogleUserController::class, 'getGoogleDataPhone'],
    'signup' => [GoogleUserController::class, 'pageSignUp'],
    'basicinfo' => [UserController::class, 'createUser'],
    'createAccountSkipPreferences' => [UserController::class, 'createAccountSkipPreferences'],
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
    // 'sendAccountToPhp' => [LeagueOfLegendsController::class, 'sendAccountToPhp'], No longer used
    'bindAccount' => [LeagueOfLegendsController::class, 'bindAccount'],
    'verifyLeagueAccount' => [LeagueOfLegendsController::class, 'verifyLeagueAccount'],
    'verifyLeagueAccountPhone' => [LeagueOfLegendsController::class, 'verifyLeagueAccountPhone'],
    'updateLookingForPage' => [UserLookingForController::class, 'pageUpdateLookingFor'],
    'updateLookingForGamePage' => [UserLookingForController::class, 'pageUpdateLookingForGame'],
    'settings' => [UserController::class, 'pageSettings'],
    'updateLookingFor' => [UserLookingForController::class, 'updateLookingFor'],
    'updateSocial' => [UserController::class, 'updateSocial'],
    'updateSocialsWebsite' => [UserController::class, 'updateSocialsWebsite'],
    'updateSocialPhone' => [UserController::class, 'updateSocialPhone'],
    'updatePicture' => [UserController::class, 'updatePicture'],
    'updatePicturePhone' => [UserController::class, 'updatePicturePhone'],
    'updateBonusPicturePhone' => [UserController::class, 'updateBonusPicturePhone'],
    'deleteBonusPicturePhone' => [UserController::class, 'deleteBonusPicturePhone'],
    'getUserData' => [UserController::class, 'getUserData'],
    'addBonusPicture' => [UserController::class, 'addBonusPicture'],
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
    'getCurrencyWebsite' => [UserController::class, 'getCurrencyWebsite'],
    'leaderboard' => [UserController::class, 'pageleaderboard'],
    'store' => [ItemsController::class, 'pageStore'],
    'getItems' => [ItemsController::class, 'getItems'],
    'buyItemPhone' => [ItemsController::class, 'buyItemPhone'],
    'buyRolePhone' => [ItemsController::class, 'buyRolePhone'],
    'buyItemWebsite' => [ItemsController::class, 'buyItemWebsite'],
    'buyRoleWebsite' => [ItemsController::class, 'buyRoleWebsite'],
    'getItemsWebsite' => [ItemsController::class, 'getItemsWebsite'],
    'getOwnedItems' => [ItemsController::class, 'getOwnedItems'],
    'getOwnedItemsPhone' => [ItemsController::class, 'getOwnedItemsPhone'],
    'ownVIPEmotesPhone' => [ItemsController::class, 'ownVIPEmotesPhone'],
    'Phone' => [ItemsController::class, 'getOwnedItemsPhone'], // Remove when mobile app update is out
    'usePictureFrame' => [ItemsController::class, 'usePictureFrame'],
    'usePictureFrameWebsite' => [ItemsController::class, 'usePictureFrameWebsite'],
    'removePictureFrame' => [ItemsController::class, 'removePictureFrame'],
    'removePictureFrameWebsite' => [ItemsController::class, 'removePictureFrameWebsite'],
    'getUnreadMessagePhone' => [ChatMessageController::class, 'getUnreadMessagePhone'],
    'getUnreadMessageWebsite' => [ChatMessageController::class, 'getUnreadMessageWebsite'],
    'markMessageAsReadWebsite' => [ChatMessageController::class, 'markMessageAsReadWebsite'],
    'deleteMessageWebsite' => [ChatMessageController::class, 'deleteMessageWebsite'],
    'getAcceptedFriendRequestWebsite' => [FriendRequestController::class, 'getAcceptedFriendRequestWebsite'],
    'updateNotificationFriendRequestAcceptedWebsite' => [FriendRequestController::class, 'updateNotificationFriendRequestAcceptedWebsite'],
    'updateNotificationFriendRequestPendingWebsite' => [FriendRequestController::class, 'updateNotificationFriendRequestPendingWebsite'],
    'getFriendRequest' => [FriendRequestController::class, 'getFriendRequest'],
    'getFriendRequestPhone' => [FriendRequestController::class, 'getFriendRequestPhone'],
    'getFriendRequestReact' => [FriendRequestController::class, 'getFriendRequestReact'],
    'getFriendRequestWebsite' => [FriendRequestController::class, 'getFriendRequestWebsite'],
    'registerToken' => [UserController::class, 'registerToken'],
    'arcaneSide' => [UserController::class, 'arcaneSide'],
    'arcaneSideWebsite' => [UserController::class, 'arcaneSideWebsite'],
    'chatFilterSwitch' => [UserController::class, 'chatFilterSwitch'],
    'chatFilterSwitchWebsite' => [UserController::class, 'chatFilterSwitchWebsite'],
    'deleteAccount' => [GoogleUserController::class, 'deleteAccountPage'],
    'deleteGoogleAccount' => [GoogleUserController::class, 'deleteGoogleAccount'],
    'deleteRiotAccount' => [GoogleUserController::class, 'deleteRiotAccount'],
    'deleteAccountConfirm' => [GoogleUserController::class, 'deleteAccountConfirm'],
    'refreshRiotData' => [LeagueOfLegendsController::class, 'refreshRiotData'],
    'deleteOldMessage' => [ChatMessageController::class, 'deleteOldMessage'],
    'uploadChatImage' => [ChatMessageController::class, 'uploadChatImage'],
    'uploadChatImagePhone' => [ChatMessageController::class, 'uploadChatImagePhone'],
    'deleteChatImage' => [ChatMessageController::class, 'deleteChatImage'],
    'getAllQueuedNotification' => [ChatMessageController::class, 'getAllQueuedNotification'],
    'deleteFriendRequestAfterWeek' => [FriendRequestController::class, 'deleteFriendRequestAfterWeek'],
    'updateFriendWebsite' => [FriendRequestController::class, 'updateFriendWebsite'],
    'addAsFriendWebsite' => [FriendRequestController::class, 'addAsFriendWebsite'],
    'signUpBypass' => [GoogleUserController::class, 'signUpBypass'],
    'notFound' => [GoogleUserController::class, 'notFoundPage'],
    'unsubscribeMails' => [GoogleUserController::class, 'unsubscribeMails'],
    'reportUserWebsite' => [UserController::class, 'reportUserWebsite'],
    'reportUserPhone' => [UserController::class, 'reportUserPhone'],
    'userIsLookingForGameWebsite' => [UserController::class, 'userIsLookingForGameWebsite'],
    'userIsLookingForGamePhone' => [UserController::class, 'userIsLookingForGamePhone'],
    'getGameUser' => [GameController::class, 'getGameUser'],
    'submitGuess' => [GameController::class, 'submitGuess'],
    'admin' => [AdminController::class, 'adminLandingPage'],
    'adminUsers' => [AdminController::class, 'adminUsersPage'],
    'adminUpdateCurrency' => [AdminController::class, 'adminUpdateCurrency'],
    'adminBanUser' => [AdminController::class, 'adminBanUser'],
    'adminAddPartner' => [AdminController::class, 'adminAddPartner'],
    'adminRemovePartner' => [AdminController::class, 'adminRemovePartner'],
    'adminCensorBio' => [AdminController::class, 'adminCensorBio'],
    'adminRemovePartnerFromPage' => [AdminController::class, 'adminRemovePartnerFromPage'],
    'adminAddPartnerFromPage' => [AdminController::class, 'adminAddPartnerFromPage'],
    'adminCensorPicture' => [AdminController::class, 'adminCensorPicture'],
    'adminGame' => [AdminController::class, 'adminGamePage'],
    'addCharGame' => [AdminController::class, 'addCharGame'],
    'adminReports' => [AdminController::class, 'adminReportsPage'],
    'reportAdminBanUser' => [AdminController::class, 'reportAdminBanUser'],
    'reportAdminCensorBio' => [AdminController::class, 'reportAdminCensorBio'],
    'reportAdminCensorPicture' => [AdminController::class, 'reportAdminCensorPicture'],
    'reportAdminRequestBan' => [AdminController::class, 'reportAdminRequestBan'],
    'reportAdminCensorBoth' => [AdminController::class, 'reportAdminCensorBoth'],
    'reportAdminDismiss' => [AdminController::class, 'reportAdminDismiss'],
    'adminPartnerPage' => [AdminController::class, 'adminPartnerPage'],
    // 'userTest' => [UserController::class, 'pageUserProfileTest'],
    'deleteBonusPicture' => [UserController::class, 'deleteBonusPicture'],
    'personalityTest' => [UserController::class, 'personalityTestPage'],
    'savePersonalityTestResult' => [UserController::class, 'savePersonalityTestResult'],
    'getMatchingPersonalityUser' => [UserController::class, 'getMatchingPersonalityUser'],
    'getPersonalityTestResult' => [UserController::class, 'getPersonalityTestResult'],
    'createChannel' => [DiscordController::class, 'createChannel'],
    'discordData' => [DiscordController::class, 'discordData'],
    'deleteExpiredChannels' => [DiscordController::class, 'deleteExpiredChannels'],
    'updateNotificationPermission' => [UserController::class, 'updateNotificationPermission'],
    'saveNotificationSubscription' => [UserController::class, 'saveNotificationSubscription'],
    'fetchNotificationEndpoint' => [UserController::class, 'fetchNotificationEndpoint'],
    'sendMessageDiscord' => [DiscordController::class, 'sendMessageDiscord'],
    'sendMessageDiscordPhone' => [DiscordController::class, 'sendMessageDiscordPhone'],
    'playerfinder' => [PlayerFinderController::class, 'playerfinderPage'],
    'addPlayerFinderPost' => [PlayerFinderController::class, 'addPlayerFinderPost'],
    'deletePlayerFinderPost' => [PlayerFinderController::class, 'deletePlayerFinderPost'],
    'playWithThem' => [PlayerFinderController::class, 'playWithThem'],    
    'getInterestedPeople' => [PlayerFinderController::class, 'getInterestedPeople'],
    'addPlayerFinderPostPhone' => [PlayerFinderController::class, 'addPlayerFinderPostPhone'],
    'getPlayerFinderPostsPhone' => [PlayerFinderController::class, 'getPlayerFinderPostsPhone'],
    'deletePlayerFinderPostPhone' => [PlayerFinderController::class, 'deletePlayerFinderPostPhone'],
    'playWithThemPhone' => [PlayerFinderController::class, 'playWithThemPhone'],    
    'getInterestedPeoplePhone' => [PlayerFinderController::class, 'getInterestedPeoplePhone'],
    'markInterestAsSeen' => [PlayerFinderController::class, 'markInterestAsSeen'],
    'addFriendAndChat' => [FriendRequestController::class, 'addFriendAndChat'],
    'addFriendAndChatPhone' => [FriendRequestController::class, 'addFriendAndChatPhone'],
    'editPlayerPost' => [PlayerFinderController::class, 'editPlayerPost'],
    'partners' => [GoogleUserController::class, 'partnersPage'],
    'markInactiveUsersOffline' => [UserController::class, 'markInactiveUsersOffline'],
    'discordClaim' => [DiscordController::class, 'discordClaim'],
    'getLeaderboardUsers' => [UserController::class, 'getLeaderboardUsers'],
    'unbindLoLAccount' => [LeagueOfLegendsController::class, 'unbindLoLAccount'],
    'adminDiscordBot' => [AdminController::class, 'adminDiscordBotPage'],
    'discordBotControl' => [AdminController::class, 'discordBotControl'],
    'discordBotCommand' => [AdminController::class, 'discordBotCommand'],
    'discordBotStatus' => [AdminController::class, 'discordBotStatus'],
    'checkIfUsersPlayedTogether' => [RiotController::class, 'checkIfUsersPlayedTogether'],
    'rateFriendWebsite' => [UserController::class, 'rateFriendWebsite'],
    'hiring' => [GoogleUserController::class, 'hiringPage'],
    'submitCandidature' => [GoogleUserController::class, 'submitCandidature'],
    'startBotCronJob' => [DiscordController::class, 'startBotCronJob'],
    'MailingCronJob' => [GoogleUserController::class, 'MailingCronJob'],
    'adminStore' => [AdminController::class, 'adminStorePage'],
    'useBadgeWebsite' => [ItemsController::class, 'useBadgeWebsite'],
    'removeBadgeWebsite' => [ItemsController::class, 'removeBadgeWebsite'],
    'adminGrantItem' => [AdminController::class, 'adminGrantItem'],
    'adminRemoveItem' => [AdminController::class, 'adminRemoveItem'],
    'connectRiotMobile' => [RiotController::class, 'connectRiotMobile'],
    'connectDiscordMobile' => [DiscordController::class, 'connectDiscordMobile'],
    'discordBind' => [DiscordController::class, 'discordBind'],
    'buyCurrencyWebsite' => [PaymentController::class, 'buyCurrencyWebsite'],
    'paymentSuccess' => [PaymentController::class, 'paymentSuccess'],
    'paymentCancel' => [PaymentController::class, 'paymentCancel'],
    'handleWebhook' => [PaymentController::class, 'handleWebhook'],
    'buyPremiumBoostWebsite' => [PaymentController::class, 'buyPremiumBoostWebsite'],
    'switchPersonalColorWebsite' => [UserController::class, 'switchPersonalColorWebsite'],
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
