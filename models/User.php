<?php
namespace models;

use config\DataBase;

class User extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function getUserByUsername($username)
    {

        $query = $this -> bdd -> prepare("
                                            SELECT
                                                `user_id`,
                                                `google_userId`,
                                                `user_username`,
                                                `user_gender`,
                                                `user_age`,
                                                `user_kindOfGamer`,
                                                `user_game`,
                                                `user_shortBio`,
                                                `user_picture`,
                                                `user_discord`,
                                                `user_instagram`,
                                                `user_twitter`,
                                                `user_twitch`
                                            FROM
                                                `user`
                                            WHERE
                                                `user_username` = ?
        ");

        $query -> execute([$username]);
        $user = $query -> fetch();

        if ($user)
        {
            return $user;
        }
        else
        {
            return false;
        }

    }

    public function createUser($googleUserId, $username, $gender, $age, $kindofgamer, $short_bio, $game) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `user`(
                                                `google_userId`,
                                                `user_username`,
                                                `user_gender`,                                                
                                                `user_age`,
                                                `user_kindOfGamer`,
                                                `user_shortBio`,
                                                `user_game`
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $createWebsiteUser = $query -> execute([$googleUserId, $username, $gender, $age, $kindofgamer, $short_bio, $game]);

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