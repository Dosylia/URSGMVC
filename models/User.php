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

    public function getUserDataByGoogleId($googleId)
    {
                $query = $this -> bdd -> prepare ("
                SELECT
                u.`user_id`,
                u.`google_userId`,
                u.`user_username`,
                u.`user_gender`,
                u.`user_age`,
                u.`user_kindOfGamer`,
                u.`user_shortBio`,
                u.`user_game`
                FROM
                    `user` as u
                INNER JOIN
                    googleuser as g
                ON
                    u.google_userId = g.google_userId


        ");

        $query -> execute([$googleId]);
        $googleIdTest = $query -> fetch();

        if($googleIdTest)
        {
            return $googleIdTest;
        }
        else
        {
            return false;
        }
    }
}
