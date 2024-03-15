<?php
namespace models;

use config\DataBase;

class GoogleUser extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() 
    {
        $this->bdd = $this->getBdd();
    }

    public function userExist($googleId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                `google_userId`,
                                                `google_id`,
                                                `google_fullName`,
                                                `google_firstName`,                                                
                                                `google_lastName`,
                                                `google_email`,
                                                `google_pictureUrl`
                                            FROM
                                                `googleUser`
                                            WHERE
                                                `google_id` = ?

        ");

        $query -> execute([$googleId]);
        $googleIdTest = $query -> fetch();

        if ($googleIdTest)
        {
            return $googleIdTest;
        } 
        else
        {
            return false;
        }
    }

    public function createGoogleUser($googleId,$googleFullName,$googleFirstName,$googleFamilyName,$googleEmail,$googleImageUrl)
    {
        $query = $this -> bdd -> prepare("
                                            INSERT INTO `googleUser`(
                                                `google_id`,
                                                `google_fullName`,
                                                `google_firstName`,                                                
                                                `google_lastName`,
                                                `google_email`,
                                                `google_pictureUrl`
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
        ");

        $createGoogleUser = $query -> execute([$googleId,$googleFullName,$googleFirstName,$googleFamilyName,$googleEmail,$googleImageUrl]);

        if($createGoogleUser)
        {
            return $this->bdd-> lastInsertId();
        } else {
            return false;
        }
    }

    public function getGoogleUserByEmail($email) 
    {
        $query = $this -> bdd -> prepare ("
                                            SELECT
                                            `google_userId`,
                                            `google_id`,
                                            `google_fullName`,
                                            `google_firstName`,
                                            `google_lastName`,
                                            `google_email`,
                                            `google_pictureUrl`,
                                            `google_confirmEmail`
                                            FROM
                                                `googleUser`
        ");

        $query -> execute([$email]);
        $emailTest = $query -> fetch();

        if($emailTest)
        {
            return $emailTest;
        }
        else
        {
            return false;
        }
    }

    public function updateEmailStatus($email)
    {
        $query = $this-> bdd -> prepare ("
                                            UPDATE
                                                `googleuser`
                                            SET
                                                `google_confirmEmail` = TRUE
                                            WHERE
                                                `google_email` = ?
        ");

        $confirmEmail =$query -> execute([$email]);

        if ($confirmEmail)
        {
            return true;
        }
        else
        {
            return false;            
        }
        
    }
}