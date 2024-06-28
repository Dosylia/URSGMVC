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

    public function getAllUsers()
    {

        $query = $this -> bdd -> prepare("
                                            SELECT
                                               *
                                            FROM
                                                `user`
        ");

        $query -> execute();
        $users = $query -> fetch();

        if ($users)
        {
            return $users;
        }
        else
        {
            return false;
        }

    }

    public function createUser($googleUserId, $username, $gender, $age, $kindOfGamer, $shortBio, $game) 
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

        $createWebsiteUser = $query -> execute([$googleUserId, $username, $gender, $age, $kindOfGamer, $shortBio, $game]);

        if($createWebsiteUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function updateUser($username, $gender, $age, $kindOfGamer, $shortBio, $game) 
    {
        $sql = "UPDATE `user` SET ";
        $params = [];
        $updates = [];
    
        if (!empty($gender)) {
            $updates[] = "`user_gender` = ?";
            $params[] = $gender;
        }
        if (!empty($age)) {
            $updates[] = "`user_age` = ?";
            $params[] = $age;
        }
        if (!empty($kindOfGamer)) {
            $updates[] = "`user_kindOfGamer` = ?";
            $params[] = $kindOfGamer;
        }
        if (!empty($shortBio)) {
            $updates[] = "`user_shortBio` = ?";
            $params[] = $shortBio;
        }
        if (!empty($game)) {
            $updates[] = "`user_game` = ?";
            $params[] = $game;
        }
    
        $sql .= implode(", ", $updates) . " WHERE `user_username` = ?";
        $params[] = $username;
    
        if (!empty($updates)) {
            $query = $this->bdd->prepare($sql);
            $updateUserTest = $query->execute($params);
    
            if ($updateUserTest) {
                return true;
            } else {
                return false;  
            }
        } else {
            return false;
        }
    }

    public function updateSocial($username, $discord, $twitter, $instagram, $twitch) 
    {
        $sql = "UPDATE `user` SET ";
        $params = [];
        $updates = [];
    
        if (!empty($discord)) {
            $updates[] = "`user_discord` = ?";
            $params[] = $discord;
        }
        if (!empty($twitter)) {
            $updates[] = "`user_twitter` = ?";
            $params[] = $twitter;
        }
        if (!empty($instagram)) {
            $updates[] = "`user_instagram` = ?";
            $params[] = $instagram;
        }
        if (!empty($twitch)) {
            $updates[] = "`user_twitch` = ?";
            $params[] = $twitch;
        }
    
        $sql .= implode(", ", $updates) . " WHERE `user_username` = ?";
        $params[] = $username;
    
        if (!empty($updates)) {
            $query = $this->bdd->prepare($sql);
            $updateSocialTest = $query->execute($params);
    
            if ($updateSocialTest) {
                return true;
            } else {
                return false;  
            }
        } else {
            return false;
        }
    }

    public function uploadPicture($username, $fileName) 
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET
                                            `user_picture` = ?
                                        WHERE
                                            `user_username` = ?

                                        ");

        $uploadPictureTest = $query->execute([$fileName,$username]);

        if($uploadPictureTest)
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
