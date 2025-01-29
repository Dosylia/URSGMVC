<?php
namespace models;

use config\DataBase;

class Admin extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function countOnlineUsers()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            COUNT(*) AS `online_users`
                                        FROM
                                            `user`
                                        WHERE
                                            (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(user_lastRequestTime) <= 30)
        ");
    
        $query->execute();
        $result = $query->fetch();
    
        return $result ? $result['online_users'] : false;
    }

    public function countOnlineUsersToday()
    {
        $query = $this->bdd->prepare("
                                        SELECT COUNT(*) AS online_users
                                        FROM `user`
                                        WHERE user_lastRequestTime >= CURRENT_DATE
        ");
    
        $query->execute();
        $result = $query->fetch();
    
        return $result ? $result['online_users'] : false;
    }

    public function countOnlineUsersLast7Days()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            COUNT(*) AS `online_users`
                                        FROM
                                            `user`
                                        WHERE
                                            (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(user_lastRequestTime) <= 7 * 24 * 60 * 60)
        ");
    
        $query->execute();
        $result = $query->fetch();
    
        return $result ? $result['online_users'] : false;
    }

    public function countPendingReports()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            COUNT(*) AS `pendingReports`
                                        FROM
                                            `reports`
                                        WHERE
                                            `status` = 'pending'
        ");

        $query->execute();
        $result = $query->fetch();

        return $result ? $result['pendingReports'] : false;
    }

    public function dailyActivity()
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            HOUR(activity_time) AS hour,
                                            COUNT(*) AS activity_count
                                        FROM 
                                            user_activity_log
                                        WHERE 
                                            activity_time >= NOW() - INTERVAL 24 HOUR
                                        GROUP BY 
                                            HOUR(activity_time)
                                        ORDER BY 
                                            hour
        ");
    
        $query->execute();
        $result = $query->fetchAll();
    
        return $result ? $result : false;
    }
    

    public function countPurchases()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            COUNT(*) AS `ownedItems`
                                        FROM
                                            `user_items`
        ");

        $query->execute();
        $result = $query->fetch();

        return $result ? $result['ownedItems'] : false;
    }

    public function countTotalCharacters()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            COUNT(*) AS `TotalChars`
                                        FROM
                                            `game`
        ");

        $query->execute();
        $result = $query->fetch();

        return $result ? $result['TotalChars'] : false;
    }

    public function countTotalPlayers()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            COUNT(*) AS `TotalPlayers`
                                        FROM
                                            `user`
                                        WHERE
                                            `user_lastCompletedGame` IS NOT NULL
        ");

        $query->execute();
        $result = $query->fetch();

        return $result ? $result['TotalPlayers'] : false;
    }

    public function getLastGameDate()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            `game_date` AS `LastDateGame`
                                        FROM
                                            `game`
                                        ORDER BY
                                            `game_date` DESC
                                        LIMIT 1
        ");
    
        $query->execute();
        $result = $query->fetch();
    
        return $result ? $result['LastDateGame'] : false;
    }

    public function censorBio($userId)
    {
        $query = $this->bdd->prepare("
                                        UPDATE
                                            `user`
                                        SET
                                            `user_shortBio` = 'This bio has been replaced for violating the terms of service.'
                                        WHERE
                                            `user_id` = ?
        ");

        $query->execute([$userId]);


        if ($query->rowCount() > 0) {
            return true; 
        } else {
            return false;
        }
    }

    public function censorPicture($userId)
    {
        $query = $this->bdd->prepare("
                                        UPDATE
                                            `user`
                                        SET
                                            `user_picture` = NULL
                                        WHERE
                                            `user_id` = ?
        ");

        $query->execute([$userId]);


        if ($query->rowCount() > 0) {
            return true; 
        } else {
            return false;
        }
    }


    public function getLastAdminActions()
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            aa.*, 
                                            u.user_username,
                                            -- Use a CASE or COALESCE to determine if it's a game action or user action
                                            COALESCE(target.user_username, ga.game_username) AS target_username 
                                        FROM 
                                            admin_actions aa
                                        LEFT JOIN 
                                            user u ON aa.admin_id = u.user_id
                                        LEFT JOIN 
                                            user target ON aa.target_user_id = target.user_id
                                        LEFT JOIN 
                                            game ga ON aa.target_game_username = ga.game_username
                                        ORDER BY 
                                            aa.timestamp DESC
                                        LIMIT 10
        ");
    
        $query->execute();
        $lastActions = $query->fetchAll();
        
        if ($lastActions) {
            return $lastActions;
        } else {
            return false;
        }
    }
    

    public function logAdminAction($adminId, $targetUserId, $actionType)
    {
        $query = $this -> bdd -> prepare("
                INSERT INTO `admin_actions`(
                    `admin_id`,
                    `target_user_id`,
                    `action_type`                                      
                )
                VALUES (
                    ?,
                    ?,
                    ?
                )
            ");

        $logActiong = $query -> execute([$adminId, $targetUserId, $actionType]);

        if($logActiong)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function logAdminActionBan($adminId, $targetUserId, $username, $email, $actionType)
    {
        $query = $this -> bdd -> prepare("
                INSERT INTO `admin_actions`(
                    `admin_id`,
                    `ban_userId`,
                    `ban_username`,
                    `ban_email`,
                    `action_type`                                      
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

        $logActiong = $query -> execute([$adminId, $targetUserId, $username, $email, $actionType]);

        if($logActiong)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function logAdminActionGame($adminId, $gameUsername, $actionType)
    {
        $query = $this -> bdd -> prepare("
                INSERT INTO `admin_actions`(
                    `admin_id`,
                    `target_game_username`,
                    `action_type`                                      
                )
                VALUES (
                    ?,
                    ?,
                    ?
                )
            ");

        $logActiong = $query -> execute([$adminId, $gameUsername, $actionType]);

        if($logActiong)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function addCharacterGame($gameUsername, $gameMain, $hintAffiliation, $hintGender, $hintGuess, $gameDate, $gameGame)
    {
        $query = $this -> bdd -> prepare("
                INSERT INTO `game`(
                    `game_username`,
                    `game_main`,
                    `hint_affiliation`,
                    `hint_gender`,
                    `hint_guess`,
                    `game_date`,
                    `game_game`                                      
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

        $addCharGame = $query -> execute([$gameUsername, $gameMain, $hintAffiliation, $hintGender, $hintGuess, $gameDate, $gameGame]); 

        if($addCharGame)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function addBannedUser($email, $reason)
    {
        $query = $this -> bdd -> prepare("
                INSERT INTO `banned_users`(
                    `email`,
                    `reason`                                   
                )
                VALUES (
                    ?,
                    ?
                )
        ");

        $bannedUser = $query -> execute([$email, $reason]); 

        if($bannedUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function getReports()
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            *
                                        FROM 
                                            `reports`
        ");
    
        $query->execute();
        $allReports = $query->fetchAll();
        
        if ($allReports) {
            return $allReports;
        } else {
            return false;
        }
    }

    public function getGroupedReports()
    {
        $query = $this->bdd->prepare("
            SELECT 
                r.reported_id, 
                u.user_username,
                u.user_shortBio,
                u.user_picture,
                u.user_age,
                GROUP_CONCAT(r.report_id) AS report_ids,
                COUNT(*) AS report_count,
                GROUP_CONCAT(r.content_id) AS content_ids,
                GROUP_CONCAT(r.content_type) AS content_types,
                GROUP_CONCAT(r.reason SEPARATOR '||') AS reasons,
                GROUP_CONCAT(r.details SEPARATOR '||') AS details
            FROM 
                `reports` r
            LEFT JOIN 
                `user` u 
            ON 
                r.reported_id = u.user_id
            WHERE 
                r.status = 'pending'
            GROUP BY 
                r.reported_id, u.user_username
            ORDER BY 
                r.created_at DESC
        ");
    
        $query->execute();
        $allReports = $query->fetchAll();
        
        return $allReports ? $allReports : [];
    }
    
    public function updateReport($reportedId, $status)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `reports`
                                        SET 
                                            `status` = ?
                                        WHERE 
                                            `reported_id` = ?
        ");
    
        $query->execute([$status, $reportedId]);
    
        if ($query->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getReportsByUserId($userId)
    {
        $query = $this->bdd->prepare("
            SELECT 
                *
            FROM 
                `reports`
            WHERE
                `reported_id` = ?
        ");
    
        $query->execute([$userId]);
        $allReports = $query->fetchAll();
        
        return $allReports ? $allReports : [];
    }
}
