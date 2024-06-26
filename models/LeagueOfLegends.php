<?php
namespace models;

use config\DataBase;

class LeagueOfLegends extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() 
    {
        $this->bdd = $this->getBdd();
    }  
    
    
    public function createLoLUser($userId, $loLMain1, $loLMain2, $loLMain3, $loLRank, $loLRole, $loLServer, $loLAccount) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `leagueoflegends`(
                                                `user_id`,
                                                `lol_main1`,
                                                `lol_main2`,                                                
                                                `lol_main3`,
                                                `lol_rank`,
                                                `lol_role`,
                                                `lol_server`,
                                                `lol_account`
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $createLeagueUser = $query -> execute([$userId, $loLMain1, $loLMain2, $loLMain3, $loLRank, $loLRole, $loLServer, $loLAccount]);


        if($createLeagueUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function updateLeagueData($userId, $loLMain1, $loLMain2, $loLMain3, $loLRank, $loLRole, $loLServer, $loLAccount) 
    {
        $sql = "UPDATE `leagueoflegends` SET ";
        $params = [];
        $updates = [];
    
        if (!empty($loLMain1)) {
            $updates[] = "`lol_main1` = ?";
            $params[] = $loLMain1;
        }
        if (!empty($loLMain2)) {
            $updates[] = "`lol_main2` = ?";
            $params[] = $loLMain2;
        }
        if (!empty($loLMain3)) {
            $updates[] = "`lol_main3` = ?";
            $params[] = $loLMain3;
        }
        if (!empty($loLRank)) {
            $updates[] = "`lol_rank` = ?";
            $params[] = $loLRank;
        }
        if (!empty($loLRole)) {
            $updates[] = "`lol_role` = ?";
            $params[] = $loLRole;
        }
        if (!empty($loLServer)) {
            $updates[] = "`lol_server` = ?";
            $params[] = $loLServer;
        }
        if (!empty($loLAccount)) {
            $updates[] = "`lol_account` = ?";
            $params[] = $loLAccount;
        }
    
        $sql .= implode(", ", $updates) . " WHERE `user_id` = ?";
        $params[] = $userId;
    
        if (!empty($updates)) {
            $query = $this->bdd->prepare($sql);
            $updateLeagueTest = $query->execute($params);

    
            if ($updateLeagueTest) {
                return true;
            } else {
                return false;  
            }
        } else {
            return false;
        }
    }

    public function getLeageUserByUsername($lolAccount) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `leagueoflegends`
                                            WHERE
                                                `lol_account` = ?

        ");

        $query -> execute([$lolAccount]);
        $lolAccountTest = $query -> fetch();


        if ($lolAccountTest)
        {
            return $lolAccountTest;
        } 
        else
        {
            return false;
        }
    }

    public function getLeageUserByLolId($lolId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `leagueoflegends`
                                            WHERE
                                                `lol_id` = ?

        ");

        $query -> execute([$lolId]);
        $lolIdTest = $query -> fetch();
        

        if ($lolIdTest)
        {
            return $lolIdTest;
        } 
        else
        {
            return false;
        }
    }

    public function getLeageUserByUserId($userId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                `lol_id`,
                                                `user_id`,
                                                `lol_main1`,
                                                `lol_main2`,                                                
                                                `lol_main3`,
                                                `lol_rank`,
                                                `lol_role`,
                                                `lol_server`,
                                                `lol_account`
                                            FROM
                                                `leagueoflegends`
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
}
