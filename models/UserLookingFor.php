<?php
namespace models;

use config\DataBase;

class UserLookingFor extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function createLookingForUser($userId, $lfGender, $lfKindOfGamer, $lfGame, $loLMain1, $loLMain2, $loLMain3, $loLRank, $loLRole) 
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

        $createLookingForUser = $query -> execute([$userId, $lfGender, $lfKindOfGamer, $lfGame, $loLMain1, $loLMain2, $loLMain3, $loLRank, $loLRole]);

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

}
