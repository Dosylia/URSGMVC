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
    
}
