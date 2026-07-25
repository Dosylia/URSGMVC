<?php

namespace controllers;

use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use models\UserLookingFor;
use models\GoogleUser;
use models\ChatMessage;
use models\Discord;
use models\Items;
use models\BannedUsers;
use traits\SecurityController;
use traits\MobileDeepLinkResponder;
use services\DiscordBotService;
use services\LogInService;
use services\SignUpService;
use services\MasterTokenService;
use services\LoginDestination;
use services\DiscordOAuthClientInterface;
use services\DiscordOAuthClient;
use services\FakeDiscordOAuthClient;

class DiscordController
{
    use SecurityController;
    use MobileDeepLinkResponder;
    private LeagueOfLegends $leagueOfLegends;
    private User $user;
    private Valorant $valorant;
    private GoogleUser $googleUser;
    private UserLookingFor $userlookingfor;
    private Discord $discord;
    private Items $items;
    private BannedUsers $bannedusers;
    private LogInService $logInService;
    private SignUpService $signUpService;
    private DiscordOAuthClientInterface $discordOAuthClient;
    private $botToken;
    private $guildId;
    private $redirectUri;

    public function __construct()
    {
        $this->leagueOfLegends = new LeagueOfLegends();
        $this->user = new User();
        $this->valorant = new Valorant();
        $this -> googleUser = new GoogleUser();
        $this -> discord = new Discord();
        $this -> userlookingfor = new userLookingFor();
        $this->items = new Items();
        $this->bannedusers = new BannedUsers();
        $masterTokenService = new MasterTokenService($this->googleUser);
        $this->logInService = new LogInService(
            $masterTokenService,
            $this->user,
            $this->leagueOfLegends,
            $this->valorant,
            $this->userlookingfor
        );
        $this->signUpService = new SignUpService($this->googleUser, $masterTokenService);
        $this->discordOAuthClient = (($_ENV['environment'] ?? null) === 'local')
            ? new FakeDiscordOAuthClient()
            : new DiscordOAuthClient();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function createChannel() {

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        if ($this->discord->hasCreatedChannelRecently($userId)) {
            echo json_encode(['success' => false, 'message' => 'You have to wait before creating another channel.']);
            return;
        }

        require_once 'keys.php';
        $botToken = $discordToken;
        $guildId = $discordServerId;

        $getDiscordUsername = $this->discord->getDiscordUsername($userId);
        $user = $this->user->getUserById($userId);

        if ($getDiscordUsername) {
            $channelName = strtolower(str_replace(" ", "-", $getDiscordUsername['discord_username'])); // Format channel name
        } else {
            $channelName = strtolower(str_replace(" ", "-", $user['user_username'])); // Format channel name
        }
        

        // Step 1: Create a new channel
        $url = "https://discord.com/api/v10/guilds/{$guildId}/channels";
        $data = [
            "name" => $channelName,
            "type" => 2, 
            "parent_id" => "1263103079235063893" // Category ID
        ];

        $response = $this->makeDiscordRequest($url, $data, $botToken);
        $channelId = $response['id'] ?? null;

        if (!$channelId) {
            echo json_encode(['success' => false, 'message' => 'Failed to create channel']);
            return;
        }

        if ($channelId) {
            // Store the channel ID and the time it should be deleted
            $this->discord->storeTemporaryChannel($channelId, $userId); // 1 hour expiry
        }

        // Step 2: Create an invite for the channel
        $inviteUrl = "https://discord.com/api/v10/channels/{$channelId}/invites";
        $inviteData = ["max_age" => 3600, "max_uses" => 1, "unique" => true]; // Expires in 1 hour

        $inviteResponse = $this->makeDiscordRequest($inviteUrl, $inviteData, $botToken);
        $inviteCode = $inviteResponse['code'] ?? null;

        if (!$inviteCode) {
            echo json_encode(['success' => false, 'message' => 'Failed to create invite']);
            return;
        }

        echo json_encode(['success' => true, 'link' => "https://discord.gg/{$inviteCode}"]);
        return;
    }

    public function deleteExpiredChannels() {
        require_once 'keys.php';

        $tokenAdmin = $_GET['token'] ?? null;

        if (!isset($tokenAdmin) || $tokenAdmin !== $tokenRefresh) { 
            header("Location: /?message=Unauthorized");
            return;
        }

        $botToken = $discordToken;
        $channels = $this->discord->getExpiredChannels();
        $guildId = $discordServerId; // Replace with your actual guild ID

        if (empty($channels)) {
            echo "No expired channels to delete.";
            return;
        }
    
        foreach ($channels as $channel) {
            // Ensure 'channel_id' is set in the channel array
            if (!isset($channel['channel_id'])) {
                error_log("No channel_id found for channel in the expired channels list.");
                continue; // Skip this iteration if there's no channel_id
            }
    
            // Fetch the guild widget to check members in the channel
            $url = "https://discord.com/api/v10/guilds/{$guildId}/widget.json";
            $headers = [
                "Authorization: Bot {$botToken}",
                "Content-Type: application/json"
            ];
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            curl_close($ch);
    
            // Check if the response is valid
            if (!$response) {
                error_log("Error fetching widget for guild {$guildId}");
                continue;
            }
    
            $widgetData = json_decode($response, true);

    
            // Check if the members array exists and is not empty
            if (!isset($widgetData['members']) || empty($widgetData['members'])) {
                error_log("No members found in the widget data for guild {$guildId}");
                continue;
            }
    
            // Check if the channel_id exists in the widget data
            $channelHasMembers = false;
            foreach ($widgetData['members'] as $member) {
                // Debug: var_dump each member
                var_dump($member);
    
                // Ensure 'channel_id' is set for each member before comparing
                if (isset($member['channel_id']) && $member['channel_id'] == $channel['channel_id']) {
                    $channelHasMembers = true;
                    break;
                }
            }
    
            if ($channelHasMembers) {
                // If there are members in the channel, skip deletion
                error_log("Channel {$channel['channel_id']} has members and cannot be deleted.");
            } else {
                // If no members are in the channel, proceed with the deletion
                $url = "https://discord.com/api/v10/channels/{$channel['channel_id']}";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HEADER, true); // Get response headers too
                curl_setopt($ch, CURLOPT_VERBOSE, true); // Helpful for debugging
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                // Parse the response body
                list($headers, $body) = explode("\r\n\r\n", $response, 2);
                
                if ($httpCode === 200 || $httpCode === 204) {
                    echo "✅ Channel {$channel['channel_id']} deleted successfully.\n";
                    $this->discord->removeTemporaryChannel($channel['channel_id']);
                } else {
                    error_log("❌ Failed to delete channel {$channel['channel_id']} - HTTP {$httpCode}");
                    echo "❌ Failed to delete channel {$channel['channel_id']} - HTTP {$httpCode}";
                    error_log("Response body: " . $body);
                }
            }
        }
    }      

    private function makeDiscordRequest($url, $data, $botToken) {
        $headers = [
            "Authorization: Bot {$botToken}",
            "Content-Type: application/json"
        ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
        $response = curl_exec($ch);
        if(curl_errno($ch)) {
            error_log("CURL Error: " . curl_error($ch)); // Log CURL errors if any
        }
        curl_close($ch);
    
        error_log("Response from Discord API: " . $response); 
        return json_decode($response, true);
    }

    public function discordData() 
    {
        require_once 'keys.php';
        $isMobile = isset($_SESSION['discordConnectMobile']) ? true : false;
        $clientId = $discordClientId;
        $clientSecret = $discordClientSecret;
        $redirectUri = "https://ur-sg.com/discordData";

        $code = $_GET['code'] ?? null;

        if (!$code) {
            die("Authorization code missing.");
        }

        $tokenInfo = $this->discordOAuthClient->getAccessToken($code, $clientId, $clientSecret, $redirectUri);

        if (!$tokenInfo) {
            die("Failed to get access token.");
        }

        $userInfo = $this->discordOAuthClient->getUserInfo($tokenInfo['access_token']);

        if (!$userInfo) {
            die("Failed to fetch Discord user data.");
        }

        // Store user info in session
        $_SESSION['discord_user'] = $userInfo;
    

        // Extract all required data
        $discordId = $userInfo['id'];
        $discordUsername = $userInfo['username'];
        $discordEmail = $userInfo['email']; 
        $discordAvatar = $userInfo['avatar'] ?? null;
        $accessToken = $tokenInfo['access_token'];
        $refreshToken = $tokenInfo['refresh_token'] ?? null;
        $expiresIn = $tokenInfo['expires_in'] ?? null;

        if ($this->bannedusers->checkBan($discordEmail)) {
            header('Location: /?message=Your account has been banned.');
            return;
        }

        if ($isMobile) {
            $this->handleMobileFlow($discordId, $discordUsername, $discordEmail, $discordAvatar, $accessToken, $refreshToken, $expiresIn);
            return;
        }

        $existingUser = $this->googleUser->getUserByDiscordId($discordId);

        if ($existingUser) {

            $outcome = $this->logInService->resumeWebSession($existingUser);
            $user = $outcome->userRow;

            if (!$user) {
                header('Location: /signup?message=Create your account.');
                return;
            }

            $discordUser = $this->discord->getDiscordAccount($user['user_id']);

            if (!$discordUser) {
                $this->discord->saveDiscordData($user['user_id'], $discordId, $discordUsername, $discordEmail, $discordAvatar, $accessToken, $refreshToken);
            }

            if ($outcome->destination === LoginDestination::ONBOARDED) {
                header('Location: /swiping?message=Connected successfully.');
                return;
            }

            if ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
                header('Location: /signup?message=Create your Looking for account.');
                return;
            }

            if ($outcome->game === 'League of Legends') {
                header('Location: /signup?message=Create your LoL account.');
            } else {
                header('Location: /signup?message=Create your Valorant account.');
            }
            return;
        } else {

            $googleUser = $this->googleUser->getGoogleUserByEmail($discordEmail);

            if($googleUser) {
                header('Location: /?message=An URSG account already exist with that email.');
                return;
            }

            $fullName = $discordUsername;
            $firstName = $discordUsername;
            $googleFamilyName = $discordUsername;
            $RSO = 0;

            $outcome = $this->signUpService->createWebIdentity($discordId, $fullName, $firstName, $googleFamilyName, $discordEmail, $RSO);

            if ($outcome) {
                header('Location: /signup?message=Account created');
                return;
            }
        }
    }

    public function sendMessageDiscord()
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $userId = (int)$_POST['userId'];

        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $user = $this->user->getUserById($userId);

        $oldTimeRaw = $_POST['oldTime'] ?? null;
        $oldTimeFormatted = str_replace('+', ' ', $oldTimeRaw);
        $oldTime = strtotime($oldTimeFormatted);

        if (time() - $oldTime < 120) {
            echo json_encode(['success' => false, 'message' => 'Please wait before sending another request']);
            return;
        }

        $account = $_POST['account'] ?? null;
        $extraMessage = $_POST['extraMessage'] ?? null;
        $server = "Unknown";

        if (!isset($user['user_game'])) {
            echo json_encode(['success' => false, 'message' => 'User game not defined']);
            return;
        }

        $game = $user['user_game'];

        if ($game === 'League of Legends') {
            if ($user['lol_verified'] == 1) {
                $lolUser = $this->leagueOfLegends->getLeageUserByUserId($user['user_id']);
                if ($lolUser) {
                    $account = $lolUser['lol_account'];
                    $server = $lolUser['lol_server'];
                } else {
                    echo json_encode(['success' => false, 'message' => 'No League account found for verified user']);
                    return;
                }
            } else {
                if (!$account) {
                    echo json_encode(['success' => false, 'message' => 'No League account provided for unverified user']);
                    return;
                } else {
                    $server = $user['lol_server'] ?? 'Unknown';
                }
            }
        } elseif ($game === 'Valorant') {
            if (!$account) {
                echo json_encode(['success' => false, 'message' => 'No Valorant account provided']);
                return;
            } else {
                $server = $user['valorant_server'] ?? 'Unknown';
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Unsupported game type']);
            return;
        }

        require_once 'keys.php';
        $botToken = $discordToken;

        if ($game == 'League of Legends') {
            $channelId = "1263123769866850406";
        } elseif ($game == 'Valorant') {
            $channelId = "1263123785716858880";
        }

        $playerFinder = $_POST['playerfinder'] ?? null;
        $voiceChat = $_POST['voiceChat'] ?? null;
        $roleLookingFor = $_POST['roleLookingFor'] ?? null;
        $rankLookingFor = $_POST['rankLookingFor'] ?? null;

        // Build embed fields
        $embedFields = [
            [
                "name" => "🌍 Server",
                "value" => $server ? $server : "Not specified",
                "inline" => true
            ],
            [
                "name" => "🎮 Account",
                "value" => "`$account`",
                "inline" => true
            ]
        ];

        if ($playerFinder) {
            $embedFields[] = [
                "name" => "🎧 Voice Chat",
                "value" => $voiceChat ? "🎤 Looking for voice chat" : "🙊 Not looking for voice chat",
                "inline" => false
            ];
            $embedFields[] = [
                "name" => "🧩 Role looking for",
                "value" => $roleLookingFor ? $roleLookingFor : "Not specified",
                "inline" => true
            ];
            $embedFields[] = [
                "name" => "📈 Rank looking for",
                "value" => $rankLookingFor ? $rankLookingFor : "Not specified",
                "inline" => true
            ];
        }

        $hasDiscordAccount = $this->discord->getDiscordAccount($userId);
        if ($hasDiscordAccount) {
            $avatarUrl = "https://cdn.discordapp.com/avatars/{$hasDiscordAccount['discord_id']}/{$hasDiscordAccount['discord_avatar']}.png";
            $embedFields[] = [
                "name" => "🔗 Discord Account",
                "value" => "<@{$hasDiscordAccount['discord_id']}>",
                "inline" => true
            ];
            $embed = [
                "title" => "{$hasDiscordAccount['discord_username']} is looking for players!",
                "color" => hexdec("F47FFF"),
                "fields" => $embedFields,
                "timestamp" => date("c"),
                "thumbnail" => ["url" => $avatarUrl]
            ];
        } else {
            $embed = [
                "title" => "{$user['user_username']} is looking for players!",
                "color" => hexdec("F47FFF"), // A pinkish embed color
                "fields" => $embedFields,
                "timestamp" => date("c")
            ];
        }


        if (!empty($extraMessage)) {
            $embed["description"] = "📣 *$extraMessage*";
        }

        $data = [
            "username" => "URSG bot",
            "embeds" => [$embed]
        ];

        $url = "https://discord.com/api/v10/channels/{$channelId}/messages";
        $response = $this->makeDiscordRequest($url, $data, $botToken);

        if (isset($response['id'])) {
            echo json_encode(['success' => true, 'messageId' => $response['id']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message', 'details' => $response]);
        }
    }

    public function sendMessageDiscordPhone()
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $userId = (int)$_POST['userId'];

        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $user = $this->user->getUserById($userId);

        $oldTimeRaw = $_POST['oldTime'] ?? null;
        $oldTimeFormatted = str_replace('+', ' ', $oldTimeRaw);
        $oldTime = strtotime($oldTimeFormatted);

        if (time() - $oldTime < 120) {
            echo json_encode(['success' => false, 'message' => 'Please wait before sending another request']);
            return;
        }

        $account = $_POST['account'] ?? null;
        $extraMessage = $_POST['extraMessage'] ?? null;
        $server = "Unknown";

        if (!isset($user['user_game'])) {
            echo json_encode(['success' => false, 'message' => 'User game not defined']);
            return;
        }

        $game = $user['user_game'];

        if ($game === 'League of Legends') {
            if ($user['lol_verified'] == 1) {
                $lolUser = $this->leagueOfLegends->getLeageUserByUserId($user['user_id']);
                if ($lolUser) {
                    $account = $lolUser['lol_account'];
                    $server = $lolUser['lol_server'];
                } else {
                    echo json_encode(['success' => false, 'message' => 'No League account found for verified user']);
                    return;
                }
            } else {
                if (!$account) {
                    echo json_encode(['success' => false, 'message' => 'No League account provided for unverified user']);
                    return;
                } else {
                    $server = $user['lol_server'] ?? 'Unknown';
                }
            }
        } elseif ($game === 'Valorant') {
            if (!$account) {
                echo json_encode(['success' => false, 'message' => 'No Valorant account provided']);
                return;
            } else {
                $server = $user['valorant_server'] ?? 'Unknown';
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Unsupported game type']);
            return;
        }

        require_once 'keys.php';
        $botToken = $discordToken;

        if ($game == 'League of Legends') {
            $channelId = "1263123769866850406";
        } elseif ($game == 'Valorant') {
            $channelId = "1263123785716858880";
        }

        $playerFinder = $_POST['playerfinder'] ?? null;
        $voiceChat = $_POST['voiceChat'] ?? null;
        $roleLookingFor = $_POST['roleLookingFor'] ?? null;
        $rankLookingFor = $_POST['rankLookingFor'] ?? null;

        // Build embed fields
        $embedFields = [
            [
                "name" => "🌍 Server",
                "value" => $server ? $server : "Not specified",
                "inline" => true
            ],
            [
                "name" => "🎮 Account",
                "value" => "`$account`",
                "inline" => true
            ]
        ];

        if ($playerFinder) {
            $embedFields[] = [
                "name" => "🎧 Voice Chat",
                "value" => $voiceChat ? "🎤 Looking for voice chat" : "🙊 Not looking for voice chat",
                "inline" => false
            ];
            $embedFields[] = [
                "name" => "🧩 Role looking for",
                "value" => $roleLookingFor ? $roleLookingFor : "Not specified",
                "inline" => true
            ];
            $embedFields[] = [
                "name" => "📈 Rank looking for",
                "value" => $rankLookingFor ? $rankLookingFor : "Not specified",
                "inline" => true
            ];
        }

        $embed = [
            "title" => "{$user['user_username']} is looking for players!",
            "color" => hexdec("F47FFF"), // A pinkish embed color
            "fields" => $embedFields,
            "timestamp" => date("c")
        ];

        if (!empty($extraMessage)) {
            $embed["description"] = "📣 *$extraMessage*";
        }

        $data = [
            "username" => "URSG bot",
            "embeds" => [$embed]
        ];

        $url = "https://discord.com/api/v10/channels/{$channelId}/messages";
        $response = $this->makeDiscordRequest($url, $data, $botToken);

        if (isset($response['id'])) {
            echo json_encode(['success' => true, 'messageId' => $response['id']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message', 'details' => $response]);
        }
    }

    public function discordClaim() 
    {
        require_once 'keys.php';

        $clientId = $discordClientId;
        $clientSecret = $discordClientSecret;
        $redirectUri = "https://ur-sg.com/discordClaim";

        $roleType = $_GET['state'] ?? null;
        
        if(!in_array($roleType, ['ursg gold', 'ursg ascend'])) {
            header("Location: /store?message=Role is not valid.");
            return;
        }

        // For the role, check that user actually owns it
        $userId = $_SESSION['userId'] ?? null;
        if (!$userId) {
            header("Location: /store?message=You must be logged in to claim a role.");
            return;
        }

        switch($roleType) {
            case 'ursggold':
                if ($user['user_isGold'] != 1) {
                    header("Location: /store?message=You do not own the gold role.");
                    return;
                }
                break;
            case 'ursgascend':
                if ($user['user_isAscend'] != 1) {
                    header("Location: /store?message=You do not own the Ascend role.");
                    return;
                }
                break;
        }

        $roleIds = [
            'ursg gold' => 1375359507063902219,
            'ursg ascend'   => 1429461787534950563,
        ];

        $roleId = $roleIds[$roleType];

        $code = $_GET['code'] ?? null;
        if (!$code) {
            die("Authorization code missing.");
        }

        // Step 1: Get access token
        $tokenResponse = file_get_contents("https://discord.com/api/oauth2/token", false, stream_context_create([
            "http" => [
                "method" => "POST",
                "header" => "Content-Type: application/x-www-form-urlencoded",
                "content" => http_build_query([
                    "client_id" => $clientId,
                    "client_secret" => $clientSecret,
                    "grant_type" => "authorization_code",
                    "code" => $code,
                    "redirect_uri" => $redirectUri,
                ])
            ]
        ]));
        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) die("Failed to get Discord access token.");

        // Step 2: Get user data
        $userInfo = json_decode(file_get_contents("https://discord.com/api/users/@me", false, stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "Authorization: Bearer {$accessToken}"
            ]
        ])), true);
        $discordId = $userInfo['id'] ?? null;
        if (!$discordId) die("Failed to fetch Discord user.");

        // Step 3: Check if user is in the server
        $checkUrl = "https://discord.com/api/guilds/{$discordServerId}/members/{$discordId}";
        $memberResponse = file_get_contents($checkUrl, false, stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "Authorization: Bot {$discordToken}",
                "ignore_errors" => true
            ]
        ]));

        if (strpos($http_response_header[0] ?? '', "200") === false) {
            file_put_contents("discord_log.txt", "[User not in server] ID: {$discordId}\n", FILE_APPEND);
            die("You must join the Discord server before claiming your role.");
        }

        // Step 4: Add role with PUT request (instead of makeDiscordRequest)
        $url = "https://discord.com/api/guilds/{$discordServerId}/members/{$discordId}/roles/{$roleId}";
        $putContext = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => "Authorization: Bot {$discordToken}\r\nContent-Length: 0\r\n",
                'content' => '', // PUT must have content
                'ignore_errors' => true
            ]
        ]);
        $response = file_get_contents($url, false, $putContext);

        // Debug output
        if (strpos($http_response_header[0] ?? '', "204") === false) {
            file_put_contents('discord_log.txt', "[Role Assignment Failed] Response:\n" . print_r($http_response_header, true), FILE_APPEND);
            die("There was a problem assigning your gold role. Please contact support.");
        }

        // Step 5: Re-check if role was added (optional but recommended)
        $checkRoles = file_get_contents("https://discord.com/api/guilds/{$discordServerId}/members/{$discordId}", false, stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "Authorization: Bot {$discordToken}",
                "ignore_errors" => true
            ]
        ]));
        $rolesInfo = json_decode($checkRoles, true);
        $hasRole = in_array($roleId, $rolesInfo['roles'] ?? []);

        if (!$hasRole) {
            file_put_contents('discord_log.txt', "[Role Assignment Failed] Role: {$roleType}\nResponse:\n" . print_r($http_response_header, true), FILE_APPEND);
            die("There was a problem assigning your {$roleType} role. Please contact support.");
        }

        header("Location: /store?message=" . ucfirst($roleType) . " role assigned successfully.");
        return;
    }

    public function startBotCronJob()
    {
        require_once 'keys.php';
    
        $tokenAdmin = $_GET['token'] ?? null;
    
        if (!isset($tokenAdmin) || $tokenAdmin !== $tokenRefresh) { 
            http_response_code(401); // Return Unauthorized for cron logs
            echo "❌ Unauthorized.\n";
            return;
        }

        $result = DiscordBotService::start();

        if ($result['success']) {
            echo "✅ Bot started successfully.\n";
        } else {
            echo "❌ Failed to start bot: " . $result['message'] . "\n";
            echo "Output: " . $result['output'] . "\n";
        }
    }

      public function connectDiscordMobile()
    {
        if (!isset($_GET['phoneData'])) {
            echo json_encode(['success' => false, 'message' => 'Missing phone data']);
            header("Location: /?error=Incorrect phone data");
            return;
        }

        // Generate a simple token to mark this as mobile flow
        $discordToken = bin2hex(random_bytes(16));

        $_SESSION['phoneData'] = $_GET['phoneData'];
        $_SESSION['discordConnectMobile'] = $discordToken; // identify mobile flow

        // Redirect to Discord OAuth
        require 'keys.php';
        $discordAuthUrl = "https://discord.com/oauth2/authorize?client_id=1354386306746159235&response_type=code&redirect_uri=https%3A%2F%2Fur-sg.com%2FdiscordData&scope=email+identify+guilds+connections";

        header("Location: $discordAuthUrl");
        return;
    }

    public function discordBind()
    {
        // Use data received from discord to bind account
        require_once 'keys.php';
        $clientId = $discordClientId;
        $clientSecret = $discordClientSecret;
        $redirectUri = "https://ur-sg.com/discordBind";

        $code = $_GET['code'] ?? null;

        if (!$code) {
            die("Authorization code missing.");
        }
        
        $tokenUrl = "https://discord.com/api/oauth2/token";
        $data = [
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "grant_type" => "authorization_code",
            "code" => $code,
            "redirect_uri" => $redirectUri,
        ];

                $options = [
            "http" => [
                "header" => "Content-Type: application/x-www-form-urlencoded",
                "method" => "POST",
                "content" => http_build_query($data),
            ],
        ];
    
        $context = stream_context_create($options);
        $response = file_get_contents($tokenUrl, false, $context);
        $tokenInfo = json_decode($response, true);
    
        if (!isset($tokenInfo['access_token'])) {
            die("Failed to get access token.");
        }
    
        // Use the access token to get user info
        $userInfoUrl = "https://discord.com/api/users/@me";
        $options = [
            "http" => [
                "header" => "Authorization: Bearer " . $tokenInfo['access_token'],
                "method" => "GET",
            ],
        ];
    
        $context = stream_context_create($options);
        $response = file_get_contents($userInfoUrl, false, $context);
        $userInfo = json_decode($response, true);
    
        if (!isset($userInfo['id'])) {
            die("Failed to fetch Discord user data.");
        }

        if (!isset($_SESSION['userId'])) {
            die("You must be logged in to bind your Discord account.");
        }

        $discordId = $userInfo['id'];
        $discordUsername = $userInfo['username'];
        $discordEmail = $userInfo['email']; 
        $discordAvatar = $userInfo['avatar'] ?? null;
        $accessToken = $tokenInfo['access_token'];
        $refreshToken = $tokenInfo['refresh_token'] ?? null;
        $expiresIn = $tokenInfo['expires_in'] ?? null;
        $userId = $_SESSION['userId'];

        $hasDiscordAccount = $this->discord->getDiscordAccount($userId);
        $bindDiscord = false;
        if ($hasDiscordAccount) {
            $updateDiscord = $this->discord->updateDiscordData($userId, $discordId, $discordUsername, $discordEmail, $discordAvatar, $accessToken, $refreshToken);
            if ($updateDiscord) {
                $bindDiscord = true;
            }
        } else {
            $bindDiscord = $this->discord->saveDiscordData($userId, $discordId, $discordUsername, $discordEmail, $discordAvatar, $accessToken, $refreshToken);
        }

        if ($bindDiscord) {
            // Update social links
            $updateDiscord = $this->user->updateDiscord($userId, $discordUsername);
            if ($updateDiscord) {
                // Give discord badge 
                $badge = $this->items->getBadgeByName("Discord account");
                if ($badge && !$this->items->userOwnsItem($userId, $badge['items_id'])) {
                    $this->items->addItemToUser($userId, $badge['items_id']);
                }
                header('Location: /userProfile?message=Discord account linked successfully.');
                return;
            } else {
                header('Location: /userProfile?error=Failed to update Discord username.');
                return;
            }
        } else {
            header('Location: /userProfile?error=Failed to link Discord account. It might be already linked to another user.');
            return;
        }
    }

    public function handleMobileFlow($discordId, $discordUsername, $discordEmail, $discordAvatar, $accessToken, $refreshToken, $expiresIn)
    {
        $existingUser = $this->googleUser->getUserByDiscordId($discordId);

        $cookieOptions = [
            'expires' => time() + 60 * 60 * 24 * 60,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ];

        // If user exists, allow connection on mobile, otherwise create account
        if ($existingUser)
        {
            $outcome = $this->logInService->resumeMobileProfile($existingUser);
            setcookie("auth_token", $outcome->masterToken, $cookieOptions);

            if (!$outcome->userExists) {
                $response = array(
                    'message' => 'Success',
                    'newUser' => false,
                    'googleUser' => $outcome->identityRow,
                    'userExists' => false
                );
                $this->handleMobileFlowSuccess('Create your account.', $response);
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
                    $this->handleMobileFlowSuccess('Create your League account.', $response);
                    return;
                }

                if ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
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
                    $this->handleMobileFlowSuccess('Create your Looking for account.', $response);
                    return;
                }

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
                $this->handleMobileFlowSuccess('Account connected', $response);
                return;
            }

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
                $this->handleMobileFlowSuccess('Create your Valorant account.', $response);
                return;
            }

            if ($outcome->destination === LoginDestination::NEEDS_LOOKING_FOR) {
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
                $this->handleMobileFlowSuccess('Create your Looking for account.', $response);
                return;
            }

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
            $this->handleMobileFlowSuccess('Account connected', $response);
            return;
        }
        else
        {
            $fullName = $discordUsername;
            $firstName = $discordUsername;
            $googleFamilyName = $discordUsername;
            $RSO = 0;

            $outcome = $this->signUpService->createMobileIdentity($discordId, $fullName, $firstName, $googleFamilyName, $discordEmail, $RSO);

            if ($outcome) {
                setcookie("auth_token", $outcome->masterToken, $cookieOptions);

                $response = array(
                    'message' => 'Success',
                    'newUser' => true,
                    'googleUser' => $outcome->identityRow,
                );
                $this->handleMobileFlowSuccess('Create your account.', $response);
            }

        }
    }

    protected function mobileFlowSessionKey(): string
    {
        return 'discordConnectMobile';
    }

    protected function mobileDeepLinkCallback(): string
    {
        return 'discordCallback';
    }
}

