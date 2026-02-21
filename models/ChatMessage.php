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
        $query = $this->bdd->prepare("
                                        SELECT
                                            c.chat_senderId,
                                            u.user_username,
                                            u.user_picture,
                                            c.chat_message,
                                            unread_counts.unread_count
                                        FROM
                                            (
                                                SELECT
                                                    chat_senderId,
                                                    MAX(chat_id) AS latest_chat_id,
                                                    COUNT(*) AS unread_count
                                                FROM
                                                    chatmessage
                                                WHERE
                                                    chat_receiverId = ?
                                                    AND chat_status = 'unread'
                                                GROUP BY
                                                    chat_senderId
                                            ) AS unread_counts
                                        JOIN chatmessage c ON c.chat_id = unread_counts.latest_chat_id
                                        JOIN user u ON c.chat_senderId = u.user_id
        ");

        $query->execute([$userId]);
        $unreadTest = $query->fetchAll();

        if ($unreadTest) {
            return $unreadTest;
        } else {
            return false;
        }
    }

    public function getUnreadSummary($userId)
    {
        $query = $this->bdd->prepare("
            SELECT 
                COUNT(*) AS unread_count,
                (
                    SELECT u.user_username
                    FROM chatmessage cm2
                    JOIN user u ON u.user_id = cm2.chat_senderId
                    WHERE cm2.chat_receiverId = :userId
                    AND cm2.chat_status = 'unread'
                    ORDER BY cm2.chat_date DESC
                    LIMIT 1
                ) AS latest_sender
            FROM chatmessage cm
            WHERE cm.chat_receiverId = :userId
            AND cm.chat_status = 'unread'
        ");
        $query->execute(['userId' => $userId]);
        return $query->fetch();
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

    // Random Chat Session Methods

    public function getActiveRandomChatSession($senderId, $receiverId)
    {
        $query = $this->bdd->prepare("
            SELECT 
                session_id, 
                initiator_user_id, 
                target_user_id, 
                session_status,
                created_at
            FROM 
                random_chat_sessions 
            WHERE 
                ((initiator_user_id = ? AND target_user_id = ?) 
                OR (initiator_user_id = ? AND target_user_id = ?))
                AND session_status = 'active'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $query->execute([$senderId, $receiverId, $receiverId, $senderId]);
        $result = $query->fetch();
        
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function createRandomChatSession($initiatorId, $targetId)
    {
        $query = $this->bdd->prepare("
            INSERT INTO random_chat_sessions 
            (initiator_user_id, target_user_id, session_status, created_at) 
            VALUES (?, ?, 'active', NOW())
        ");
        
        $result = $query->execute([$initiatorId, $targetId]);
        
        if ($result) {
            return $this->bdd->lastInsertId();
        } else {
            return false;
        }
    }

    public function getRandomChatSession($userId, $targetUserId)
    {
        $query = $this->bdd->prepare("
            SELECT 
                session_id, 
                initiator_user_id, 
                target_user_id, 
                session_status
            FROM 
                random_chat_sessions 
            WHERE 
                ((initiator_user_id = ? AND target_user_id = ?) 
                OR (initiator_user_id = ? AND target_user_id = ?))
                AND session_status = 'active'
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $query->execute([$userId, $targetUserId, $targetUserId, $userId]);
        $result = $query->fetch();
        
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function updateRandomChatSession($sessionId, $newStatus)
    {
        $query = $this->bdd->prepare("
            UPDATE random_chat_sessions 
            SET 
                session_status = ?,
                closed_at = NOW()
            WHERE 
                session_id = ?
        ");
        
        $result = $query->execute([$newStatus, $sessionId]);
        
        if ($result) {
            return true;
        } else {
            return false;
        }
    }


    // Get incoming random chat sessions for a user (when someone finds them)
    public function getIncomingRandomChatSessions($userId)
    {
        $query = $this->bdd->prepare("
            SELECT 
                session_id, 
                initiator_user_id, 
                target_user_id, 
                session_status,
                created_at
            FROM 
                random_chat_sessions 
            WHERE 
                target_user_id = ?
                AND session_status = 'active'
            ORDER BY created_at DESC
        ");
        
        $query->execute([$userId]);
        $result = $query->fetchAll();
        
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function getAllActiveRandomChatSessions()
    {
        $query = $this->bdd->prepare("
            SELECT 
                session_id, 
                initiator_user_id, 
                target_user_id, 
                session_status,
                created_at,
                last_fetch_initiator,
                last_fetch_target,
                closed_at
            FROM 
                random_chat_sessions 
            WHERE 
                session_status = 'active'
            ORDER BY created_at DESC
        ");
        
        $query->execute();
        $result = $query->fetchAll();
        
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function updateRandomChatLastFetch($sessionId, $userId)
    {
        // Determine which column to update based on whether the user is initiator or target
        $sessionQuery = $this->bdd->prepare("
            SELECT initiator_user_id, target_user_id 
            FROM random_chat_sessions 
            WHERE session_id = ?
        ");
        $sessionQuery->execute([$sessionId]);
        $session = $sessionQuery->fetch();
        
        if (!$session) {
            return false;
        }
        
        $columnToUpdate = ($session['initiator_user_id'] == $userId) ? 'last_fetch_initiator' : 'last_fetch_target';
        
        $query = $this->bdd->prepare("
            UPDATE random_chat_sessions 
            SET {$columnToUpdate} = NOW() 
            WHERE session_id = ?
        ");
        
        return $query->execute([$sessionId]);
    }

    public function closeRandomChatBySessionId($sessionId)
    {
        $query = $this->bdd->prepare("
            UPDATE random_chat_sessions 
            SET 
                session_status = 'closed',
                closed_at = NOW()
            WHERE 
                session_id = ?
        ");
        
        return $query->execute([$sessionId]);
    }

    public function closeRandomChatSession($userId, $targetUserId)
    {
        $query = $this->bdd->prepare("
            UPDATE random_chat_sessions 
            SET 
                session_status = 'closed',
                closed_at = NOW()
            WHERE 
                ((initiator_user_id = ? AND target_user_id = ?) 
                OR (initiator_user_id = ? AND target_user_id = ?))
                AND session_status = 'active'
        ");
        
        return $query->execute([$userId, $targetUserId, $targetUserId, $userId]);
    }


}
