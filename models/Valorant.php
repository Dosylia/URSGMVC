<?php
namespace models;

use config\DataBase;

class Valorant extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() 
    {
        $this->bdd = $this->getBdd();
    }  
    
    
    public function createValorantUser($userId, $valorantMain1, $valorantMain2, $valorantMain3, $valorantRank, $valorantRole, $valorantServer) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `valorant`(
                                                `user_id`,
                                                `valorant_main1`,
                                                `valorant_main2`,                                                
                                                `valorant_main3`,
                                                `valorant_rank`,
                                                `valorant_role`,
                                                `valorant_server`
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

        $createValorantUser = $query -> execute([$userId, $valorantMain1, $valorantMain2, $valorantMain3, $valorantRank, $valorantRole, $valorantServer]);


        if($createValorantUser)
        {
            return $this->bdd-> lastInsertId();
        }
        else 
        {
            return false;  
        }

    }

    public function updateValorantData($userId, $valorantMain1, $valorantMain2, $valorantMain3, $valorantRank, $valorantRole, $valorantServer) 
    {
        $sql = "UPDATE `valorant` SET ";
        $params = [];
        $updates = [];
    
        if (!empty($valorantMain1)) {
            $updates[] = "`valorant_main1` = ?";
            $params[] = $valorantMain1;
        }
        if (!empty($valorantMain2)) {
            $updates[] = "`valorant_main2` = ?";
            $params[] = $valorantMain2;
        }
        if (!empty($valorantMain3)) {
            $updates[] = "`valorant_main3` = ?";
            $params[] = $valorantMain3;
        }
        if (!empty($valorantRank)) {
            $updates[] = "`valorant_rank` = ?";
            $params[] = $valorantRank;
        }
        if (!empty($valorantRole)) {
            $updates[] = "`valorant_role` = ?";
            $params[] = $valorantRole;
        }
        if (!empty($valorantServer)) {
            $updates[] = "`valorant_server` = ?";
            $params[] = $valorantServer;
        }
    
        $sql .= implode(", ", $updates) . " WHERE `user_id` = ?";
        $params[] = $userId;
    
        if (!empty($updates)) {
            $query = $this->bdd->prepare($sql);
            $updateValorantTest = $query->execute($params);

    
            if ($updateValorantTest) {
                return true;
            } else {
                return false;  
            }
        } else {
            return false;
        }
    }


    public function getValorantUserByUsername($valorantAccount) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `valorant`
                                            WHERE
                                                `valorant_account` = ?

        ");

        $query -> execute([$valorantAccount]);
        $valorantAccountTest = $query -> fetch();


        if ($valorantAccountTest)
        {
            return $valorantAccountTest;
        } 
        else
        {
            return false;
        }
    }

    public function getValorantAccountByValorantId($valorantId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `valorant`
                                            WHERE
                                                `valorant_id` = ?

        ");

        $query -> execute([$valorantId]);
        $valorantAccountTest = $query -> fetch();


        if ($valorantAccountTest)
        {
            return $valorantAccountTest;
        } 
        else
        {
            return false;
        }
    }

    public function getValorantUserByValorantId($valorantId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `valorant`
                                            WHERE
                                                `valorant_id` = ?

        ");

        $query -> execute([$valorantId]);
        $valorantIdTest = $query -> fetch();
        

        if ($valorantIdTest)
        {
            return $valorantIdTest;
        } 
        else
        {
            return false;
        }
    }

    public function getValorantUserByUserId($userId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `valorant`
                                            WHERE
                                                `user_id` = ?

        ");

        $query -> execute([$userId]);
        $userIdTest = $query -> fetch();


        if ($userIdTest)
        {
            return $userIdTest;
        } 
        else
        {
            return false;
        }
    }

    public function addPuuid($puuid, $userId) 
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `valorant`
                                            SET
                                                `valorant_aPuuid` = ?                                                                                              
                                            WHERE
                                                `user_id` = ?
        ");

        $addPuuidTest  = $query -> execute([$puuid, $userId]);

        if($addPuuidTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function updateValorantRiot($valorantProfile, $valorantRank, $valorantLevel, $profileIconId, $userId) 
    {

        $query = $this -> bdd -> prepare("
                                            UPDATE 
                                                `valorant`
                                            SET
                                                `valorant_verified` = 1,
                                                `valorant_aUsername` = ?,
                                                `valorant_aRank` = ?,                                
                                                `valorant_aLevel` = ?,
                                                `valorant_aProfileIconId` = ?,
                                            WHERE
                                                `user_id` = ?
        ");

        $updateValorantTest = $query -> execute([$valorantProfile, $valorantRank, $valorantLevel, $profileIconId, $userId]);

        if($updateValorantTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
