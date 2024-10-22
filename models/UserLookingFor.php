<?php
namespace models;

use config\DataBase;

class UserLookingFor extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function createLookingForUser($userId, $lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole, $statusChampion) 
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
                                                `lf_lolrole`,
                                                `lf_lolNoChamp`
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
                                                ?,
                                                ?
                                            )
                                        ");

        $createLookingForUser = $query -> execute([$userId, $lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole, $statusChampion]);

        if($createLookingForUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function createLookingForUserValorant($userId, $lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole, $statusChampion) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `userlookingfor`(
                                                `user_id`,
                                                `lf_gender`,
                                                `lf_kindofgamer`,
                                                `lf_game`,
                                                `lf_valmain1`,
                                                `lf_valmain2`,      
                                                `lf_valmain3`,
                                                `lf_valrank`,
                                                `lf_valrole`,
                                                `lf_valNoChamp`
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
                                                ?,
                                                ?
                                            )
                                        ");

        $createLookingForUser = $query -> execute([$userId, $lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole, $statusChampion]);

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
                                                `lf_lolNoChamp`,
                                                `lf_lolmain1`,
                                                `lf_lolmain2`,      
                                                `lf_lolmain3`,
                                                `lf_lolrank`,
                                                `lf_lolrole`,
                                                `lf_valNoChamp`,
                                                `lf_valmain1`,
                                                `lf_valmain2`,      
                                                `lf_valmain3`,
                                                `lf_valrank`,
                                                `lf_valrole`
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

    public function updateLookingForData($lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole, $statusChampion, $userId) 
    {
        $sql = "UPDATE `userlookingfor` SET ";
        $params = [];
        $updates = [];
    
        if (!empty($lfGender)) {
            $updates[] = "`lf_gender` = ?";
            $params[] = $lfGender;
        }
        if (!empty($lfKindOfGamer)) {
            $updates[] = "`lf_kindofgamer` = ?";
            $params[] = $lfKindOfGamer;
        }
        if (!empty($lfGame)) {
            $updates[] = "`lf_game` = ?";
            $params[] = $lfGame;
        }
            $updates[] = "`lf_lolmain1` = ?";
            $params[] = $lfMain1;


            $updates[] = "`lf_lolmain2` = ?";
            $params[] = $lfMain2;


            $updates[] = "`lf_lolmain3` = ?";
            $params[] = $lfMain3;

        if (!empty($lfRank)) {
            $updates[] = "`lf_lolrank` = ?";
            $params[] = $lfRank;
        }
        if (!empty($lfRole)) {
            $updates[] = "`lf_lolrole` = ?";
            $params[] = $lfRole;
        }

        $updates[] = "`lf_lolNoChamp` = ?";
        $params[] = $statusChampion;

    
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

    public function updateLookingForDataValorant($lfGender, $lfKindOfGamer, $lfGame, $lfMain1, $lfMain2, $lfMain3, $lfRank, $lfRole, $statusChampion, $userId) 
    {
        $sql = "UPDATE `userlookingfor` SET ";
        $params = [];
        $updates = [];

        if (!empty($lfGender)) {
            $updates[] = "`lf_gender` = ?";
            $params[] = $lfGender;
        }
        if (!empty($lfKindOfGamer)) {
            $updates[] = "`lf_kindofgamer` = ?";
            $params[] = $lfKindOfGamer;
        }
        if (!empty($lfGame)) {
            $updates[] = "`lf_game` = ?";
            $params[] = $lfGame;
        }

            $updates[] = "`lf_valmain1` = ?";
            $params[] = $lfMain1;


            $updates[] = "`lf_valmain2` = ?";
            $params[] = $lfMain2;

            $updates[] = "`lf_valmain3` = ?";
            $params[] = $lfMain3;

        if (!empty($lfRank)) {
            $updates[] = "`lf_valrank` = ?";
            $params[] = $lfRank;
        }
        if (!empty($lfRole)) {
            $updates[] = "`lf_valrole` = ?";
            $params[] = $lfRole;
        }

        $updates[] = "`lf_valNoChamp` = ?";
        $params[] = $statusChampion;
    
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


