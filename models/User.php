<?php
namespace models;

use config\DataBase;

class User extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function getUserByUsername($username)
    {

        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `user`
                                            WHERE
                                                `user_username` = ?
        ");

        $query -> execute([$username]);
        $user = $query -> fetch();


        if ($user)
        {
            return $user;
        }
        else
        {
            return false;
        }

    }

    public function getCurrencyByUserId($userId)
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                `user_currency`
                                            FROM
                                                `user`
                                            WHERE
                                                `user_id` = ?
        ");

        $query -> execute([$userId]);
        $currency = $query -> fetch();

        if ($currency) {
            return $currency;
        } else {
            return false;
        }
    }

    public function addCurrency($userId, $currency)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_currency` = `user_currency` + ?
                                            WHERE
                                                `user_id` = ?
        ");

        $addCurrencyTest = $query -> execute([$currency, $userId]);

        if ($addCurrencyTest) {
            return true;
        } else {
            return false;
        }
    }

    public function addCurrencySnapshot($userId, $currency)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `arcane_snapshot` = `arcane_snapshot` + ?
                                            WHERE
                                                `user_id` = ?
        ");

        $addCurrencyTest = $query -> execute([$currency, $userId]);

        if ($addCurrencyTest) {
            return true;
        } else {
            return false;
        }
    }

    public function updateSide($side, $userId, $currency)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_arcane` = ?,
                                                `arcane_snapshot` = ?
                                            WHERE
                                                `user_id` = ?
        ");

        $pickSideTest = $query -> execute([$side, $currency, $userId]);

        if ($pickSideTest) {
            return true;
        } else {
            return false;
        }
    }

    public function ignoreSide($userId)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_ignore` = 1
                                            WHERE
                                                `user_id` = ?
        ");

        $ignoreSideTest = $query -> execute([$userId]);

        if ($ignoreSideTest) {
            return true;
        } else {
            return false;
        }
    }

    public function UpdateCurrency($userId, $currency)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_currency` =  ?
                                            WHERE
                                                `user_id` = ?
        ");

        $updateCurrencyTest = $query -> execute([$currency, $userId]);

        if ($updateCurrencyTest) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserById($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            u.*,
                                            l.*,
                                            v.*,
                                            lf.*,
                                            (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(u.user_lastRequestTime) <= 45) AS user_isOnline,
                                            (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(u.user_requestIsLooking) <= 300) AS user_isLooking,
                                            g.google_email,
                                            g.google_createdWithRSO
                                        FROM
                                            `user` AS u
                                        LEFT JOIN
                                            `leagueoflegends` AS l ON u.user_id = l.user_id
                                        LEFT JOIN
                                            `valorant` AS v ON u.user_id = v.user_id
                                        LEFT JOIN
                                            `userlookingfor` AS lf ON u.user_id = lf.user_id
                                        LEFT JOIN
                                            `googleuser` AS g ON u.google_userId = g.google_userId
                                        WHERE
                                            u.user_id = ?;
        ");
        
        $query->execute([$userId]);
        $user = $query->fetch();
        
        if ($user) {
            return $user;
        } else {
            return false;
        }
    }

    public function getAllUsers()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            u.*,
                                            l.*,
                                            v.*,
                                            lf.*
                                        FROM
                                            `user` AS u
                                        LEFT JOIN
                                            `leagueoflegends` AS l ON u.user_id = l.user_id
                                        LEFT JOIN
                                            `valorant` AS v ON u.user_id = v.user_id
                                        LEFT JOIN
                                            `userlookingfor` AS lf ON u.user_id = lf.user_id;
        ");
        
        $query->execute();
        $users = $query->fetchAll();
        
        if ($users) {
            return $users;
        } else {
            return false;
        }
    }

    public function getTopUsers()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            `user_id`,
                                            `user_username`,    
                                            `user_picture`,
                                            `user_isVip`,
                                            `user_currency`
                                        FROM
                                            `user`
                                        ORDER BY
                                            `user_currency` DESC
                                        LIMIT
                                            100;
        ");
        
        $query->execute();
        $users = $query->fetchAll();
        
        if ($users) {
            return $users;
        } else {
            return false;
        }
    }
    

    
    public function getAllUsersExceptFriends($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            u.*, 
                                            l.*, 
                                            v.*, 
                                            lf.*
                                        FROM
                                            `user` AS u
                                        LEFT JOIN
                                            `leagueoflegends` AS l ON u.user_id = l.user_id
                                        LEFT JOIN
                                            `valorant` AS v ON u.user_id = v.user_id
                                        INNER JOIN
                                            `userlookingfor` AS lf ON u.user_id = lf.user_id
                                        WHERE
                                            NOT EXISTS (
                                                SELECT 1
                                                FROM `friendrequest` AS fr1
                                                WHERE fr1.fr_senderId = ? AND fr1.fr_receiverId = u.user_id
                                            )
                                            AND NOT EXISTS (
                                                SELECT 1
                                                FROM `friendrequest` AS fr2
                                                WHERE fr2.fr_receiverId = ? AND fr2.fr_senderId = u.user_id
                                            );
        ");
        
        $query->execute([$userId, $userId]);
        $users = $query->fetchAll();
        
        return $users ?: false;
    }

    public function getAllUsersExceptFriendsLimit($userId, $game, $serverList, $genderConditions = [], $gameModeCondition = null)
    {
        $serverPlaceholders = implode(',', array_fill(0, count($serverList), '?'));
        $serverColumn = ($game == "League of Legends") ? "l.lol_server" : "v.valorant_server";
    
        // Base WHERE clauses
        $whereClauses = [
            "u.user_game = ?",
            "u.user_lastRequestTime >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            "$serverColumn IN ($serverPlaceholders)",
            "NOT EXISTS (SELECT 1 FROM friendrequest AS fr1 WHERE fr1.fr_senderId = ? AND fr1.fr_receiverId = u.user_id)",
            "NOT EXISTS (SELECT 1 FROM friendrequest AS fr2 WHERE fr2.fr_receiverId = ? AND fr2.fr_senderId = u.user_id)",
            "NOT EXISTS (SELECT 1 FROM block AS b1 WHERE b1.block_senderId = ? AND b1.block_receiverId = u.user_id)",
            "NOT EXISTS (SELECT 1 FROM block AS b2 WHERE b2.block_receiverId = ? AND b2.block_senderId = u.user_id)"
        ];
    
        $params = array_merge(
            [$game],
            $serverList,
            [$userId, $userId, $userId, $userId]
        );
    
        // Add gender conditions
        if (!empty($genderConditions)) {
            $genderPlaceholders = implode(',', array_fill(0, count($genderConditions), '?'));
            $whereClauses[] = "u.user_gender IN ($genderPlaceholders)";
            $params = array_merge($params, $genderConditions);
        }
    
        if ($gameModeCondition !== null) {
            if (is_array($gameModeCondition)) {
                $placeholders = implode(',', array_fill(0, count($gameModeCondition), '?'));
                $whereClauses[] = "u.user_kindOfGamer IN ($placeholders)";
                $params = array_merge($params, $gameModeCondition);
            } else {
                $whereClauses[] = "u.user_kindOfGamer = ?";
                $params[] = $gameModeCondition;
            }
        }
    
        // Build final query
        $query = $this->bdd->prepare("
            SELECT u.*, l.*, v.*, lf.*
            FROM user AS u
            LEFT JOIN leagueoflegends AS l ON u.user_id = l.user_id
            LEFT JOIN valorant AS v ON u.user_id = v.user_id
            INNER JOIN userlookingfor AS lf ON u.user_id = lf.user_id
            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY RAND()
            LIMIT 5;
        ");
    
        $query->execute($params);
        $users = $query->fetchAll();
    
        return $users ?: false;
    }
    

    public function storeDeletionToken($userId, $deletionToken, $expiry, $currentDate)
    {
        $query = $this->bdd->prepare("
                                        UPDATE `user`
                                        SET `user_deletionToken` = ?, `user_deletionTokenExpiry` = ?
                                        WHERE `user_id` = ?
        ");
    
         $storeDeletionTokenTest = $query->execute([$deletionToken, $currentDate, $userId]);
        
        if ($storeDeletionTokenTest) {
            return true;
        } else {
            return false;
        }
    }

    public function getDeletionToken($token)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            u.`user_deletionToken`,
                                            u.`user_deletionTokenExpiry`,
                                            g.`google_email`
                                        FROM 
                                            `user`AS u
                                        INNER JOIN
                                            `googleuser` AS g 
                                        ON 
                                            u.`google_userId` = g.`google_userId`
                                        WHERE 
                                            `user_deletionToken` = ?
        ");
    
        $query->execute([$token]);
        $deletionToken = $query->fetch();
    
        if ($deletionToken) {
            return $deletionToken;
        } else {
            return false;
        }
    }

    public function invalidateDeletionToken($token)
    {
        $query = $this->bdd->prepare("
            UPDATE `user`
            SET `user_deletionToken` = NULL, `user_deletionTokenExpiry` = NULL
            WHERE `user_deletionToken` = ?
        ");


        return $query->execute([$token]);
    }

    public function userIsLookingForGame($userId)
    {
        $query = $this->bdd->prepare("
                                        UPDATE `user`
                                        SET `user_isLooking` = 1, `user_requestIsLooking` =  NOW()
                                        WHERE `user_id` = ?
        ");

        $updateStatusLookingGame = $query -> execute([$userId]);


        if($updateStatusLookingGame)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function createUser($googleUserId, $username, $gender, $age, $kindOfGamer, $shortBio, $game) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `user`(
                                                `google_userId`,
                                                `user_username`,
                                                `user_gender`,                                                
                                                `user_age`,
                                                `user_kindOfGamer`,
                                                `user_shortBio`,
                                                `user_game`
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

        $createWebsiteUser = $query -> execute([$googleUserId, $username, $gender, $age, $kindOfGamer, $shortBio, $game]);


        if($createWebsiteUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function updateUser($gender, $age, $kindOfGamer, $shortBio, $game, $userId) 
    {
        $sql = "UPDATE `user` SET ";
        $params = [];
        $updates = [];
    
        if (!empty($gender)) {
            $updates[] = "`user_gender` = ?";
            $params[] = $gender;
        }
        if (!empty($age)) {
            $updates[] = "`user_age` = ?";
            $params[] = $age;
        }
        if (!empty($kindOfGamer)) {
            $updates[] = "`user_kindOfGamer` = ?";
            $params[] = $kindOfGamer;
        }
        if (!empty($shortBio)) {
            $updates[] = "`user_shortBio` = ?";
            $params[] = $shortBio;
        }
        if (!empty($game)) {
            $updates[] = "`user_game` = ?";
            $params[] = $game;
        }
    
        $sql .= implode(", ", $updates) . " WHERE `user_id` = ?";
        $params[] = $userId;
    
        if (!empty($updates)) {
            $query = $this->bdd->prepare($sql);
            $updateUserTest = $query->execute($params);

    
            if ($updateUserTest) {
                return true;
            } else {
                return false;  
            }
        } else {
            return false;
        }
    }

    public function registerToken($userId, $token)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET
                                            `user_token` = ?
                                        WHERE
                                            `user_id` = ?
        ");

        $registerTokenTest = $query->execute([$token, $userId]);

        if ($registerTokenTest) {
            return true;
        } else {
            return false;  
        }
    }

    public function getToken($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            `user_token`
                                        FROM
                                            `user`
                                        WHERE
                                            `user_id` = ?
        ");

        $query->execute([$userId]);
        $token = $query->fetch();

        if ($token) {
            return $token;
        } else {
            return false;
        }
    }

    public function updateSocial($username, $discord, $twitter, $instagram, $twitch) 
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user` 
                                        SET
                                            `user_discord` = ?,
                                            `user_twitter` = ?,
                                            `user_instagram` = ?,
                                            `user_twitch` = ?
                                        WHERE
                                            `user_username` = ?
        ");

        $updateSocialTest = $query->execute([$discord, $twitter, $instagram, $twitch, $username]);

        if ($updateSocialTest) {
            return true;
        } else {
            return false;  
        }
    }

    public function updateSocial2($username, $discord, $twitter, $instagram, $twitch, $bluesky) 
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user` 
                                        SET
                                            `user_discord` = ?,
                                            `user_twitter` = ?,
                                            `user_instagram` = ?,
                                            `user_twitch` = ?,
                                            `user_bluesky` = ?
                                        WHERE
                                            `user_username` = ?
        ");

        $updateSocialTest = $query->execute([$discord, $twitter, $instagram, $twitch, $bluesky, $username]);

        if ($updateSocialTest) {
            return true;
        } else {
            return false;  
        }
    }

    public function uploadPicture($username, $fileName) 
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET
                                            `user_picture` = ?
                                        WHERE
                                            `user_username` = ?

                                        ");

        $uploadPictureTest = $query->execute([$fileName,$username]);

        if($uploadPictureTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function updateBonusPictures($username, $picturesArray)
    {
        $updatedPicturesJson = json_encode($picturesArray);
    
        $query = $this->bdd->prepare("
            UPDATE `user`
            SET `user_bonusPicture` = ?
            WHERE `user_username` = ?
        ");
    
        return $query->execute([$updatedPicturesJson, $username]);
    }

    public function getBonusPictures($username)
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            `user_bonusPicture`
                                        FROM
                                            `user`
                                        WHERE
                                            `user_username` = ?
        ");

        $query->execute([$username]);
        $bonusPictures = $query->fetch();

        if ($bonusPictures) {
            return !empty($bonusPictures['user_bonusPicture']) ? json_decode($bonusPictures['user_bonusPicture'], true) : [];
        } else {
            return false;
        }
    }

    public function updateFilter($status, $userId) 
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET
                                            `user_hasChatFilter` = ?
                                        WHERE
                                            `user_id` = ?

                                        ");

        $updateFilterTest = $query->execute([$status, $userId]);

        if($updateFilterTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }


    public function getUserDataByGoogleUserId($googleUserId)
    {
                $query = $this -> bdd -> prepare ("
                                                        SELECT
                                                        u.user_id,
                                                        u.google_userId,
                                                        u.user_username,
                                                        u.user_gender,
                                                        u.user_age,
                                                        u.user_kindOfGamer,
                                                        u.user_shortBio,
                                                        u.user_game
                                                        FROM
                                                            `user` as u
                                                        INNER JOIN
                                                            `googleuser` as g
                                                        ON
                                                            u.google_userId = g.google_userId
                                                        WHERE
                                                             g.google_userId = ?
                                                ");

        $query -> execute([$googleUserId]);
        $googleUserIdTest = $query -> fetch();


        if($googleUserIdTest)
        {
            return $googleUserIdTest;
        }
        else
        {
            return false;
        }
    }

    public function buyPremium($userId)
    {
        $query = $this->bdd->prepare("
                                    UPDATE
                                        `user`
                                    SET
                                        user_isVip = 1
                                    WHERE
                                        user_id = ?
        ");
    
        $query->execute([$userId]);
    }

    public function getLastRequestTime($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            `user_lastRequestTime` 
                                        FROM 
                                            `user` 
                                        WHERE 
                                            `user_id` = ?
        ");
        
        $query->execute([$userId]);
        $result = $query->fetch();
    
        return $result ? strtotime($result['user_lastRequestTime']) : 0; 
    }

    public function updateLastRequestTime($userId)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user` 
                                        SET 
                                            `user_lastRequestTime` = NOW() 
                                        WHERE 
                                            `user_id` = ?
        ");
        
        return $query->execute([$userId]);
    } 

    public function logUserActivity($userId)
    {
        $query = $this->bdd->prepare("
                                            INSERT INTO `user_activity_log`(
                                                `user_id`
                                            )
                                            VALUES (
                                                ?
                                            )
        ");
        
        $logActivity = $query -> execute([$userId]);

        if($logActivity)
        {
            return $this->bdd-> lastInsertId();
        } else {
            return false;
        }
    } 

    public function selectLastActivity($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            `id`,
                                            `activity_time`
                                        FROM
                                            `user_activity_log`
                                        WHERE
                                            `user_id` = ?
                                        ORDER BY
                                            `activity_time` DESC
                                        LIMIT 1
        ");
        
        $query->execute([$userId]);

        $logActivity = $query->fetch(); 

        if($logActivity)
        {
            return $logActivity;
        } else {
            return false;
        }
    } 

    public function markGameAsPlayed($userId, $date)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET 
                                            `user_lastCompletedGame` = ?
                                        WHERE 
                                            `user_id` = ?
        ");
        
        return $query->execute([$date, $userId]);
    }   

    public function updateFriendsInvited($userId)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET 
                                            `user_friendsInvited` = `user_friendsInvited` + 1
                                        WHERE 
                                            `user_id` = ?
        ");
        
        return $query->execute([$userId]);
    }   

    public function updatePermission($userId) 
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_notificationPermission` = 1
                                            WHERE
                                                `user_id` = ?
        ");

        $updatePermissionTest = $query -> execute([$userId]);

        if ($updatePermissionTest) {
        return true;
        } else {
        return false;
        }
    }

    public function saveSubscription($userId, $endpoint, $p256dh, $auth) 
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_notificationEndPoint` = ?,
                                                `user_notificationP256dh` = ?,
                                                `user_notificationAuth` = ?
                                            WHERE
                                                `user_id` = ?
        ");

        $saveSubscriptionTest = $query -> execute([$endpoint, $p256dh, $auth, $userId]);

        if ($saveSubscriptionTest) {
        return true;
        } else {
        return false;
        }
    }

    public function addPartner($userId)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_isPartner` = 1
                                            WHERE
                                                `user_id` = ?
        ");

        $addPartnerTest = $query -> execute([$userId]);

        if ($addPartnerTest) {
            return true;
        } else {
            return false;
        }
    }

    public function removePartner($userId)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_isPartner` = 0
                                            WHERE
                                                `user_id` = ?
        ");

        $removePartnerTest = $query -> execute([$userId]);

        if ($removePartnerTest) {
            return true;
        } else {
            return false;
        }
    }

    public function updateLastRewardTime($userId)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `user`
                                            SET
                                                `user_lastReward` = NOW()
                                            WHERE
                                                `user_id` = ?
        ");

        $addLastRewardTimeTest = $query -> execute([$userId]);

        if ($addLastRewardTimeTest) {
            return true;
        } else {
            return false;
        }
    }
}