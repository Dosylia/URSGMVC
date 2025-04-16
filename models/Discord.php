<?php
namespace models;

use config\DataBase;

class Discord extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function saveDiscordData($userId, $discordId, $discordUsername, $discordEmail, $discordAvatar, $accessToken, $refreshToken) 
    {
        $query = $this->bdd->prepare("
                            INSERT INTO `discord`(
                                                `user_id`,
                                                `discord_id`,
                                                `discord_username`, 
                                                `discord_email`,                              
                                                `discord_avatar`,
                                                `access_token`,
                                                `refresh_token`
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
        ");

        $saveDiscordData = $query->execute([$userId, $discordId, $discordUsername, $discordEmail, $discordAvatar, $accessToken, $refreshToken]);
    
        if($saveDiscordData)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }  

    public function getDiscordUsername($userId)
    {
        $query = $this->bdd->prepare("
                            SELECT `discord_username`
                            FROM `discord`
                            WHERE `user_id` = ?
        ");

        $query->execute([$userId]);
        $discordUsername = $query->fetch();
        return $discordUsername;
    }

    public function getDiscordByUsername($username)
    {
        $query = $this->bdd->prepare("
                            SELECT
                                *
                            FROM `discord`
                            WHERE `discord_username` = ?
        ");

        $query->execute([$username]);
        $discordUsername = $query->fetch();

        if($discordUsername)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function storeTemporaryChannel($channelId, $userId)
    {
        $query = $this->bdd->prepare("
            INSERT INTO `temporary_channels` (
                `channel_id`,
                `user_id`,
                `created_at`,
                `expiry_time`
            )
            VALUES (
                ?,
                ?,
                NOW(),
                NOW() + INTERVAL 3600 SECOND
            )
        ");
    
        return $query->execute([$channelId, $userId]);
    }

    public function hasCreatedChannelRecently($userId, $minutes = 10)
    {
        $query = $this->bdd->prepare("
            SELECT COUNT(*) FROM `temporary_channels`
            WHERE `user_id` = ? AND `created_at` > (NOW() - INTERVAL ? MINUTE)
        ");
        $query->execute([$userId, $minutes]);
        return $query->fetchColumn() > 0;
    }
        

    public function removeTemporaryChannel($channelId)
    {
        $query = $this->bdd->prepare("
                            DELETE FROM `temporary_channels`
                            WHERE `channel_id` = ?
        ");

        $deleteTemporaryChannel = $query->execute([$channelId]);
    
        if($deleteTemporaryChannel)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function getExpiredChannels()
    {
        $query = $this->bdd->prepare("
        SELECT `channel_id`
        FROM `temporary_channels`
        WHERE DATE_ADD(`created_at`, INTERVAL 1 HOUR) < NOW()
        ");
        
        $query->execute();
        $expiredChannels = $query->fetchAll();
        return $expiredChannels;
    }
}
