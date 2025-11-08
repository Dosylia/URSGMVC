<?php
namespace models;

use config\DataBase;

class GoogleUser extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() 
    {
        $this->bdd = $this->getBdd();
    }

    public function userExist($googleId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                `google_userId`,
                                                `google_id`,
                                                `google_fullName`,
                                                `google_firstName`,                                                
                                                `google_lastName`,
                                                `google_email`,
                                                `google_masterTokenWebsite`,
                                                `google_masterToken`
                                            FROM
                                                `googleuser`
                                            WHERE
                                                `google_id` = ?

        ");

        $query -> execute([$googleId]);
        $googleIdTest = $query -> fetch();

        if ($googleIdTest)
        {
            return $googleIdTest;
        } 
        else
        {
            return false;
        }
    }

    public function unsubscribeMails($email)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `googleuser`
                                        SET 
                                            `google_unsubscribeMails` = 1
                                        WHERE 
                                            `google_email` = ?
        ");
    
        $unsubscribeMails = $query->execute([$email]);
        
        if ($unsubscribeMails) {
            return true;
        } else {
            return false;
        }
    }

    // public function createGoogleUser($googleId,$googleFullName,$googleFirstName,$googleFamilyName,$googleEmail)
    // {
    //     $query = $this -> bdd -> prepare("
    //                                         INSERT INTO `googleuser`(
    //                                             `google_id`,
    //                                             `google_fullName`,
    //                                             `google_firstName`,                                                
    //                                             `google_lastName`,
    //                                             `google_email`
    //                                         )
    //                                         VALUES (
    //                                             ?,
    //                                             ?,
    //                                             ?,
    //                                             ?,
    //                                             ?
    //                                         )
    //     ");

    //     $createGoogleUser = $query -> execute([$googleId,$googleFullName,$googleFirstName,$googleFamilyName,$googleEmail]);

    //     if($createGoogleUser)
    //     {
    //         return $this->bdd-> lastInsertId();
    //     } else {
    //         return false;
    //     }
    // }

    public function createGoogleUser($googleId,$googleFullName,$googleFirstName,$googleFamilyName, $RSO, $googleEmail)
    {
        $query = $this -> bdd -> prepare("
                                            INSERT INTO `googleuser`(
                                                `google_id`,
                                                `google_fullName`,
                                                `google_firstName`,            
                                                `google_lastName`,
                                                `google_email`,
                                                `google_createdWithRSO`,
                                                `google_confirmEmail`
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                TRUE
                                            )
        ");

        $createGoogleUser = $query -> execute([$googleId,$googleFullName,$googleFirstName,$googleFamilyName, $googleEmail, $RSO]);

        if($createGoogleUser)
        {
            return $this->bdd-> lastInsertId();
        } else {
            return false;
        }
    }

    public function getGoogleUserByEmail($email) 
    {
        $query = $this -> bdd -> prepare ("
                                            SELECT
                                            `google_userId`,
                                            `google_id`,
                                            `google_fullName`,
                                            `google_firstName`,
                                            `google_lastName`,
                                            `google_email`,
                                            `google_confirmEmail`
                                            FROM
                                                `googleuser`
                                            WHERE                                            
                                                `google_email` = ?
        ");

        $query -> execute([$email]);
        $emailTest = $query -> fetch();

        if($emailTest)
        {
            return $emailTest;
        }
        else
        {
            return false;
        }
    }

    public function getGoogleUsersMailingCronJob()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            u.user_id,
                                            u.user_username,
                                            g.google_userId,
                                            g.google_email,
                                            g.last_notified_at
                                        FROM googleuser AS g
                                        INNER JOIN user AS u ON g.google_userId = u.google_userId
                                        WHERE 
                                            g.google_unsubscribeMails != 1
                                            AND (g.last_notified_at IS NULL OR g.last_notified_at < NOW() - INTERVAL 14 DAY)
                                            AND g.google_createdWithRSO = 0
                                            AND g.google_createdWithDiscord = 0
                                            AND u.user_lastRequestTime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                        ORDER BY RAND()
                                        LIMIT 10;
        ");

        $query->execute();
        $getGoogleUsers =$query->fetchAll();

        if ($getGoogleUsers) {
            return $getGoogleUsers;
        } else {
            return false;
        }
    }

    public function getUserByEmail($email) 
    {
        $query = $this -> bdd -> prepare ("
                                            SELECT
                                                u.`user_id`,
                                                u.`user_username`,
                                                g.`google_userId`
                                            FROM
                                                `googleuser` AS g
                                            INNER JOIN
                                                `user` AS u ON g.`google_userId` = u.`google_userId`
                                            WHERE                                            
                                                g.`google_email` = ?
        ");

        $query -> execute([$email]);
        $emailTest = $query -> fetch();

        if($emailTest)
        {
            return $emailTest;
        }
        else
        {
            return false;
        }
    }

    public function updateEmailStatus($email)
    {
        $query = $this-> bdd -> prepare ("
                                            UPDATE
                                                `googleuser`
                                            SET
                                                `google_confirmEmail` = TRUE
                                            WHERE
                                                `google_email` = ?
        ");

        $confirmEmail =$query -> execute([$email]);

        if ($confirmEmail)
        {
            return true;
        }
        else
        {
            return false;            
        }
        
    }

    public function deleteAccount($email) 
    {
        $query = $this->bdd->prepare("
                                        DELETE FROM
                                            `googleuser`
                                        WHERE
                                            `google_email` = ?
        ");

        $deleteAccountTest = $query->execute([$email]);

        if($deleteAccountTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function deleteRiotAccount($puuid) 
    {
        $query = $this->bdd->prepare("
                                        DELETE FROM
                                            `googleuser`
                                        WHERE
                                            `google_id` = ?
        ");

        $deleteAccountTest = $query->execute([$puuid]);

        if($deleteAccountTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function storeMasterToken($googleUserId, $token)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `googleuser`
                                        SET 
                                            `google_masterToken` = ?
                                        WHERE 
                                            `google_userId` = ?
        ");
    
         $tokenTest = $query->execute([$token, $googleUserId]);
        
        if ($tokenTest) {
            return true;
        } else {
            return false;
        }
    }

    public function getMasterToken($googleUserId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            `google_masterToken`
                                        FROM 
                                            `googleuser`
                                        WHERE 
                                            `google_userId` = ?
        ");
    
        $query->execute([$googleUserId]);
        $token = $query->fetch();
        
        if ($token) {
            return $token;
        } else {
            return false;
        }
    }

    public function storeMasterTokenWebsite($googleUserId, $token)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `googleuser`
                                        SET 
                                            `google_masterTokenWebsite` = ?
                                        WHERE 
                                            `google_userId` = ?
        ");
    
         $tokenTest = $query->execute([$token, $googleUserId]);
        
        if ($tokenTest) {
            return true;
        } else {
            return false;
        }
    }

    public function getMasterTokenWebsite($googleUserId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            `google_masterTokenWebsite`
                                        FROM 
                                            `googleuser`
                                        WHERE 
                                            `google_userId` = ?
        ");
    
        $query->execute([$googleUserId]);
        $token = $query->fetch();
        
        if ($token) {
            return $token;
        } else {
            return false;
        }
    }

    public function getMasterTokenByUserId($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            g.google_masterToken
                                        FROM 
                                            googleuser AS g
                                        INNER JOIN
                                            `user` AS u ON u.google_userId = g.google_userId
                                        WHERE 
                                            u.user_id = ?
        ");
    
        $query->execute([$userId]);
        $token = $query->fetch();
    
        if ($token) {
            return $token;
        } else {
            return false;
        }
    }

    public function getMasterTokenPhoneByGoogleUserId($googleUserId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            google_masterToken
                                        FROM 
                                            googleuser AS g
                                        WHERE 
                                            google_userId = ?
        ");
    
        $query->execute([$googleUserId]);
        $token = $query->fetch();
    
        if ($token) {
            return $token;
        } else {
            return false;
        }
    }

    public function getMasterTokenWebsiteByUserId($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            g.google_masterTokenWebsite
                                        FROM 
                                            googleuser AS g
                                        INNER JOIN
                                            `user` AS u ON u.google_userId = g.google_userId
                                        WHERE 
                                            u.user_id = ?
        ");
    
        $query->execute([$userId]);
        $token = $query->fetch();
    
        if ($token) {
            return $token;
        } else {
            return false;
        }
    }

    public function getUserByPuuid($puuid) 
    {
        $query = $this->bdd->prepare("
            SELECT
                g.google_userId,
                g.google_id,
                g.google_fullName,
                g.google_firstName,
                g.google_lastName,
                g.google_email,
                g.google_confirmEmail,
                g.google_masterToken,
                g.google_masterTokenWebsite,
                g.google_createdWithRSO,
                g.google_createdWithDiscord,
                g.google_unsubscribeMails,
                g.last_notified_at,
                
                u.user_id,
                u.user_username,
                u.user_gender,
                u.user_age,
                u.user_kindOfGamer,
                u.user_shortBio,
                u.user_picture,
                u.user_bonusPicture,
                u.user_discord,
                u.user_instagram,
                u.user_twitter,
                u.user_twitch,
                u.user_bluesky,
                u.user_game,
                u.user_token,
                u.user_deletionToken,
                u.user_deletionTokenExpiry,
                u.user_currency,
                u.user_isGold,
                u.user_isPartner,
                u.user_isCertified,
                u.user_hasChatFilter,
                u.user_lastRequestTime,
                u.user_lastReward,
                u.user_streak,
                u.user_isOnline,
                u.user_lastSeen,
                u.user_arcane,
                u.user_ignore,
                u.arcane_snapshot,
                u.user_isLooking,
                u.user_requestIsLooking,
                u.user_lastCompletedGame,
                u.user_totalCompletedGame,
                u.user_friendsInvited,
                u.user_notificationPermission,
                u.user_notificationEndPoint,
                u.user_notificationP256dh,
                u.user_notificationAuth,
                u.user_personalityTestResult,
                
                lol.lol_id,
                lol.user_id AS lol_user_id,
                lol.lol_noChamp,
                lol.lol_main1,
                lol.lol_main2,
                lol.lol_main3,
                lol.lol_rank,
                lol.lol_role,
                lol.lol_server,
                lol.lol_account,
                lol.lol_verificationCode,
                lol.lol_verified,
                lol.lol_sUsername,
                lol.lol_sUsernameId,
                lol.lol_sPuuid,
                lol.lol_sLevel,
                lol.lol_sRank,
                lol.lol_sProfileIcon
            FROM
                `googleuser` as g
            LEFT JOIN
                `user` as u ON g.google_userId = u.google_userId
            LEFT JOIN
                `leagueoflegends` as lol ON u.user_id = lol.user_id
            WHERE
                g.`google_id` = ? 
                OR lol.`lol_sPuuid` = ? 
        ");

        $query->execute([$puuid, $puuid]);
        $puuidTest = $query->fetch();

        return $puuidTest ?: false;
    }

    public function getUserByPuuidGoogle($puuid) 
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            *
                                        FROM
                                            `googleuser` as g
                                        LEFT JOIN
                                            `user` as u ON g.google_userId = u.google_userId
                                        LEFT JOIN
                                            `leagueoflegends` as lol ON u.user_id = lol.user_id
                                        WHERE
                                            g.`google_id` = ? 
        ");
    
        $query->execute([$puuid]);
        $puuidTest = $query->fetch();
    
        return $puuidTest ?: false;
    }

    public function getUserByDiscordId($discordId) 
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            *
                                        FROM
                                            `googleuser` as g
                                        WHERE
                                            g.`google_id` = ? 
        ");
    
        $query->execute([$discordId]);
        $discordIdTest = $query->fetch();
    
        return $discordIdTest ?: false;
    }

    public function getGoogleUserByMasterTokenWebsite($masterTokenWebsite) 
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            *
                                        FROM
                                            `googleuser` as g
                                        WHERE
                                            g.`google_masterTokenWebsite` = ? 
        ");
    
        $query->execute([$masterTokenWebsite]);
        $masterTokenTest = $query->fetch();
    
        return $masterTokenTest ?: false;
    }

    public function updateLastNotified($userId)
    {
        $query = $this->bdd->prepare("
                                        UPDATE
                                            `googleuser`
                                        SET
                                            `last_notified_at` = NOW()
                                        WHERE
                                            `google_userId` = ?
        ");

        $updatingLastNotified = $query->execute([$userId]);

        if ($updatingLastNotified) {
            return true;
        } else {
            return false;
        }
    }

}
