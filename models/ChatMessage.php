<?php
namespace models;

use config\DataBase;

class ChatMessage extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function countMessage($userId)
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                `chat_receiverId`,
                                                `chat_senderId`,
                                                COUNT(*) AS `unread_count`
                                            FROM
                                                `chatmessage`
                                            WHERE
                                                `chat_receiverId` = ? 
                                            AND
                                                `chat_status` = 'unread'
                                            GROUP BY
                                                `chat_senderId`
        ");

        $query -> execute([$userId]);
        $unreadTest = $query -> fetchAll();

        if($unreadTest)
        {
            return $unreadTest;
        }
        else
        {
            return false;
        }
    }

    public function insertMessage($senderId, $receiverId, $message, $status) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `chatmessage`(
                                                `chat_senderId`,
                                                `chat_receiverId`,                                    
                                                `chat_message`,
                                                `chat_status`      
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $insertMessageTest = $query -> execute([$senderId, $receiverId, $message, $status]);

        if($insertMessageTest)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function getMessage($userId, $friendId)
    {
        $query = $this -> bdd -> prepare("
                                        SELECT * FROM (
                                            SELECT
                                                *
                                            FROM
                                                `chatmessage`
                                            WHERE
                                                (chat_receiverId = ? AND chat_senderId = ?)
                                                OR
                                                (chat_receiverId = ? AND chat_senderId = ?)
                                            ORDER BY
                                                chat_date DESC
                                            LIMIT 20
                                        ) subquery
                                        ORDER BY
                                            chat_date ASC
        ");

        $query -> execute([$friendId, $userId, $userId, $friendId]);
        $getMessageTest = $query -> fetchAll();

        if($getMessageTest)
        {
            return $getMessageTest;
        }
        else
        {
            return false;
        }
    }

    public function updateMessageStatus($statuts, $userId, $friendId)
    {
        $query = $this -> bdd -> prepare("
                                        UPDATE
                                            `chatmessage`
                                        SET
                                            chat_status = ?
                                        WHERE
                                            (chat_receiverId = ? AND chat_senderId = ?)
                                        AND
                                            `chat_status` = 'unread'
        ");

        $updateStatusTest  = $query -> execute([$statuts, $userId, $friendId]);

        if($updateStatusTest)
        {
            return $updateStatusTest;
        }
        else
        {
            return false;
        }
    }

}
