<?php

namespace controllers;

use models\User;
use models\Block;
use models\GoogleUser;
use models\ChatMessage;
use models\Discord;
use traits\SecurityController;

class DiscordController
{
    use SecurityController;
    private User $user;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private Discord $discord;
    private $botToken;
    private $guildId;
    private $redirectUri;

    public function __construct()
    {
        $this->user = new User();
        $this -> googleUser = new GoogleUser();
        $this -> discord = new Discord();
        $this->chatmessage = new ChatMessage();
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
            $this->discord->storeTemporaryChannel($channelId); // 1 hour expiry
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
        $botToken = $discordToken; 
        $channels = $this->discord->getExpiredChannels();
    
        foreach ($channels as $channel) {
            $url = "https://discord.com/api/v10/channels/{$channel['channel_id']}";
            
            $headers = [
                "Authorization: Bot {$botToken}",
                "Content-Type: application/json"
            ];
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
            $response = curl_exec($ch);
            curl_close($ch);
    
            // Remove channel from the database
            $this->discord->removeTemporaryChannel($channel['channel_id']);
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
    
        // Get logged-in user ID (assumed stored in session)
        $userId = $_SESSION['userId'] ?? null;
    
        if (!$userId) {
            die("User must be logged in.");
        }
    
        // Extract all required data
        $discordId = $userInfo['id'];
        $discordUsername = $userInfo['username'];
        $discordAvatar = $userInfo['avatar'] ?? null;
        $accessToken = $tokenInfo['access_token'];
        $refreshToken = $tokenInfo['refresh_token'] ?? null;
        $expiresIn = $tokenInfo['expires_in'] ?? null;
    
        // Save data in database
        $discordData = $this->discord->saveDiscordData($userId, $discordId, $discordUsername, $discordAvatar, $accessToken, $refreshToken);

        if (!$discordData) {
            die("Failed to save data.");
        }
    
        // Redirect to the create channel page
        header("Location: /userProfile?message==Discord account linked successfully.");
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

