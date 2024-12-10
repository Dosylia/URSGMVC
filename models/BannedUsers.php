<?php
namespace models;

use config\DataBase;

class BannedUsers extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function checkBan($email)
    {

        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `banned_users`
                                            WHERE
                                                `email` = ?
        ");

        $query -> execute([$email]);
        $bannedUser = $query -> fetch();


        if ($bannedUser)
        {
            return $bannedUser;
        }
        else
        {
            return false;
        }

    }
}
