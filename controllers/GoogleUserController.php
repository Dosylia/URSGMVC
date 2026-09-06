<?php

namespace controllers;

use models\GoogleUser;
use models\User;
use models\UserGames;
use models\Games;
use models\UserLookingForGames;
use models\Partners;
use models\BannedUsers;
use models\PlayerFinder;
use models\ChatMessage;
use models\FriendRequest;
use enums\GameSlug;
use traits\SecurityController;
use traits\Translatable;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Google_Client;
use services\LogInService;
use services\SignUpService;
use services\MasterTokenService;
use services\LoginDestination;
use services\RoutingService;
use traits\PageRenderer;

require 'vendor/autoload.php';

class GoogleUserController
{
    use SecurityController;
    use Translatable;
    use PageRenderer;

    private GoogleUser $googleUser;
    private RoutingService $routingService;
    private User $user;
    private UserGames $userGames;
    private Games $games;
    private UserLookingForGames $userlookingforgames;
    private Partners $partners;
    private BannedUsers $bannedusers;
    private PlayerFinder $playerFinder;
    private ChatMessage $chatmessage;
    private FriendRequest $friendrequest;
    private LogInService $logInService;
    private SignUpService $signUpService;
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
        $this -> routingService = new RoutingService();
        $this -> user = new User();
        $this -> userGames = new UserGames();
        $this -> games = new Games();
        $this -> userlookingforgames = new UserLookingForGames();
        $this -> partners = new Partners();
        $this -> bannedusers = new BannedUsers();
        $this -> playerFinder = new PlayerFinder();
        $this->chatmessage = new ChatMessage();
        $this->friendrequest = new FriendRequest();
        $masterTokenService = new MasterTokenService($this->googleUser);
        $this->logInService = new LogInService(
            $masterTokenService,
            $this->user,
            $this->userGames,
            $this->games,
            $this->userlookingforgames
        );
        $this->signUpService = new SignUpService($this->googleUser, $masterTokenService);
    }

    public function homePage() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        ) {
            if (isset($_GET['message'])) {
                $message = $_GET['message'];
                header("location:/swiping"."?message=$message");
                return;
            } else {
                header("location:/swiping");
                return;
            }
        } else {
            if($this->isConnectGoogle())
            {
                $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);
            }
    
            if($this->isConnectWebsite())
            {
                $user = $this-> user -> getUserByUsername($_SESSION['username']);
            }

            $reconnectUser = $this->restoreSessionFromToken();

            if ($reconnectUser) {
                header("location:/swiping");
                return;
            }

            $this->initializeLanguage();

            require 'keys.php';
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill"];
            $valorant_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant"];
            $valorant_roles = ["Controller", "Duelist", "Initiator", "Sentinel", "Fill"];
            $regionAbbreviations = [
                "Europe West" => "EUW",
                "North America" => "NA",
                "Europe Nordic & East" => "EUNE",
                "Brazil" => "BR",
                "Latin America North" => "LAN",
                "Latin America South" => "LAS",
                "Oceania" => "OCE",
                "Russia" => "RU",
                "Turkey" => "TR",
                "Japan" => "JP",
                "Korea" => "KR",
            ];

            $availableRoles = [
                'League of Legends' => array_merge(['Any'], $lol_roles),
                'Valorant' => array_merge(['Any'], $valorant_roles)
            ];

            $availableRanks = [
                'League of Legends' => array_merge(['Any'], $lol_ranks),
                'Valorant' => array_merge(['Any'], $valorant_ranks)
            ];
            $playerFinderLasts = $this->playerFinder->getPlayerFinderLasts();
            if (is_array($playerFinderLasts) && count($playerFinderLasts) > 0) {
                $totalPosts = count($playerFinderLasts);
            } else {
                $totalPosts = 0;
            }
            $visibleCards = 3;
            $centerStart = max(0, floor(($totalPosts - $visibleCards) / 2));
            $centerEnd = $centerStart + $visibleCards - 1;
            $this->renderPage(
                layout: 'views/layoutHome.phtml',
                template: 'views/home',
                current_url: 'https://ur-sg.com/',
                page_title: 'URSG - Home',
                picture: 'ursg-preview-small',
                title: $this->_('join_now'),
                page_css: ['playerfinder', 'home'],
                data: [
                    'lol_ranks' => $lol_ranks,
                    'lol_roles' => $lol_roles,
                    'valorant_ranks' => $valorant_ranks,
                    'valorant_roles' => $valorant_roles,
                    'regionAbbreviations' => $regionAbbreviations,
                    'availableRoles' => $availableRoles,
                    'availableRanks' => $availableRanks,
                    'playerFinderLasts' => $playerFinderLasts,
                    'totalPosts' => $totalPosts,
                    'visibleCards' => $visibleCards,
                    'centerStart' => $centerStart,
                    'centerEnd' => $centerEnd,
                    'googleUser' => $googleUser ?? null,
                    'user' => $user ?? null,
                ],
            );
        }
    }

    public function partnersPage()
    {
        $this->initializeLanguage();
        $partners = $this -> partners -> getPartners();

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/partners',
            current_url: 'https://ur-sg.com/partners',
            page_title: 'URSG - Partners',
            picture: 'ursg-preview-small',
            title: 'Partners',
            page_css: ['partner'],
            data: ['partners' => $partners, 'user' => $user],
        );
    }


    public function hiringPage()
    {
        $this->initializeLanguage();

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/hiring',
            current_url: 'https://ur-sg.com/hiring',
            page_title: 'URSG - Apply to the team',
            picture: 'ursg-preview-small',
            title: 'Apply to the team',
            page_css: ['hiring'],
            data: ['user' => $user],
        );
    }


    public function changeLanguage()
    {
        $allowedLangs = ['en', 'fr', 'de', 'es'];
        if (isset($_POST['lang']) && in_array($_POST['lang'], $allowedLangs)) {
            $lang = $_POST['lang'];

            setcookie('lang', $lang, time() + (7 * 24 * 60 * 60), "/");
            $_SESSION['lang'] = $lang;

            // Load the new language immediately
            $this->loadLanguage($lang);
            $message = $this->_('switched_language', ['lang' => $lang]);
            header("Location: /?message=" . urlencode($message));
            return;
        }
    }

    public function restoreSessionFromToken()
    {
        if (isset($_COOKIE['auth_token']) && !$this->isConnectGoogle()) {
            $token = $_COOKIE['auth_token'];
            $token = strval($token);

            // Get Google user by token
            $testGoogleUser = $this->googleUser->getGoogleUserByMasterTokenWebsite($token);
            if (!$testGoogleUser) {
                return false;
            }

            // Set session and cookie
            $_SESSION['google_userId'] = $testGoogleUser['google_userId'];
            $_SESSION['full_name'] = $testGoogleUser['google_fullName'];
            $_SESSION['google_id'] = $testGoogleUser['google_id'];
            $_SESSION['email'] = $testGoogleUser['google_email'];
            $_SESSION['google_firstName'] = $testGoogleUser['google_firstName'];
            $_SESSION['masterTokenWebsite'] = $token;

            // Update cookie expiration
            setcookie("auth_token", $token, [
                'expires' => time() + 60 * 60 * 24 * 60,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            // Continue restoring user info
            $googleUser = $this->user->getUserDataByGoogleUserId($testGoogleUser['google_userId']);
            if ($googleUser) {
                $user = $this->user->getUserByUsername($googleUser['user_username']);
                if ($user) {
                    $_SESSION['userId'] = $user['user_id'];
                    $_SESSION['username'] = $user['user_username'];
                    $_SESSION['gender'] = $user['user_gender'];
                    $_SESSION['age'] = $user['user_age'];
                    $_SESSION['kindOfGamer'] = $user['user_kindOfGamer'];
                    $_SESSION['game'] = $user['user_game'];

                    $gameId = $this->games->getIdBySlug($user['game_slug'] ?? '');
                    $userGame = $gameId ? $this->userGames->getUserGameByUserIdAndGameId($user['user_id'], $gameId) : false;

                    if ($userGame) {
                        match ($user['game_slug']) {
                            GameSlug::LEAGUE_OF_LEGENDS->value => $_SESSION['lol_id'] = $userGame['ug_id'],
                            GameSlug::VALORANT->value => $_SESSION['valorant_id'] = $userGame['ug_id'],
                            default => null,
                        };
                        $lfGame = $this->userlookingforgames->getLookingForGameByUserIdAndGameId($user['user_id'], $gameId);
                        if ($lfGame) {
                            $_SESSION['lf_id'] = $lfGame['lfg_id'];
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function getSocialNetworkLogo($social)
    {
        $logos = [
            'facebook' => 'public/images/facebook-logo.png',
            'x' => 'public/images/twitter_user.png',
            'instagram' => 'public/images/instagram-logo.png',
            'twitch' => 'public/images/twitch_user.png',
            'youtube' => 'public/images/youtube_user.png',
            'tiktok' => 'public/images/tiktok.png',
        ];

        return $logos[strtolower($social)] ?? 'path/to/default-logo.png';
    }

    public function confirmMailPage() 
    {
        $this->initializeLanguage();
        if (isset($_SESSION['email'])) {
            $googleUser = $this-> googleUser -> getGoogleUserByEmail($_SESSION['email']);
        } else {
            header("Location: /?message=" . urlencode($this->_('messages.no_email')));
            return;
        }

        if($googleUser['google_confirmEmail'] == 0 || $googleUser['google_confirmEmail'] == NULL)
        {
            $this->renderPage(
                layout: 'views/layoutSignup.phtml',
                template: 'views/signup/waitingEmail',
                current_url: 'https://ur-sg.com/confirmMail',
                page_title: 'URSG - Confirm Mail',
                picture: 'ursg-preview-small',
                title: 'Confirm Mail',
            );
        }
        else if($googleUser['google_confirmEmail'] == 1 && !$this->isConnectWebsite())
        {
            ob_start();
            header("Location: /signup");
            return;
        }
        else if($googleUser['google_confirmEmail'] == 1 && $this->isConnectWebsite())
        {
            ob_start();
            header("Location: /signup");
            return;
        }
        else
        {
            ob_start();
            header("Location: /swiping");
            return;
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
                echo json_encode(['message' => $this->_('messages.no_email')]);
                return;
            }

            if($googleUser['google_confirmEmail'] == 0 || $googleUser['google_confirmEmail'] == NULL)
            {
                echo json_encode(['message' => $this->_('messages.success_email_confirmed')]);
                return;
            }
            else
            {
                echo json_encode(['message' => $this->_('messages.email_not_confirmed')]);
                return;
            }
        }
    }

    public function pageSignUp()
    {
        $this->initializeLanguage();

        if (isset($_SESSION['google_userId'])) {
            $secondTierUser = $this->user->getUserDataByGoogleUserId($_SESSION['google_userId']);
            if ($secondTierUser) {
                $finalUser = $this->user->getUserById($secondTierUser['user_id']);
            }
        }

        $gameSlug = $finalUser['game_slug'] ?? null;

        $destination = $this->routingService->routeUser(['step' => 'swipingMain'], gameSlug: $gameSlug);

        if (empty($destination)) {
            $destination = $this->routingService->swipingMainDestination();
        }

        $googleUser = isset($_SESSION['email']) ? $this->googleUser->getGoogleUserByEmail($_SESSION['email']) : null;

        $this->dispatch($destination, ['user' => $finalUser ?? null, 'googleUser' => $googleUser ?: null]);
    }

    public function legalNoticePage()
    {
        $this->initializeLanguage();

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/legalnotice',
            current_url: 'https://ur-sg.com/legalNotice',
            page_title: 'URSG - Legal notice',
            picture: 'ursg-preview-small',
            title: 'Legal Notice',
            data: ['user' => $user],
        );
    }

    public function CSAEPage()
    {
        $this->initializeLanguage();

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/csae',
            current_url: 'https://ur-sg.com/CSAE',
            page_title: 'URSG - Child Sexual Abuse and Exploitation (CSAE) Policy',
            picture: 'ursg-preview-small',
            title: 'Child Sexual Abuse and Exploitation (CSAE) Policy',
            data: ['user' => $user],
        );
    }

    public function termsOfServicePage()
    {
        $this->initializeLanguage();

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/termsofservice',
            current_url: 'https://ur-sg.com/termsOfService',
            page_title: 'URSG - Terms of service',
            picture: 'ursg-preview-small',
            title: 'Terms of service',
            data: ['user' => $user],
        );
    }

    public function siteMapPage()
    {
        $this->initializeLanguage();
        $xml = simplexml_load_file('sitemap.xml');

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/sitemap',
            current_url: 'https://ur-sg.com/siteMap',
            page_title: 'URSG - Site map',
            picture: 'ursg-preview-small',
            title: 'Site map',
            data: ['user' => $user, 'xml' => $xml],
        );
    }

    public function notFoundPage()
    {
        $this->initializeLanguage();

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/pageNotFound',
            current_url: 'https://ur-sg.com/',
            page_title: 'URSG - 404 - Page not found',
            picture: 'ursg-preview-small',
            title: '404 - Page not found',
            data: ['user' => $user],
        );
    }

    public function verifyGoogleToken($idToken) {
        $client = new Google_Client();
        $client->setClientId('666369513537-r75otamfu9qqsnaklgqiromr7bhiehft.apps.googleusercontent.com'); 
    
        try {
            $payload = $client->verifyIdToken($idToken);
            if ($payload) {
                $userId = $payload['sub'];
                $email = $payload['email'];
                $name = $payload['name'];
                $picture = $payload['picture'];
    
                return [
                    'userId' => $userId,
                    'email' => $email,
                    'name' => $name,
                    'picture' => $picture,
                    'verified' => true
                ];
            } else {
                return ['verified' => false, 'message' => 'Invalid token'];
            }
        } catch (Exception $e) {
            return ['verified' => false, 'message' => $e->getMessage()];
        }
    }

    public function getGoogleData() 
    {
        $this->initializeLanguage();
        $response = array('message' => $this->_('messages.contact_admin'));
    
        if (isset($_POST['googleData'])) // DATA SENT BY AJAX
        {
            $googleData = json_decode($_POST['googleData']);
            $idToken = $googleData->idToken ?? null;

            if ($idToken) {
                if ($_ENV['environment'] === 'local') {
                    // Local environment specific code
                    $verificationResult = true;
                } else {
                    $verificationResult = $this->verifyGoogleToken($idToken);
                }

                if (!$verificationResult) {
                    $response = array('message' => $this->_('messages.invalid_token'));
                    echo json_encode($response);
                    return;
                }
            } else {
                $response = array('message' => $this->_('messages.no_token'));
                echo json_encode($response);
                return;
            }


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

            $testBan = $this->bannedusers->checkBan($this->getGoogleEmail());

            if ($testBan) {
                $response = array('message' => $this->_('messages.account_banned'));
                echo json_encode($response);
                return;
            }
            
            $testGoogleUser = $this->googleUser->userExist($this->getGoogleId());

            if($testGoogleUser) //CREATING SESSION IF USER EXISTS
            {
                if ($this->isConnectGoogle())
                {
                    if (isset($_COOKIE['googleId'])) {
                        setcookie('googleId', "", time() - 42000, COOKIEPATH);
                        unset($_COOKIE['googleId']);
                    }

                    if (isset($_COOKIE['auth_token'])) {
                        setcookie('auth_token', "", time() - 42000, "/");
                        unset($_COOKIE['auth_token']);
                    }

                    session_unset();
                    session_destroy();
                    session_start();
                }

                $outcome = $this->logInService->resumeWebSession($testGoogleUser);
                $adminToken = '';

                if (!$outcome->userExists) {
                    $response = array(
                        'message' => 'Success',
                        'newUser' => false,
                        'googleUser' => $testGoogleUser,
                        'userExists' => false,
                        'masterTokenWebsite' => $outcome->masterToken,
                        'adminToken' => $adminToken
                    );
                    echo json_encode($response);
                    return;
                }

                $user = $outcome->userRow;

                if ($user['user_id'] === 157 || $user['user_id'] === 158) {
                    require 'keys.php';
                    $adminToken = $adminTokenSecret;
                }

                if ($outcome->game === 'League of Legends') {
                    if ($outcome->destination === LoginDestination::NEEDS_GAME_ACCOUNT) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'googleUser' => $testGoogleUser,
                            'user' => $user,
                            'userExists' => true,
                            'leagueUserExists' => false,
                            'masterTokenWebsite' => $outcome->masterToken,
                            'adminToken' => $adminToken
                        );
                    } elseif ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => true,
                            'lookingForUserExists' => false,
                            'googleUser' => $testGoogleUser,
                            'user' => $user,
                            'leagueUser' => $outcome->gameProfile,
                            'masterTokenWebsite' => $outcome->masterToken,
                            'adminToken' => $adminToken
                        );
                    } else {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => true,
                            'lookingForUserExists' => true,
                            'googleUser' => $testGoogleUser,
                            'user' => $user,
                            'leagueUser' => $outcome->gameProfile,
                            'lookingForUser' => $outcome->lookingForRow,
                            'masterTokenWebsite' => $outcome->masterToken,
                            'adminToken' => $adminToken
                        );
                    }
                } else {
                    if ($outcome->destination === LoginDestination::NEEDS_GAME_ACCOUNT) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'googleUser' => $testGoogleUser,
                            'user' => $user,
                            'userExists' => true,
                            'leagueUserExists' => false,
                            'valorantUserExists' => false,
                            'masterTokenWebsite' => $outcome->masterToken,
                            'adminToken' => $adminToken
                        );
                    } elseif ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => true, // preserved: pre-existing mislabel in the original response, out of scope for this refactor
                            'lookingForUserExists' => false,
                            'googleUser' => $testGoogleUser,
                            'user' => $user,
                            'valorantUser' => $outcome->gameProfile,
                            'valorantUserExists' => true,
                            'masterTokenWebsite' => $outcome->masterToken,
                            'adminToken' => $adminToken
                        );
                    } else {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => false,
                            'lookingForUserExists' => true,
                            'googleUser' => $testGoogleUser,
                            'user' => $user,
                            'valorantUser' => $outcome->gameProfile,
                            'lookingForUser' => $outcome->lookingForRow,
                            'valorantUserExists' => true,
                            'masterTokenWebsite' => $outcome->masterToken,
                            'adminToken' => $adminToken
                        );
                    }
                }

                echo json_encode($response);
                return;
            }
            else // IF USER DOES NOT EXIST, INSERT IT INTO DATABASE
            {
                $testGoogleUserEmail = $this->googleUser->getGoogleUserByEmail($this->getGoogleEmail());
                if ($testGoogleUserEmail)
                {
                    $response = array(
                        'message' => $this->_('messages.email_used'),
                    );
                    echo json_encode($response);
                    return;
                }
                $RSO = 0;
                $outcome = $this->signUpService->createWebIdentity(
                    $this->getGoogleId(),
                    $this->getGoogleFullName(),
                    $this->getGoogleFirstName(),
                    $this->getGoogleFamilyName(),
                    $this->getGoogleEmail(),
                    $RSO
                );

                if ($outcome) {
                    $this->setGoogleUserId($outcome->identityRow['google_userId']);

                    $response = array(
                        'message' => 'Success',
                        'newUser' => true,
                        'googleUser' => $outcome->identityRow['google_userId'],
                        'masterTokenWebsite' => $outcome->masterToken
                    );
                }
            }
        }
        else
        {
            $response = array(
                'message' => $this->_('messages.contact_admin'),
            );
        }
        echo json_encode($response);
        return;
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

            $testBan = $this->bannedusers->checkBan($this->getGoogleEmail());

            if ($testBan) {
                $response = array('message' => 'Account is banned');
                echo json_encode($response);
                return;
            }
            
            $testGoogleUser = $this->googleUser->userExist($this->getGoogleId());

            if($testGoogleUser) //CREATING SESSION IF USER EXISTS
            {
                $outcome = $this->logInService->resumeMobileProfile($testGoogleUser);

                if (!$outcome->userExists) {
                    $response = array(
                        'message' => 'Success',
                        'newUser' => false,
                        'googleUser' => $outcome->identityRow,
                        'userExists' => false
                    );
                    echo json_encode($response);
                    return;
                }

                if ($outcome->game === 'League of Legends') {
                    if ($outcome->destination === LoginDestination::NEEDS_GAME_ACCOUNT) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'googleUser' => $outcome->identityRow,
                            'user' => $outcome->userRow,
                            'userExists' => true,
                            'leagueUserExists' => false
                        );
                    } elseif ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => true,
                            'lookingForUserExists' => false,
                            'googleUser' => $outcome->identityRow,
                            'user' => $outcome->userRow,
                            'leagueUser' => $outcome->gameProfile
                        );
                    } else {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => true,
                            'lookingForUserExists' => true,
                            'googleUser' => $outcome->identityRow,
                            'user' => $outcome->userRow,
                            'leagueUser' => $outcome->gameProfile,
                            'lookingForUser' => $outcome->lookingForRow
                        );
                    }
                } else {
                    if ($outcome->destination === LoginDestination::NEEDS_GAME_ACCOUNT) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'googleUser' => $outcome->identityRow,
                            'user' => $outcome->userRow,
                            'userExists' => true,
                            'leagueUserExists' => false,
                            'valorantUserExists' => false
                        );
                    } elseif ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => false,
                            'lookingForUserExists' => false,
                            'googleUser' => $outcome->identityRow,
                            'user' => $outcome->userRow,
                            'valorantUser' => $outcome->gameProfile,
                            'valorantUserExists' => true
                        );
                    } else {
                        $response = array(
                            'message' => 'Success',
                            'newUser' => false,
                            'userExists' => true,
                            'leagueUserExists' => false,
                            'lookingForUserExists' => true,
                            'googleUser' => $outcome->identityRow,
                            'user' => $outcome->userRow,
                            'valorantUser' => $outcome->gameProfile,
                            'lookingForUser' => $outcome->lookingForRow,
                            'valorantUserExists' => true
                        );
                    }
                }

                echo json_encode($response);
                return;
            }
            else // IF USER DOES NOT EXIST, INSERT IT INTO DATABASE
            {
                $testGoogleUserEmail = $this->googleUser->getGoogleUserByEmail($this->getGoogleEmail());
                if ($testGoogleUserEmail)
                {
                    $response = array(
                        'message' => 'Email is already used.',
                    );
                    echo json_encode($response);
                    return;
                }

                $RSO = 0;
                $outcome = $this->signUpService->createMobileIdentity(
                    $this->getGoogleId(),
                    $this->getGoogleFullName(),
                    $this->getGoogleFirstName(),
                    $this->getGoogleFamilyName(),
                    $this->getGoogleEmail(),
                    $RSO
                );

                if ($outcome) {
                    $this->setGoogleUserId($outcome->identityRow['googleUserId']);

                    $response = array(
                        'message' => 'Success',
                        'newUser' => true,
                        'googleUser' => $outcome->identityRow,
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
        echo json_encode($response);
        return;
    }
    

    public function logOut() {
        if ($this->isConnectGoogle() || $this->isConnectWebsite()) {
            if (isset($_COOKIE['googleId'])) {
                setcookie('googleId', "", time() - 42000, COOKIEPATH);
                unset($_COOKIE['googleId']);
            }

            if (isset($_COOKIE['auth_token'])) {
                setcookie('auth_token', "", time() - 42000, "/");
                unset($_COOKIE['auth_token']);
            }
    
    
            session_unset();
            session_destroy();
    
            header("location:/?message=You are now offline&clearToken=true");
            return;
        } else {
            if (isset($_COOKIE['googleId'])) {
                setcookie('googleId', "", time() - 42000, COOKIEPATH);
                unset($_COOKIE['googleId']);
            }

            if (isset($_COOKIE['auth_token'])) {
                setcookie('auth_token', "", time() - 42000, "/");
                unset($_COOKIE['auth_token']);
            }
            header("location:/?message=You are now offline&clearToken=true");
            return;
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
                    return;                   
                }
                else 
                {
                    header("location:/?message=Couldnt confirm email");
                    return;                    
                }
            }
            else
            {
                header("location:/?message=Email does not exists");
                return;
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
                return;
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
                return;
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
                return;
            } else {
                echo json_encode(['message' => "Mail couldn't be sent"]);
                return;

            }
        } 
    }

    public function deleteAccountPage()
    {
        $this->initializeLanguage();

        $isOnboarded = $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf();

        $user = $isOnboarded ? $this->user->getUserById($_SESSION['userId']) : null;

        $this->renderPage(
            layout: $isOnboarded ? 'views/layoutSwiping.phtml' : 'views/layoutSwiping_noheader.phtml',
            template: 'views/swiping/delete_account',
            current_url: 'https://ur-sg.com/deleteAccount',
            page_title: 'URSG - Delete account',
            picture: 'ursg-preview-small',
            data: ['user' => $user],
        );
    }

    public function deleteGoogleAccount()
    {
        if (isset($_POST['submit']))
        {
            $email = $this->validateInput($_POST["email"]);
            $user = $this->googleUser->getUserByEmail($email);
    
            if (!$user) {
                header("location:/?message=Invalid email address");
                return;
            }
    
            // Generate a secure random token
            $token = bin2hex(random_bytes(32));
            $expiryDate = date('Y-m-d H:i:s', time() + 1800); 
            $currentDate = date('Y-m-d H:i:s');
    
            // Save the token and expiry in the database
            $this->user->storeDeletionToken($user['user_id'], $token, $expiry, $currentDate);
    
            // Send the email
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
            $mail->Subject = 'Confirm Deleting Your URSG Account';
            $mail->isHTML(true);
    
            $confirmationUrl = "https://ur-sg.com/deleteAccountConfirm?token={$token}";
            $mail->Body = "
            <html>
            <head>...</head>
            <body>
                <p>We are sad to lose you!</p>
                <p>Confirm deleting your account by clicking the link below:</p>
                <a href='{$confirmationUrl}' class='button'>Confirm Deletion</a>
            </body>
            </html>";
    
            if (!$mail->send()) {
                header("location:/?message=Could not send mail");
                return;
            }
    
            header("location:/?message=You received a mail to confirm your choice");
            return;
        }
    }


    public function deleteAccountConfirm()
    {
        if (isset($_GET['token'])) {
            $token = $_GET['token'];
    
            // Validate the token
            $deletionData = $this->user->getDeletionToken($token);

            if (!$deletionData) {
                header("location:/?message=Invalid token");
                return;
            }

            if (!strtotime($deletionData['user_deletionTokenExpiry']) > strtotime('+30 minutes')) {
                header("location:/?message=Expired token");
                return;
            }
    
            $email = $deletionData['google_email'];
    
            // Delete the user's account
            $deleteAccount = $this->googleUser->deleteAccount($deletionData['google_email']);
            if ($deleteAccount) {
                // Invalidate the token after successful deletion
                $this->user->invalidateDeletionToken($token);
    
                // Log out and clear cookies
                session_unset();
                session_destroy();
                if (isset($_COOKIE['googleId'])) {
                    setcookie('googleId', "", time() - 42000, COOKIEPATH);
                    unset($_COOKIE['googleId']);
                }
    
                header("location:/?message=Account deleted, Email: ".$deletionData['google_email']."&deleted=true");
                return;
            } else {
                header("location:/?message=Account not found");
                return;
            }
        } else {
            header("location:/?message=Invalid request");
            return;
        }
    }

    public function deleteRiotAccount() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {
            $user = $this->user->getUserById($_SESSION['userId']);
            if ($user['google_createdWithRSO'] === 1) 
            {
                $deleteAccount = $this->googleUser->deleteRiotAccount($_SESSION['google_id']);

                if ($deleteAccount)
                {
                    session_unset();
                    session_destroy();
                    if (isset($_COOKIE['googleId'])) {
                        setcookie('googleId', "", time() - 42000, COOKIEPATH);
                        unset($_COOKIE['googleId']);
                    }
                    header("location:/?message=Account deleted");
                    return;
                }
                else
                {
                    header("location:/deleteAccount?message=Could not delete account");
                    return;
                }
            }
            else
            {
                header("location:/deleteAccount?message=This account is not a Riot account");
                return; 
            }
        }
        else 
        {
            header("location:/deleteAccount?message=You need to be online to delete a Riot account");
            return; 
        }
    }

    public function submitCandidature()
    {
        if (isset($_POST['name']) &&
            isset($_POST['email']) &&
            isset($_POST['role']) &&
            isset($_POST['skills']) &&
            isset($_POST['portfolio'])
            ) {

            require 'keys.php';
            $emailToSend = "contact@ur-sg.com";
    
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@ur-sg.com';
            $mail->Password = $password_gmail;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
    
            $mail->setFrom('contact@ur-sg.com', 'UR-SG.com');
            $mail->addAddress($emailToSend);
            $mail->Subject = 'Application Form';
            $mail->isHTML(true);
            
            $fullName = $_POST['name'];
            $userMail = $_POST['email'];
            $role = $_POST['role'];
            $skills = $_POST['skills'];
            $link = $_POST['portfolio'];

            $username = 'Unknown';
            $linkToURSGAccount = 'Unknown';

            if (isset($_POST['ursg_username'])) {
                $username = $_POST['ursg_username'];
                $linkToURSGAccount = 'https://ur-sg.com/user/'.urlencode($username);
            }

            $mail->Body = "
            <html>
            <head>...</head>
            <body>
                <p>A new Application was sent on the form!</p>
                <p>Here is the form Data:</p>
                <br>
                Full Name: $fullName<br>
                Email: $userMail<br>
                Role to apply for: $role<br>
                About the skills: $skills<br>
                Links: $link<br>
                UR-SG Username: $username<br>
                UR-SG Account Link: $linkToURSGAccount<br>
                <br><br>
            </body>
            </html>";
    
            if (!$mail->send()) {
                header("location:/hiring?message=Could not send mail");
                return;
            } else {
                header("location:/hiring?message=We got your candidature and will answer soon!");
                return;
            }
        }
    }

    public function unsubscribeMails() 
    {
        if (isset($_GET['email']) && isset($_GET['googleUserId']))
        {
            $email = $_GET['email'];
            $googleUserId = $_GET['googleUserId'];

            $user = $this->googleUser->getUserByEmail($email);

            if ($user['google_userId'] != $googleUserId) {
                header("location:/termsOfService?message=Invalid request");
                return;
            }

            $unsubscribe = $this->googleUser->unsubscribeMails($email);
            if ($unsubscribe) {
                header("location:/termsOfService?message=Unsubscribed from mails");
                return;
            } else {
                header("location:/termsOfService?message=Could not unsubscribe");
                return;
            }
        } else {
            header("location:/termsOfService?message=Invalid request");
            return;
        }
    }

    public function MailingCronJob() {
        require_once 'keys.php';

        $tokenAdmin = $_GET['token'] ?? null;
        if (!isset($tokenAdmin) || $tokenAdmin !== $tokenRefresh) { 
            http_response_code(401);
            echo "❌ Unauthorized.\n";
            return;
        }

        // Use a batched query rather than all users
        $users = $this->googleUser->getGoogleUsersMailingCronJob();
        if (!$users) {
            echo "No users to notify.\n";
            return;
        }

        foreach ($users as $user) {
            if (strpos($user['google_email'], '@gmail.com') !== false) {
                $unread = $this->chatmessage->getUnreadSummary($user['user_id']);
                $requests = $this->friendrequest->countFriendRequest($user['user_id']);
                $result = false;

                if ($unread['unread_count'] > 0 || $requests > 0) {
                    $result = $this->sendNotificationEmail(
                        $user['google_email'],
                        $unread['unread_count'],
                        $unread['latest_sender'],
                        $requests,
                        $user['google_userId']
                    );

                    if ($result) {
                        $this->googleUser->updateLastNotified($user['google_userId']);
                        echo "Sent to {$user['google_email']}\n";
                    } else {
                        echo "❌ Failed to send to {$user['google_email']}\n";
                    }
                }
            }
        }
    }

    public function sendNotificationEmail($to, $unreadCount, $latestSender, $requests, $googleUserId)
    {   
        $messageTextMessage = "";
        $messageTextRequests = "";
        if ($unreadCount == 0) { 
            $unreadCount = false;
        } else {
            if ($unreadCount == 1) {
                $messageTextMessage = "You have <strong>1 unread message</strong> (latest from <a href='https://ur-sg.com/user/{$latestSender}'>{$latestSender}</a>)";
            } else {
                $messageTextMessage = "You have <strong>{$unreadCount} unread messages</strong> (latest from <a href='https://ur-sg.com/user/{$latestSender}'>{$latestSender}</a>)";
            }
        }

        if ($requests == 0) { 
            $requests = false;
        } else {
            if ($requests == 1) {
                $messageTextRequests = " You have <strong>1 pending friend request</strong>";
            } else {
                $messageTextRequests = " You have <strong>{$requests} pending friend requests</strong>";
            }
        }

        $subjects = [
            "We miss you on URSG!",
            "What you missed on URSG",
            "Time to check what you missed on URSG",
            "Someones reaching out to you on URSG",
        ];

        $headings = [
            "Dont miss out, check UR-SG today",
            "Your friends have something for you"
        ];

        $subject = $subjects[array_rand($subjects)];
        $heading = $headings[array_rand($headings)];

        require 'keys.php';
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.ionos.de';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@ur-sg.com';
            $mail->Password = $password_gmail;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('contact@ur-sg.com', 'UR-SG.com');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;

            // Build the body here
            

            $mail->Body = "
            <html lang='en'>
                <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>{$heading}</title>
                <style>
                    body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                    }
                    .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    padding: 10px;
                    border: 1px solid #dddddd;
                    }
                    .header {
                    text-align: center;
                    padding: 10px 0;
                    }
                    .header img {
                    max-width: 180px;
                    height: auto;
                    }
                    .content {
                    padding: 20px;
                    text-align: center;
                    color: #333333;
                    }
                    .content h2 {
                    color: #e74057;
                    }
                    .btn {
                    background-color: #e74057;
                    color: #ffffff !important;
                    padding: 12px 24px;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                    display: inline-block;
                    margin-top: 20px;
                    }
                    .footer {
                    text-align: center;
                    padding: 10px;
                    font-size: 12px;
                    color: #777777;
                    }
                </style>
                </head>
                <body>
                <div class='email-container'>
                    <!-- Header with Logo -->
                    <div class='header'>
                    <img src='https://ur-sg.com/public/images/logo_ursg.png' alt='URSG Logo'>
                    </div>

                    <!-- Email Content -->
                    <div class='content'>
                    <h2>Someone’s trying to reach you on UR-SG</h2>
                    <p>
                        {$messageTextMessage}{$messageTextRequests}
                    </p>

                    <a href='https://ur-sg.com/?triggerSignUp=true' class='btn'>Check Your Account</a>
                    </div>

                    <!-- Footer -->
                    <div class='footer'>
                    <p>&copy; 2025 UR-SG. All rights reserved.</p>
                    <p style='font-size: 11px; color: #999999;'>
                        You're receiving this email because you have an account at UR-SG.<br>
                        <a href='https://ur-sg.com/unsubscribeMails?email={$to}&googleUserId={$googleUserId}' style='color: #999999;'>Unsubscribe</a>
                    </p>
                    </div>
                </div>
                </body>
            </html>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail to {$to} failed: {$mail->ErrorInfo}");
            return false;
        }
    }

    public function isMobileUpdateNeeded()
    {
        if (isset($_POST['currentVersion'])) {
            $currentVersion = $this->validateInput($_POST['currentVersion']);
            $latestVersion = '1.3.9';

            if (version_compare($currentVersion, $latestVersion, '<')) {
                $response = [
                    'updateNeeded' => true,
                    'latestVersion' => $latestVersion,
                    'message' => 'A new version is available. Please update the app.'
                ];
            } else {
                $response = [
                    'updateNeeded' => false,
                    'message' => 'You are using the latest version.'
                ];
            }
            echo json_encode($response);
            return;
        } else {
            $response = [
                'message' => 'Current version not provided.'
            ];
            echo json_encode($response);
            return;
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