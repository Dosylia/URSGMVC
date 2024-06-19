<?php
namespace models;

use config\DataBase;

class UserLookingFor extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

}
