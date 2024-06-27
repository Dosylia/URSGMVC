<?php
namespace models;

use config\DataBase;

class Block extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

}
