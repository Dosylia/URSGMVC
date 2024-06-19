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
    
    public function createLoLUser($UserId, $loLMain1, $loLMain2, $loLMain3, $loLRank, $loLRole, $loLServer, $loLAccount) 
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

        $createWebsiteUser = $query -> execute([$UserId, $loLMain1, $loLMain2, $loLMain3, $loLRank, $loLRole, $loLServer, $loLAccount]);

        if($createWebsiteUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }
}
