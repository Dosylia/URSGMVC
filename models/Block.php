<?php
namespace models;

use config\DataBase;

class Block extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function getBlocklist($userId)
    {
        $query = $this->bdd->prepare("
                                    SELECT
                                        b.block_id,
                                        b.block_senderId,
                                        b.block_receiverId,
                                        b.block_date,
                                        u.user_id,
                                        u.user_username,
                                        u.user_picture
                                    FROM
                                        `block` AS b
                                    INNER JOIN
                                        `user` AS u
                                    ON 
                                        b.block_receiverId = u.user_id
                                    WHERE
                                        b.block_senderId = ?
                                    ORDER BY
                                        b.block_date DESC
        ");
    
        $query->execute([$userId]);
        $BlocklistTest = $query->fetchAll();
    
        if ($BlocklistTest) {
            return $BlocklistTest;
        } else {
            return false;
        }
    }

    public function isBlocked($senderId, $receiverId)
{
    $query = $this->bdd->prepare("
                                    SELECT 1 FROM `block`
                                    WHERE 
                                        (block_senderId = ? AND block_receiverId = ?)
                                        OR
                                        (block_senderId = ? AND block_receiverId = ?)
    ");

    $query->execute([$senderId, $receiverId, $receiverId, $senderId]);
    return $query->fetch() ? true : false;
}

    public function blockPerson($senderId, $receiverId, $date) 
    {

        $query = $this -> bdd -> prepare("
                                        INSERT INTO `block`(
                                            block_senderId,
                                            block_receiverId,
                                            block_date
                                        )
                                        VALUES (
                                            ?,
                                            ?,
                                            ?
                                        )
        ");

        
        $blockedPersonTest =  $query->execute([$senderId, $receiverId, $date]);

        if($blockedPersonTest)
        {
            return  $blockedPersonTest;
        }
        else
        {
            return false;
        }        
    }

    
    public function unblockPerson($blockId)
    {

        $query = $this->bdd->prepare("
                                    DELETE FROM
                                        `block`
                                    WHERE
                                        `block_id` = ?
        ");

        $success = $query->execute([$blockId]);

        return $success;

    }
    

}
