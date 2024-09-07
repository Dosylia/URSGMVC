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
                                                *
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

    public function getUserById($userId)
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `user` AS u
                                            LEFT JOIN
                                                `leagueoflegends` AS l
                                            ON
                                                u.user_id = l.user_id
                                            LEFT JOIN
                                                `userlookingfor` AS lf
                                            ON
                                                u.user_id = lf.user_id
                                            WHERE
                                                u.user_id = ?
        ");
    
        $query -> execute([$userId]);
        $user = $query -> fetch();
    
        if ($user) {
            return $user;
        } else {
            return false;
        }
    }

    public function getAllUsers()
    {

        $query = $this -> bdd -> prepare("
                                            SELECT
                                               *
                                            FROM
                                                `user` AS u
                                            INNER JOIN
                                                `leagueoflegends` AS l
                                            ON
                                                u.user_id = l.user_id
                                            INNER JOIN
                                                `userlookingfor` AS lf
                                            ON
                                                u.user_id = lf.user_id

        ");

        $query -> execute();
        $users = $query -> fetchAll();


        if ($users)
        {
            return $users;
        }
        else
        {
            return false;
        }

    }

    
    public function getAllUsersExceptFriends($userId)
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `user` AS u
                                            INNER JOIN
                                                `leagueoflegends` AS l ON u.user_id = l.user_id
                                            INNER JOIN
                                                `userlookingfor` AS lf ON u.user_id = lf.user_id
                                            LEFT JOIN
                                                `friendrequest` AS fr1 ON u.user_id = fr1.fr_senderId AND fr1.fr_receiverId = ?
                                            LEFT JOIN
                                                `friendrequest` AS fr2 ON u.user_id = fr2.fr_receiverId AND fr2.fr_senderId = ?
                                            WHERE
                                                fr1.fr_id IS NULL AND fr2.fr_id IS NULL
        ");
    
        $query -> execute([$userId, $userId]);
        $users = $query -> fetchAll();
    
        if ($users) {
            return $users;
        } else {
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

    public function registerToken($userId, $token)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET
                                            `user_token` = ?
                                        WHERE
                                            `user_id` = ?
        ");

        $registerTokenTest = $query->execute([$token, $userId]);

        if ($registerTokenTest) {
            return true;
        } else {
            return false;  
        }
    }

    public function getToken($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT
                                            `user_token`
                                        FROM
                                            `user`
                                        WHERE
                                            `user_id` = ?
        ");

        $query->execute([$userId]);
        $token = $query->fetch();

        if ($token) {
            return $token;
        } else {
            return false;
        }
    }

    public function updateSocial($username, $discord, $twitter, $instagram, $twitch) 
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user` 
                                        SET
                                            `user_discord` = ?,
                                            `user_twitter` = ?,
                                            `user_instagram` = ?,
                                            `user_twitch` = ?
                                        WHERE
                                            `user_username` = ?
        ");

        $updateSocialTest = $query->execute([$discord, $twitter, $instagram, $twitch, $username]);

        if ($updateSocialTest) {
            return true;
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


    public function getUserDataByGoogleUserId($googleUserId)
    {
                $query = $this -> bdd -> prepare ("
                                                        SELECT
                                                        u.user_id,
                                                        u.google_userId,
                                                        u.user_username,
                                                        u.user_gender,
                                                        u.user_age,
                                                        u.user_kindOfGamer,
                                                        u.user_shortBio,
                                                        u.user_game
                                                        FROM
                                                            `user` as u
                                                        INNER JOIN
                                                            `googleuser` as g
                                                        ON
                                                            u.google_userId = g.google_userId
                                                        WHERE
                                                             g.google_userId = ?
                                                ");

        $query -> execute([$googleUserId]);
        $googleUserIdTest = $query -> fetch();


        if($googleUserIdTest)
        {
            return $googleUserIdTest;
        }
        else
        {
            return false;
        }
    }
}
