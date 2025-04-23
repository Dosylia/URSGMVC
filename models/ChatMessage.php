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
                                                c.chat_receiverId,
                                                c.chat_senderId,
                                                c.chat_message,
                                                u.user_username,
                                                u.user_picture,
                                                COUNT(*) AS `unread_count`
                                            FROM
                                                `chatmessage` AS c
                                            INNER JOIN
                                                `user` AS u
                                            ON 
                                                c.chat_senderId = u.user_id
                                            WHERE
                                                c.chat_receiverId = ? 
                                            AND
                                                c.chat_status = 'unread'
                                            GROUP BY
                                                c.chat_senderId
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

    public function deleteMessageUser($chatId)
    {
        $query = $this -> bdd -> prepare("
                                            UPDATE `chatmessage`
                                            SET `chat_message` = 'This message has been deleted'
                                            WHERE `chat_id` = ?
        ");
    
        $updateMessageTest = $query -> execute([$chatId]);
    
        if($updateMessageTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function insertMessageWebsite($senderId, $receiverId, $message, $replyToChatId, $status) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `chatmessage`(
                                                `chat_senderId`,
                                                `chat_receiverId`,                                    
                                                `chat_message`,
                                                `chat_replyTo`,
                                                `chat_status`      
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $insertMessageTest = $query -> execute([$senderId, $receiverId, $message, $replyToChatId, $status]);

        if($insertMessageTest)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function getMessageById($chatId)
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `chatmessage`
                                            WHERE
                                                `chat_id` = ?
        ");

        $query -> execute([$chatId]);
        $getMessageTest = $query -> fetch();

        if($getMessageTest)
        {
            return $getMessageTest;
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
                                            LIMIT 100
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

    public function getNewMessages($userId, $friendId, $messageId)
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
                                            AND
                                                `chat_id` > ?
                                            ORDER BY
                                                chat_date DESC
                                            LIMIT 20
                                        ) subquery
                                        ORDER BY
                                            chat_date ASC
        ");

        $query -> execute([$friendId, $userId, $userId, $friendId, $messageId]);
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

    public function createRecentMessagesTable()
    {
        try {
            $this->bdd->exec("
                CREATE TEMPORARY TABLE recent_messages AS
                (
                    SELECT chat_id
                    FROM (
                        SELECT chat_id, ROW_NUMBER() OVER (PARTITION BY chat_senderId, chat_receiverId ORDER BY chat_date DESC) AS row_num
                        FROM chatmessage
                    ) AS ranked_messages
                    WHERE row_num <= 20
                );
            ");
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception("Failed to create recent messages table.");
        }
    }


    public function deleteOldMessage()
    {
        $query = $this->bdd->prepare("
                                        DELETE FROM chatmessage
                                    WHERE chat_id NOT IN (SELECT chat_id FROM recent_messages);
    ");
    
        $deleteOldMessageTest = $query->execute();
    
        if ($deleteOldMessageTest) {
            return $deleteOldMessageTest;
        } else {
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

    public function deleteMessageUnfriend($userId, $friendId)
    {
        $query = $this -> bdd -> prepare("
                                        DELETE FROM
                                            `chatmessage`
                                        WHERE
                                            (chat_receiverId = ? AND chat_senderId = ?)
                                            OR
                                            (chat_receiverId = ? AND chat_senderId = ?)
        ");

        $deleteMessageTest = $query -> execute([$userId, $friendId, $friendId, $userId]);

        if($deleteMessageTest)
        {
            return $deleteMessageTest;
        }
        else
        {
            return false;
        }
    }

    public function queueNotificationWebsite($senderId, $receiverId, $message, $type, $endpoint, $p256dh, $auth) 
    {
        $query = $this -> bdd -> prepare("
                                            INSERT INTO `notifications_queue`(
                                                `user_id`,
                                                `sender_id`,         
                                                `message`,
                                                `type`,
                                                `endpoint`,
                                                `p256dh`,
                                                `auth`   
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

        $queueNotificationTest = $query -> execute([$receiverId, $senderId, $message, $type, $endpoint, $p256dh, $auth]);

        if($queueNotificationTest)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function queueNotificationPhone($senderId, $receiverId, $message, $type, $expoToken) 
    {
        $query = $this -> bdd -> prepare("
                                            INSERT INTO `notifications_queue`(
                                                `user_id`,
                                                `sender_id`,         
                                                `message`,
                                                `type`,
                                                `expoToken`   
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $queueNotificationTest = $query -> execute([$receiverId, $senderId, $message, $type, $expoToken]);

        if($queueNotificationTest)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function getAllQueuedNotifications()
    {
        $query = $this -> bdd -> prepare("
                                            SELECT 
                                                * 
                                            FROM 
                                                `notifications_queue`
        ");

        $query -> execute();
        $getAllQueuedNotificationTest = $query -> fetchAll();

        if($getAllQueuedNotificationTest)
        {
            return $getAllQueuedNotificationTest;
        }
        else
        {
            return false;
        }
    }

    public function deleteQueuedNotification($notificationId)
    {
        $query = $this -> bdd -> prepare("
                                            DELETE FROM 
                                                `notifications_queue`
                                            WHERE
                                                `id` = ?
        ");

        $deleteQueuedNotificationTest = $query -> execute([$notificationId]);

        if($deleteQueuedNotificationTest)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

}
