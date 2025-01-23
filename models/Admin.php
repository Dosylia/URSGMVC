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
                                            target.user_username AS target_username 
                                        FROM 
                                            admin_actions aa
                                        JOIN
                                            user u ON aa.admin_id = u.user_id
                                        JOIN 
                                            user target ON aa.target_user_id = target.user_id
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

        $query -> execute([$adminId, $targetUserId, $actionType]);

    }
    
}
