<?php
namespace models;

use config\DataBase;

class PlayerFinder extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function addPlayerFinderPost($role, $rank, $description, $voicechat, $game, $userId) 
    {
        $query = $this -> bdd -> prepare("
                                            INSERT INTO `playerfinder`(
                                                `user_id`,
                                                `pf_role`,
                                                `pf_rank`,
                                                `pf_description`,
                                                `pf_voicechat`,
                                                `pf_game`
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

        $addPlayerFinderPost = $query -> execute([$userId, $role, $rank, $description, $voicechat, $game]);


        if($addPlayerFinderPost)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function getRandomPlayerFinderPost($filters)
    {
        $sql = "
            SELECT 
                pf.*, 
                u.user_username, u.user_picture, u.user_game, u.user_id,
                lol.lol_rank, lol.lol_role, lol.lol_server,
                val.valorant_rank, val.valorant_role, val.valorant_server
            FROM playerfinder pf
            JOIN user u ON pf.user_id = u.user_id
            LEFT JOIN leagueoflegends lol ON lol.user_id = u.user_id
            LEFT JOIN valorant val ON val.user_id = u.user_id
            WHERE pf.user_id != :userId
            AND NOT EXISTS (
                SELECT 1 
                FROM friendrequest fr
                WHERE 
                    (
                        (fr.fr_senderId = :userId AND fr.fr_receiverId = u.user_id)
                        OR
                        (fr.fr_senderId = u.user_id AND fr.fr_receiverId = :userId)
                    )
                    AND fr.fr_status = 'accepted'
            )
        ";

        $params = [
            ':userId' => $filters['userId']
        ];

        // Apply only filters that are NOT null
        if (!empty($filters['game'])) {
            $sql .= " AND pf.pf_game = :game ";
            $params[':game'] = $filters['game'];
        }

        if (!empty($filters['voiceChat'])) {
            $sql .= " AND pf.pf_voiceChat = :voiceChat ";
            $params[':voiceChat'] = $filters['voiceChat'];
        }

        if (!empty($filters['role'])) {
            $sql .= " AND pf.pf_role = :role ";
            $params[':role'] = $filters['role'];
        }

        if (!empty($filters['rank'])) {
            $sql .= " AND pf.pf_rank = :rank ";
            $params[':rank'] = $filters['rank'];
        }

        // Randomize result
        $sql .= " ORDER BY RAND() LIMIT 1";

        $query = $this->bdd->prepare($sql);
        $query->execute($params);

        return $query->fetch() ?: false;
    }

    public function getAllPlayerFinderPost() 
    {
        $query = $this->bdd->prepare("
            SELECT 
                pf.*, 
                u.user_username, u.user_picture, u.user_game, u.user_id,
                lol.lol_rank, lol.lol_role, lol.lol_server,
                val.valorant_rank, val.valorant_role, val.valorant_server
            FROM playerfinder pf
            JOIN user u ON pf.user_id = u.user_id
            LEFT JOIN leagueoflegends lol ON lol.user_id = u.user_id
            LEFT JOIN valorant val ON val.user_id = u.user_id
            ORDER BY pf.pf_id DESC
        ");
        
        $query->execute();
        $getAllPlayerFinderPost = $query->fetchAll(\PDO::FETCH_ASSOC);
    
        return $getAllPlayerFinderPost ?: false;
    }

        public function getPlayerFinderLasts() 
    {
        $query = $this->bdd->prepare("
            SELECT 
                pf.*, 
                u.user_username, u.user_picture, u.user_game, u.user_id,
                lol.lol_rank, lol.lol_role, lol.lol_server,
                val.valorant_rank, val.valorant_role, val.valorant_server
            FROM playerfinder pf
            JOIN user u ON pf.user_id = u.user_id
            LEFT JOIN leagueoflegends lol ON lol.user_id = u.user_id
            LEFT JOIN valorant val ON val.user_id = u.user_id
            ORDER BY pf.pf_id DESC
            LIMIT 10
        ");
        
        $query->execute();
        $getAllPlayerFinderPost = $query->fetchAll(\PDO::FETCH_ASSOC);
    
        return $getAllPlayerFinderPost ?: false;
    }

    public function getPlayerFinderPost($userId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT * FROM `playerfinder`
                                            WHERE `user_id` = ?
                                        ");

        $query -> execute([$userId]);
        $getPlayerFinderPost = $query -> fetch();

        if($getPlayerFinderPost)
        {
            return $getPlayerFinderPost;
        }
        else 
        {
            return false;  
        }
    }

    public function getPlayerFinderPostById($postId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT * FROM `playerfinder`
                                            WHERE `pf_id` = ?
                                        ");

        $query -> execute([$postId]);
        $getPlayerFinderPostById = $query -> fetch();

        if($getPlayerFinderPostById)
        {
            return $getPlayerFinderPostById;
        } else 
        {
            return false;  
        }
    }

    public function deletePlayerFinderPost($postId) 
    {
        $query = $this -> bdd -> prepare("
                                            DELETE FROM `playerfinder`
                                            WHERE `pf_id` = ?
                                        ");

        $deletePlayerFinderPost = $query -> execute([$postId]);

        if($deletePlayerFinderPost)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function updatePeopleInterest($postId, $interested)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `playerfinder`
                                            SET
                                                `pf_peopleInterest` = ?
                                            WHERE
                                                `pf_id` = ?
                                        ");

        $updatePeopleInterest = $query->execute([json_encode($interested), $postId]);

        if($updatePeopleInterest)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function editPlayerPost($postId, $role, $rank, $description)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE
                                                `playerfinder`
                                            SET
                                                `pf_role` = ?,
                                                `pf_rank` = ?,
                                                `pf_description` = ?
                                            WHERE
                                                `pf_id` = ?
                                        ");

        $editPlayerPost = $query->execute([$role, $rank, $description, $postId]);

        if($editPlayerPost)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

}
