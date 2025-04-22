<?php

namespace controllers;

use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use models\UserLookingFor;
use models\GoogleUser;
use models\ChatMessage;
use models\Discord;
use traits\SecurityController;

class DiscordController
{
    use SecurityController;
    private LeagueOfLegends $leagueOfLegends;
    private User $user;
    private Valorant $valorant;
    private GoogleUser $googleUser;
    private UserLookingFor $userlookingfor;
    private Discord $discord;
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
    }

    public function createChannel() {

        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        if ($this->discord->hasCreatedChannelRecently($userId)) {
            echo json_encode(['success' => false, 'error' => 'You have to wait before creating another channel.']);
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
            echo json_encode(['success' => false, 'error' => 'Failed to create channel']);
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
            echo json_encode(['success' => false, 'error' => 'Failed to create invite']);
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
            exit();
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
        $clientId = $discordClientId;
        $clientSecret = $discordClientSecret;
        $redirectUri = "https://ur-sg.com/discordData";

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

        $existingUser = $this->googleUser->getUserByDiscordId($discordId);

        if ($existingUser) {
            $_SESSION['google_userId'] = $existingUser['google_userId'];
            $_SESSION['google_id'] = $discordId;
            $_SESSION['email'] = $existingUser['google_email'];
            $_SESSION['full_name'] = $existingUser['google_fullName'];
            $_SESSION['google_firstName'] = $existingUser['google_firstName'];
            $_SESSION['masterTokenWebsite'] = $existingUser['google_masterTokenWebsite'];

            $googleUser = $this->user->getUserDataByGoogleUserId($existingUser['google_userId']);

            if ($googleUser)
            {
                $user = $this->user->getUserByUsername($googleUser['user_username']);

                if ($user) 
                {
                    $_SESSION['userId'] = $user['user_id'];
                    $_SESSION['username'] = $user['user_username'];
                    $_SESSION['gender'] = $user['user_gender'];
                    $_SESSION['age'] = $user['user_age'];
                    $_SESSION['kindOfGamer'] = $user['user_kindOfGamer'];
                    $_SESSION['game'] = $user['user_game'];

                    if ($user['user_game'] == 'League of Legends') {
                        $lolUser = $this->leagueOfLegends->getLeageUserByUserId($user['user_id']);

                        if ($lolUser)
                        {
                            $_SESSION['lol_id'] = $lolUser['lol_id'];
                            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
                            if ($lfUser)
                            {
                                $_SESSION['lf_id'] = $lfUser['lf_id'];
                                header('Location: /swiping?message=Connected successfully.');
                                exit();
                            }
                            else 
                            {
                                header('Location: /signup?message=Create your Looking for account.');
                                exit();
                            }
                        }
                        else 
                        {
                            header('Location: /signup?message=Create your LoL account.');
                            exit();
                        }
                    }
                    else 
                    {
                        $valorantUser = $this->valorant->getValorantUserByUserId($user['user_id']);

                        if ($valorantUser)
                        {

                            $_SESSION['valorant_id'] = $valorantUser['valorant_id'];
            
                            $lfUser = $this->userlookingfor->getLookingForUserByUserId($user['user_id']);
                            if ($lfUser)
                            {
                                $_SESSION['lf_id'] = $lfUser['lf_id'];
                                header('Location: /swiping?message=Connected successfully.');
                                exit();
                            }
                            else 
                            {
                                header('Location: /signup?message=Create your Looking for account.');
                                exit();
                            }

                        }
                        else 
                        {
                            header('Location: /signup?message=Create your Valorant account.');
                            exit();
                        }

                    }

                }
                else 
                {
                    header('Location: /signup?message=Create your account.');
                    exit();
                }
            }
            else 
            {
                header('Location: /signup?message=Create your account.');
                exit();
            }
        } else {

            $googleUser = $this->googleUser->getGoogleUserByEmail($discordEmail);

            if($googleUser) {
                header('Location: /?message=An URSG account already exist with that email.');
                exit();
            }

            $fullName = $discordUsername;
            $firstName = $discordUsername;
            $googleFamilyName = $discordUsername;
            $RSO = 0;
            $createGoogleUserDiscord = $this->googleUser->createGoogleUser($discordId, $fullName, $firstName, $googleFamilyName,  $RSO, $discordEmail);

            if ($createGoogleUserDiscord)
            {
                require 'keys.php';

                $lifetime = 7 * 24 * 60 * 60;

                session_destroy();

                session_set_cookie_params($lifetime);

                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }

                // MASTER TOKEN SYSTEM
                $token = bin2hex(random_bytes(32));
                $createToken = $this->googleUser->storeMasterTokenWebsite($createGoogleUserDiscord, $token);

                if ($createToken) {
                    $_SESSION['masterTokenWebsite'] = $token;
                }
                
                if (!isset($_SESSION['googleId'])) {
                    $_SESSION['google_userId'] = $createGoogleUserDiscord;
                    $_SESSION['google_id'] = $discordId;
                    $_SESSION['email'] = $discordEmail;
                    $_SESSION['full_name'] = $fullName;
                }

                header('Location: /signup?message=Account created');
                exit();

            }
        }
    }

    public function validateTokenWebsite($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenWebsiteByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterTokenWebsite'])) {
            $storedToken = $storedTokenData['google_masterTokenWebsite'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }
}

