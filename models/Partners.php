<?php
namespace models;

use config\DataBase;

class Partners extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function getPartners()
    {

        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `partners`
        ");

        $query -> execute();
        $partners = $query -> fetchAll();


        if ($partners)
        {
            return $partners;
        }
        else
        {
            return false;
        }

    }
}
