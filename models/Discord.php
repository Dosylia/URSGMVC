<?php
namespace models;

use config\DataBase;

class Discord extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function saveDiscordData($userId, $discordId, $discordUsername, $discordAvatar, $accessToken, $refreshToken) 
    {
        $query = $this->bdd->prepare("
                            INSERT INTO `discord`(
                                                `user_id`,
                                                `discord_id`,
                                                `discord_username`,                               
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
                                                ?
                                            )
        ");

        $saveDiscordData = $query->execute([$userId, $discordId, $discordUsername, $discordAvatar, $accessToken, $refreshToken]);
    
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
        $discordUsername = $query->fetch(\PDO::FETCH_ASSOC);
        return $discordUsername;
    }

    public function storeTemporaryChannel($channelId)
    {
        $query = $this->bdd->prepare("
                            INSERT INTO `temporary_channels`(
                                                `channel_id`,
                                                `expiry_time`
                                            )
                                            VALUES (
                                                ?,
                                                NOW() + INTERVAL 3600 SECOND
                                            )
        ");

        $storeTemporaryChannel = $query->execute([$channelId]);
    
        if($storeTemporaryChannel)
        {
            return true;
        }
        else 
        {
            return false;  
        }
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
            WHERE `expiry_time` > ?
        ");
    
        $query->execute([time()]);
        $nonExpiredChannels = $query->fetchAll();
        return $nonExpiredChannels;
    }
}
