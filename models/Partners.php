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

    public function addPartner($username, $socials, $picture)
    {
        $query = $this -> bdd -> prepare("
                                        INSERT INTO
                                            `partners`
                                        (
                                            `username`,
                                            `picture_path`,
                                            `social_links`
                                        )
                                        VALUES
                                        (
                                            ?,
                                            ?,
                                            ?
                                        )
        ");

        $insertPartner = $query->execute([
            $username,
            $picture,
            $socials
        ]);

        if($insertPartner)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function removePartner($partnerId)
    {
        $query = $this -> bdd -> prepare("
                                        DELETE FROM
                                            `partners`
                                        WHERE
                                            `id` = ?
        ");

        $deletePartner = $query -> execute([$partnerId]);

        if($deletePartner)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

}
