<?php
namespace models;

use config\DataBase;

class Items extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function getItems()
    {
        $query = $this->bdd->prepare("
                                    SELECT
                                        items_id,
                                        items_name,
                                        items_price,
                                        items_desc,
                                        items_picture,
                                        items_category,
                                        items_discount,
                                        items_isActive,
                                        items_createdAt
                                    FROM
                                        `items`
        ");
    
        $query->execute([]);
        $ItemslistTest = $query->fetchAll();
    
        if ($ItemslistTest) {
            return $ItemslistTest;
        } else {
            return false;
        }
    }

}
