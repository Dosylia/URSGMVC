<?php

namespace controllers;

use models\Admin;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;
use models\Items;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpressionList;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Dimension;


require 'vendor/autoload.php';

use traits\SecurityController;

class AdminController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private Admin $admin;
    private Items $items;

    public function __construct()
    {
        $this->friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();
        $this->chatmessage = new ChatMessage();
        $this->admin = new Admin();
        $this->items = new Items();
    }

    public function adminLandingPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            ($this->isModerator() || $this->isAdmin() || $this->isMarketing())
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersOnline = $this-> admin -> countOnlineUsers();
            $usersOnlineLast7Days = $this-> admin -> countOnlineUsersLast7Days();
            $countOnlineUsersToday = $this-> admin -> countOnlineUsersToday();
            $purchases = $this-> admin -> countPurchases();
            $pendingReports = $this-> admin -> countPendingReports();
            $adminActions = $this-> admin -> getLastAdminActions();
            $dailyActivity = $this-> admin -> dailyActivity();
            $weeklyActivity = $this-> admin -> weeklyActivity();
            $pageViews = $this->fetchPageViews();
            $funnelConversions = $this->getFunnelConversion();
            $eventCounts = $this->fetchMultipleEventCounts();
            $returningUserCount   = $eventCounts['returning_user'] ?? 0;
            $matchCreatedCount    = $eventCounts['match_created'] ?? 0;
            $newUserCount         = $eventCounts['new_user'] ?? 0;
            $LoggedOnUserCount    = $eventCounts['login'] ?? 0;
            $deletedAccountCount  = $eventCounts['deleted_account'] ?? 0;
            $dailyActivityJson = json_encode($dailyActivity);
            $current_url = "https://ur-sg.com/admin";
            $template = "views/admin/admin_landing";
            $picture = "ursg-preview-small";
            $page_title = "URSG - Admin";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    private function getAnalyticsClient()
    {
        static $client = null;
        if ($client === null) {
            $keyFilePath = __DIR__ . '/../config/ursg-389213-9698aca8b0a6.json';
            $client = new BetaAnalyticsDataClient(['credentials' => $keyFilePath]);
        }
        return $client;
    }

    public function fetchPageViews()
    {
        $client = $this->getAnalyticsClient();
        $property_id = '496417395';

        $response = $client->runReport([
            'property' => 'properties/' . $property_id,
            'dateRanges' => [
                new DateRange([
                    'start_date' => '7daysAgo',
                    'end_date' => 'today',
                ]),
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews']),
            ],
            'dimensions' => [
                new Dimension(['name' => 'pagePath']),
            ],
            'limit' => 5,
        ]);

        $results = [];
        foreach ($response->getRows() as $row) {
            $results[] = [
                'page' => $row->getDimensionValues()[0]->getValue(),
                'views' => $row->getMetricValues()[0]->getValue(),
            ];
        }

        return $results;
    }

    public function fetchMultipleEventCounts()
    {
        $client = $this->getAnalyticsClient();
        $property_id = '496417395';

        $eventNames = [
            'returning_user',
            'new_user',
            'match_created',
            'login',
            'deleted_account'
        ];

        // Build OR filter for events
        $expressions = array_map(function ($name) {
            return new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'eventName',
                    'string_filter' => new StringFilter(['value' => $name]),
                ])
            ]);
        }, $eventNames);

        $response = $client->runReport([
            'property' => 'properties/' . $property_id,
            'dateRanges' => [
                new DateRange(['start_date' => '30daysAgo', 'end_date' => 'today']),
            ],
            'dimensions' => [new Dimension(['name' => 'eventName'])],
            'metrics' => [new Metric(['name' => 'eventCount'])],
            'dimensionFilter' => new FilterExpression([
                'or_group' => new FilterExpressionList(['expressions' => $expressions])
            ]),
        ]);

        $results = array_fill_keys($eventNames, 0);
        foreach ($response->getRows() as $row) {
            $eventName = $row->getDimensionValues()[0]->getValue();
            $count = $row->getMetricValues()[0]->getValue();
            $results[$eventName] = (int)$count;
        }

        return $results;
    }

    public function getFunnelConversion($startDate = '30daysAgo')
    {
        $client = $this->getAnalyticsClient();
        $property_id = '496417395';

        // Update: Add 'customEvent:page_path' to dimensions
        $response = $client->runReport([
            'property' => 'properties/' . $property_id,
            'dateRanges' => [
                new DateRange(['start_date' => $startDate, 'end_date' => 'today']),
            ],
            'dimensions' => [
                new Dimension(['name' => 'customEvent:visitor_id']),
                new Dimension(['name' => 'eventName']),
                new Dimension(['name' => 'pagePath']),
                new Dimension(['name' => 'date']),
            ],
            'metrics' => [
                new Metric(['name' => 'eventCount']),
            ],
            'limit' => 10000,
        ]);

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return [
                'landingToSignup' => 0,
                'signupToLogin' => 0,
                'loginToMatch' => 0,
                'signupToMatch' => 0,
                'totals' => [
                    'landing' => 0,
                    'signup' => 0,
                    'login' => 0,
                    'match' => 0,
                    'signupToMatch' => 0,
                ]
            ];
        }

        $users = [];
        $eventCounts = [];
        $landingViewTimestamps = [];

        foreach ($rows as $index => $row) {
            $dimensionValues = $row->getDimensionValues();
            $metricValues = $row->getMetricValues();

            if (count($dimensionValues) >= 4 && count($metricValues) >= 1) {
                $visitorId = $dimensionValues[0]->getValue();
                $eventName = $dimensionValues[1]->getValue();
                $pagePath = $dimensionValues[2]->getValue();
                $eventTimestampStr = $dimensionValues[3]->getValue(); // ISO 8601 format
                $eventTimestamp = strtotime($eventTimestampStr);

                $eventCount = intval($metricValues[0]->getValue());

                if ($index < 10) {
                    error_log("Row $index: visitor_id='$visitorId', event='$eventName', path='$pagePath', time='$eventTimestampStr', count=$eventCount");
                }

                // Count event totals
                $eventKey = $eventName . ($pagePath ? " @ $pagePath" : '');
                if (!isset($eventCounts[$eventKey])) {
                    $eventCounts[$eventKey] = 0;
                }
                $eventCounts[$eventKey] += $eventCount;

                if (!empty($visitorId) && $visitorId !== '(not set)') {
                    if (!isset($users[$visitorId])) {
                        $users[$visitorId] = [];
                    }

                    if ($eventName === 'page_view' && $pagePath === '/') {
                        // Only count landing_page_view once per visitor every 7 days
                        $lastLanding = $landingViewTimestamps[$visitorId] ?? null;
                        $date = $dimensionValues[3]->getValue();
                        $eventDate = \DateTime::createFromFormat('Ymd', $date)->getTimestamp();

                        if (!$lastLanding || ($eventDate - $lastLanding) >= (7 * 24 * 60 * 60)) {
                            $users[$visitorId][] = 'landing_page_view';
                            $landingViewTimestamps[$visitorId] = $eventDate;
                        }
                    } else {
                        $users[$visitorId][] = $eventName;
                    }
                }
            }
        }

        // Funnel logic
        $totalLanding = 0;
        $totalSignup = 0;
        $totalLogin = 0;
        $totalMatch = 0;
        $totalSignupToMatch = 0;

        foreach ($users as $visitorId => $events) {
            $uniqueEvents = array_unique($events);

            $hasLanding = in_array('landing_page_view', $uniqueEvents);
            $hasNewUser = in_array('new_user', $uniqueEvents);
            $hasLogin = in_array('login', $uniqueEvents);
            $hasMatch = in_array('match_created', $uniqueEvents);

            if ($hasLanding) $totalLanding++;
            if ($hasLanding && $hasNewUser) $totalSignup++;
            if ($hasLogin) $totalLogin++;
            if ($hasLogin && $hasMatch) $totalMatch++;
            if ($hasLanding && $hasNewUser && $hasMatch) $totalSignupToMatch++;
        }

        $conversion1 = $totalLanding ? round(($totalSignup / $totalLanding) * 100, 1) : 0;
        $conversion2 = $totalSignup ? round(($totalLogin / $totalSignup) * 100, 1) : 0;
        $conversion3 = $totalLogin ? round(($totalMatch / $totalLogin) * 100, 1) : 0;
        $conversionSignupToMatch = $totalSignup ? round(($totalSignupToMatch / $totalSignup) * 100, 1) : 0;

        return [
            'landingToSignup' => $conversion1,
            'signupToLogin' => $conversion2,
            'loginToMatch' => $conversion3,
            'signupToMatch' => $conversionSignupToMatch,
            'totals' => [
                'landing' => $totalLanding,
                'signup' => $totalSignup,
                'login' => $totalLogin,
                'match' => $totalMatch,
                'signupToMatch' => $totalSignupToMatch,
            ]
        ];
    }

    public function adminGamePage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {

            $totalPlayers = $this-> admin -> countTotalPlayers();
            $totalCharacters = $this-> admin -> countTotalCharacters();
            $lastGameDate = $this-> admin -> getLastGameDate();

            if ($lastGameDate) {
                $lastGameDatePlusOne = date("Y-m-d", strtotime($lastGameDate . " +1 day")); 
            }

            $current_url = "https://ur-sg.com/adminGame";
            $template = "views/admin/admin_game";
            $picture = "ursg-preview-small";
            $page_title = "URSG - Admin Game";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminReportsPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {

            $reports = $this->admin->getGroupedReports();

            $current_url = "https://ur-sg.com/adminReports";
            $template = "views/admin/admin_reports";
            $picture = "ursg-preview-small";
            $page_title = "URSG - Admin Report";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function addCharGame()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        ) {
            if (isset($_POST['game_username'])) {
                $gameUsername = $_POST['game_username'];
                $gameMain = $_POST['game_main'];
                $hintAffiliation = $_POST['hint_affiliation'] ?? null;
                $hintGender = $_POST['hint_gender'] ?? null;
                $hintGuess = $_POST['hint_guess'] ?? null;
                $gameDate = $_POST['game_date'];
                $gameGame = $_POST['game_game'];
    
                // Handle the uploaded picture
                if (isset($_FILES['game_picture']) && $_FILES['game_picture']['error'] === UPLOAD_ERR_OK) {
                    $pictureTmpName = $_FILES['game_picture']['tmp_name'];
                    $pictureFileName = "public/images/game/{$gameUsername}.jpg";
    
                    // Resize and save the image
                    if ($this->resizeAndSaveImage($pictureTmpName, $pictureFileName, 50, 50)) {
                        $addChar = $this->admin->addCharacterGame($gameUsername, $gameMain, $hintAffiliation, $hintGender, $hintGuess, $gameDate, $gameGame
                        );
    
                        if ($addChar) {
                            $this->admin->logAdminActionGame($_SESSION['userId'], $gameUsername, "Added Character");
                            header("Location: /adminGame?message=Character added successfully");
                            exit();
                        } else {
                            header("Location: /adminGame?message=Error adding character");
                            exit();
                        }
                    } else {
                        header("Location: /adminGame?message=Error resizing image");
                        exit();
                    }
                } else {
                    header("Location: /adminGame?message=Image upload failed");
                    exit();
                }
            } else {
                header("Location: /adminGame?message=Invalid input data");
                exit();
            }
        } else {
            header("Location: /");
            exit();
        }
    }

    public function resizeAndSaveImage($sourcePath, $destinationPath, $width, $height)
{
    try {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new Exception("Failed to get image size.");
        }
    
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $srcImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $srcImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $srcImage = imagecreatefromgif($sourcePath);
                break;
            default:
                throw new Exception("Unsupported image type.");
        }
    
        $dstImage = imagecreatetruecolor($width, $height);
        if (!$dstImage) {
            throw new Exception("Failed to create true color image.");
        }
    
        $resampled = imagecopyresampled(
            $dstImage,
            $srcImage,
            0, 0, 0, 0,
            $width, $height,
            $imageInfo[0], $imageInfo[1]
        );
        if (!$resampled) {
            throw new Exception("Failed to resample the image.");
        }
    
        $saved = imagejpeg($dstImage, $destinationPath, 90);
        if (!$saved) {
            throw new Exception("Failed to save the image.");
        }
    
        return true;
    } catch (Exception $e) {
        error_log("Image processing error: " . $e->getMessage());
        return false;
    }
}
    

    public function adminUpdateCurrency()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isAdmin()
        )
        {
            if (isset($_POST['currency']) && isset($_POST['user_id']))
            {
                $currency = $this->user->updateCurrency($_POST['user_id'], $_POST['currency']);

                if ($currency)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Updated Currency");
                    header("Location: /adminUsers?message=Money updated");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error updating money");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error updating money");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminBanUser()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isAdmin()
        )
        {
            if (isset($_POST['user_id']))
            {
                $userToBan = $this->user->getUserById($_POST['user_id']);
                $userId = $userToBan['user_id'];
                $username = $userToBan['user_username'];
                $deleteUser = $this->googleUser->deleteAccount($userToBan['google_email']);

                if ($deleteUser)
                {
                    $addBannedUser = $this->admin->addBannedUser($userToBan['google_email'], "Banned by admin");
                    if ($addBannedUser)
                    {
                        $this-> admin -> logAdminActionBan($_SESSION['userId'],  $userId, $username, $userToBan['google_email'], "Ban");
                        header("Location: /adminUsers?message=User banned");
                        exit();
                    }
                    else
                    {
                        header("Location: /adminUsers?message=Error banning user");
                        exit();
                    }
                }
                else
                {
                    header("Location: /adminUsers?message=Error banning user");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error banning user");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminCensorBio() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorBio = $this->admin->censorBio($_POST['user_id']);

                if ($censorBio)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Censor Bio");
                    header("Location: /adminUsers?message=Bio censored");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error censoring bio");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error censoring bio");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminCensorPicture() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorPicture = $this->admin->censorPicture($_POST['user_id']);

                if ($censorPicture)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Censor Picture");
                    header("Location: /adminUsers?message=Picture censored");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error censoring picture");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error censoring picture");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminUsersPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            ($this->isModerator() || $this->isAdmin() || $this->isMarketing())
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);
            $users = $this-> user -> getAllUsers();

            $current_url = "https://ur-sg.com/admin_users";
            $template = "views/admin/admin_users";
            $picture = "ursg-preview-small";
            $page_title = "URSG - Admin Users";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function reportAdminBanUser()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isAdmin()
        )
        {
            if (isset($_POST['user_id']))
            {
                $userToBan = $this->user->getUserById($_POST['user_id']);
                if ($userToBan) {
                    $userId = $userToBan['user_id'];
                    $username = $userToBan['user_username'];
                    $googleEmail = $userToBan['google_email'];
    
                    $deleteUser = $this->googleUser->deleteAccount($googleEmail);
    
                    if ($deleteUser)
                    {
                        $addBannedUser = $this->admin->addBannedUser($googleEmail, "Banned by admin");
    
                        if ($addBannedUser)
                        {
                            $this->admin->logAdminActionBan($_SESSION['userId'], $userId, $username, $googleEmail, "Ban");
    
                            $userReports = $this->admin->getReportsByUserId($userId);
                            foreach ($userReports as $report) {
                                $this->admin->updateReport($report['reported_id'], 'reviewed');
                            }
    
                            header("Location: /adminReports?message=User banned and reports reviewed");
                            exit();
                        }
                        else
                        {
                            header("Location: /adminReports?message=Error banning user");
                            exit();
                        }
                    }
                    else
                    {
                        header("Location: /adminReports?message=Error deleting user account");
                        exit();
                    }
                }
                else
                {
                    header("Location: /adminUsers?message=User not found");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function reportAdminCensorBio()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorBio = $this->admin->censorBio($_POST['user_id']);

                if ($censorBio)
                {
                    $this->admin->logAdminAction($_SESSION['userId'], $_POST['user_id'], "Censor Bio");

                    $userReports = $this->admin->getReportsByUserId($_POST['user_id']);
                    foreach ($userReports as $report) {
                        $this->admin->updateReport($report['reported_id'], 'reviewed');
                    }

                    header("Location: /adminReports?message=Bio censored and reports reviewed");
                    exit();
                }
                else
                {
                    header("Location: /adminReports?message=Error censoring bio");
                    exit();
                }
            }
            else
            {
                header("Location: /adminReports?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function reportAdminCensorPicture()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorPicture = $this->admin->censorPicture($_POST['user_id']);

                if ($censorPicture)
                {
                    $this->admin->logAdminAction($_SESSION['userId'], $_POST['user_id'], "Censor Picture");

                    $userReports = $this->admin->getReportsByUserId($_POST['user_id']);
                    foreach ($userReports as $report) {
                        $this->admin->updateReport($report['reported_id'], 'reviewed');
                    }

                    header("Location: /adminReports?message=Picture censored and reports reviewed");
                    exit();
                }
                else
                {
                    header("Location: /adminReports?message=Error censoring picture");
                    exit();
                }
            }
            else
            {
                header("Location: /adminReports?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function reportAdminCensorBoth()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorBio = $this->admin->censorBio($_POST['user_id']);
                $censorPicture = $this->admin->censorPicture($_POST['user_id']);

                if ($censorBio && $censorPicture)
                {
                    $this->admin->logAdminAction($_SESSION['userId'], $_POST['user_id'], "Censor Bio and Picture");

                    $userReports = $this->admin->getReportsByUserId($_POST['user_id']);
                    foreach ($userReports as $report) {
                        $this->admin->updateReport($report['reported_id'], 'reviewed');
                    }

                    header("Location: /adminReports?message=Bio and picture censored and reports reviewed");
                    exit();
                }
                else
                {
                    header("Location: /adminReports?message=Error censoring bio and picture");
                    exit();
                }
            }
            else
            {
                header("Location: /adminReports?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function reportAdminRequestBan()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $userToBan = $this->user->getUserById($_POST['user_id']);
                if ($userToBan) {
                    $userId = $userToBan['user_id'];

                    $this->admin->logAdminAction($_SESSION['userId'], $_POST['user_id'], "Requesting ban");
    
                    $userReports = $this->admin->getReportsByUserId($_POST['user_id']);
                    foreach ($userReports as $report) {
                        $this->admin->updateReport($report['reported_id'], 'Request Ban');
                    }

                    header("Location: /adminReports?message=Requested the ban successfully");
                    exit();
                  
                }
                else
                {
                    header("Location: /adminReports?message=User not found");
                    exit();
                }
            }
            else
            {
                header("Location: /adminReports?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminRemovePartner() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isAdmin()
        )
        {
            if (isset($_POST['user_id']))
            {
                $userToRemove = $this->user->getUserById($_POST['user_id']);
                $removePartner = $this->user->removePartner($userToRemove['user_id']);

                if ($removePartner)
                {
                    // Remove all items in store that partner doesn't own
                    $this->items->removePartnerItems($_POST['user_id']);

                    $this->admin->logAdminAction($_SESSION['userId'], $_POST['user_id'], "Removed partner");
                    header("Location: /adminUsers?message=Partner removed successfully");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error removing partner");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminAddPartner()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isAdmin()
        )
        {
            if (isset($_POST['user_id']))
            {
                $userToAdd = $this->user->getUserById($_POST['user_id']);
                $addPartner = $this->user->addPartner($userToAdd['user_id']);

                if ($addPartner)
                {

                    // Add all items in store that partner doesn't own
                    $ownedItems = $this->items->getOwnedItems($_POST['user_id']); // should return an array of item IDs the user owns
                    $allItems = $this->items->getItems(); // should return all items (with their IDs)

                    if (empty($ownedItems)) {
                        foreach ($allItems as $item) {
                            $this->items->addItemToUserAsPartner($_POST['user_id'], $item['items_id']);
                        }
                    } else {
                        foreach ($allItems as $item) {
                            if (!in_array($item['items_id'], $ownedItems)) {
                                $this->items->addItemToUserAsPartner($_POST['user_id'], $item['items_id']);
                            }
                        }
                    }


                    $this->admin->logAdminAction($_SESSION['userId'], $_POST['user_id'], "Added partner");
                    header("Location: /adminUsers?message=Partner added successfully");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error adding partner");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function reportAdminDismiss()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $userToDismiss = $this->user->getUserById($_POST['user_id']);
                if ($userToDismiss) {
                    $userId = $userToDismiss['user_id'];

                    $this->admin->logAdminAction($_SESSION['userId'], $_POST['user_id'], "Dismissed");
    
                    $userReports = $this->admin->getReportsByUserId($_POST['user_id']);
                    foreach ($userReports as $report) {
                        $this->admin->updateReport($report['reported_id'], 'Dismissed');
                    }

                    header("Location: /adminReports?message=Requested the dismiss successfully");
                    exit();
                  
                }
                else
                {
                    header("Location: /adminReports?message=User not found");
                    exit();
                }
            }
            else
            {
                header("Location: /adminReports?message=Invalid request");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }
    
}
