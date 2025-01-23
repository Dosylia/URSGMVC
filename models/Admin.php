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

    public function countPurchases()
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            COUNT(*) AS `ownedItemss`
                                        FROM
                                            `user_items`
        ");

        $query->execute();
        $result = $query->fetch();

        return $result ? $result['ownedItemss'] : false;
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
    
}
