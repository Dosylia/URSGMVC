<?php
namespace models;

use config\DataBase;

class UserLookingFor extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function createLookingForUser($userId, $lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `userlookingfor`(
                                                `user_id`,
                                                `lf_gender`,
                                                `lf_kindofgamer`,
                                                `lf_game`,
                                                `lf_lolmain1`,
                                                `lf_lolmain2`,      
                                                `lf_lolmain3`,
                                                `lf_lolrank`,
                                                `lf_lolrole`
                                            )
                                            VALUES (
                                                ?,
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

        $createLookingForUser = $query -> execute([$userId, $lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole]);

        if($createLookingForUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function getLookingForUserByUserId($userId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                `lf_id`,
                                                `user_id`,
                                                `lf_gender`,
                                                `lf_kindofgamer`,
                                                `lf_game`,
                                                `lf_lolmain1`,
                                                `lf_lolmain2`,      
                                                `lf_lolmain3`,
                                                `lf_lolrank`,
                                                `lf_lolrole`
                                            FROM
                                                `userlookingfor`
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

    public function updateLookingForData($userId, $lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole) 
    {
        $sql = "UPDATE `userlookingfor` SET ";
        $params = [];
        $updates = [];
    
        if (!empty($lfMain1)) {
            $updates[] = "`lf_lolmain1` = ?";
            $params[] = $lfMain1;
        }
        if (!empty($lfMain2)) {
            $updates[] = "`lf_lolmain2` = ?";
            $params[] = $lfMain2;
        }
        if (!empty($lfMain3)) {
            $updates[] = "`lf_lolmain3` = ?";
            $params[] = $lfMain3;
        }
        if (!empty($lfRank)) {
            $updates[] = "`lf_lolrank` = ?";
            $params[] = $lfRank;
        }
        if (!empty($lfRole)) {
            $updates[] = "`lf_lolrole` = ?";
            $params[] = $lfRole;
        }
        if (!empty($lfServer)) {
            $updates[] = "`lf_lolserver` = ?";
            $params[] = $lfServer;
        }
    
        $sql .= implode(", ", $updates) . " WHERE `user_id` = ?";
        $params[] = $userId;
    
        if (!empty($updates)) {
            $query = $this->bdd->prepare($sql);
            $updateLookingForTest = $query->execute($params);

            if ($updateLookingForTest) {
                return true;
            } else {
                return false;  
            }
        } else {
            return false;
        }
    }
}


