<?php
namespace models;

use config\DataBase;

class FriendRequest extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function countFriendRequest($userId)
    {
        $query = $this -> bdd -> prepare("
                                        SELECT
                                            COUNT(*)
                                        AS
                                            `friendrequest_count`
                                        FROM
                                            `friendrequest`
                                        WHERE
                                            `fr_receiverId` = ? 
                                        AND
                                            `fr_status` = 'pending'
        ");

        $query -> execute([$userId]);
        $pendingTest = $query -> fetch();

        if($pendingTest)
        {
            return $pendingTest['friendrequest_count'];
        }
        else
        {
            return false;
        }
    }

    public function getUserIdByFrId($frId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            `fr_receiverId`
                                        FROM 
                                            `friendrequest`
                                        WHERE 
                                            `fr_id` = ?
        ");
    
        $query->execute([$frId]);
        $userId = $query->fetch();
    
        return $userId['fr_receiverId'];
    }

    public function skipUserSwipping($userId)
    {
        $query = $this->bdd->prepare("
            SELECT *
            FROM `friendrequest`
            WHERE (`fr_receiverId` = ? OR `fr_senderId` = ?)
            AND `fr_status` IN ('accepted', 'rejected', 'pending')
        ");
    
        $query->execute([$userId, $userId]);
        $friendRequestTests = $query->fetchAll();
    
        if ($friendRequestTests) {
            $friendRequests = [];
    
            foreach ($friendRequestTests as $friendRequestTest) {
                if ($friendRequestTest['fr_senderId'] == $userId) {
                    $friendRequests[] = $friendRequestTest['fr_receiverId'];
                } else {
                    $friendRequests[] = $friendRequestTest['fr_senderId'];
                }
            }
    
            return $friendRequests;
        } else {
            return [];
        }
    }

    public function getFriendRequest($userId)
    {
        $query = $this -> bdd -> prepare("
                                        SELECT
                                            fr.fr_id,
                                            fr.fr_senderId,
                                            fr.fr_receiverId,
                                            fr.fr_date,
                                            fr.fr_status,
                                            u.user_gender,
                                            u.user_age,
                                            u.user_username,
                                            u.user_id,
                                            u.user_picture
                                        FROM
                                            `friendrequest` AS fr
                                        INNER JOIN
                                            `user` AS u
                                        ON 
                                            fr.fr_senderId = u.user_id
                                        WHERE
                                            fr.fr_receiverId = ? 
                                        AND
                                            fr.fr_status  = 'pending'
                                        ORDER BY
                                            fr.fr_date DESC
        ");

        $query -> execute([$userId]);
        $friendRequestTest = $query -> fetchAll();

        if($friendRequestTest)
        {
            return $friendRequestTest;
        }
        else
        {
            return false;
        }
    }

    public function getFriendlist($userId)
    {
        $query = $this->bdd->prepare("
            SELECT
                fr.fr_id,
                fr.fr_senderId,
                fr.fr_receiverId,
                fr.fr_date,
                fr.fr_status,
                fr.fr_acceptedAt,
                us.user_id AS sender_id,
                us.user_username AS sender_username,
                us.user_picture AS sender_picture,
                us.user_game AS sender_game,
                us.user_lastRequestTime AS sender_lastRequestTime,
                ur.user_id AS receiver_id,
                ur.user_username AS receiver_username,
                ur.user_picture AS receiver_picture,
                ur.user_game AS receiver_game,
                ur.user_lastRequestTime AS receiver_lastRequestTime,
                c.latest_message_date,
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(us.user_lastRequestTime) <= 30) AS sender_isOnline,
                (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(ur.user_lastRequestTime) <= 30) AS receiver_isOnline
            FROM
                `friendrequest` AS fr
            INNER JOIN
                `user` AS us ON fr.fr_senderId = us.user_id
            INNER JOIN
                `user` AS ur ON fr.fr_receiverId = ur.user_id
            LEFT JOIN (
                SELECT
                    CASE 
                        WHEN chat_senderId = :userId1 THEN chat_receiverId
                        WHEN chat_receiverId = :userId2 THEN chat_senderId
                    END AS friend_id,
                    MAX(chat_date) AS latest_message_date
                FROM
                    `chatmessage`
                WHERE
                    chat_senderId = :userId3 OR chat_receiverId = :userId4
                GROUP BY
                    friend_id
            ) AS c
            ON (fr.fr_senderId = c.friend_id AND fr.fr_receiverId = :userId5)
            OR (fr.fr_receiverId = c.friend_id AND fr.fr_senderId = :userId6)
            WHERE
                (fr.fr_senderId = :userId7 OR fr.fr_receiverId = :userId8)
            AND
                fr.fr_status = 'accepted'
            ORDER BY
                COALESCE(c.latest_message_date, fr.fr_acceptedAt) DESC
        ");
    
        $query->execute([
            'userId1' => $userId,
            'userId2' => $userId,
            'userId3' => $userId,
            'userId4' => $userId,
            'userId5' => $userId,
            'userId6' => $userId,
            'userId7' => $userId,
            'userId8' => $userId,
        ]);
    
        $friendlistTest = $query->fetchAll();
    
        if ($friendlistTest) {
            return $friendlistTest;
        } else {
            return false;
        }
    }
    

    public function acceptFriendRequest($frId) 
    {
        $query = $this -> bdd -> prepare("
                                        UPDATE
                                            `friendrequest`
                                        SET
                                            `fr_status` = 'accepted',
                                            `fr_acceptedAt` = NOW()
                                        WHERE
                                            fr_id = ?
        ");

        
        $acceptedFriendRequestTest =  $query->execute([$frId]);

        if($acceptedFriendRequestTest)
        {
            return  $acceptedFriendRequestTest;
        }
        else
        {
            return false;
        }        
    }

    public function rejectFriendRequest($frId) 
    {
        $query = $this -> bdd -> prepare("
                                        UPDATE
                                            `friendrequest`
                                        SET
                                            `fr_status` = 'rejected',
                                            `fr_rejectedAt` = NOW()
                                        WHERE
                                            fr_id = ?
        ");

        $rejectedFriendRequestTest =  $query->execute([$frId]);

        if($rejectedFriendRequestTest)
        {
            return  $rejectedFriendRequestTest;
        }
        else
        {
            return false;
        }        
    }

    public function deleteFriendRequestAfterWeek()
    {
        $query = $this->bdd->prepare("
                                        DELETE FROM
                                            `friendrequest`
                                        WHERE
                                            `fr_RejectedAt` < NOW() - INTERVAL 1 WEEK
        ");

        $success = $query->execute();

        return $success;
    }

    public function updateFriend($senderId, $receiverId) 
    {
        $query = $this -> bdd -> prepare("
                                        UPDATE
                                            `friendrequest`
                                        SET
                                            `fr_status` = 'rejected',
                                            `fr_rejectedAt` = NOW()
                                        WHERE
                                            (fr_senderId = ? AND fr_receiverId = ?) OR (fr_senderId = ? AND fr_receiverId = ?)
        ");

        
        $updateFriendTest =  $query->execute([$senderId, $receiverId, $receiverId, $senderId]);

        if($updateFriendTest)
        {
            return  $updateFriendTest;
        }
        else
        {
            return false;
        }        
    }

    public function swipeStatusYes($senderId, $receiverId, $requestDate, $status) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `friendrequest`(
                                                `fr_senderId`,
                                                `fr_receiverId`,                                    
                                                `fr_date`,
                                                `fr_status`
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $swipeStatusTest = $query -> execute([$senderId, $receiverId, $requestDate, $status]);

        if($swipeStatusTest)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function swipeStatusNo($senderId, $receiverId, $requestDate, $status) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `friendrequest`(
                                                `fr_senderId`,
                                                `fr_receiverId`,                                    
                                                `fr_date`,
                                                `fr_status`,  
                                                `fr_rejectedAt`   
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?,
                                                NOW()
                                            )
                                        ");

        $swipeStatusTest = $query -> execute([$senderId, $receiverId, $requestDate, $status]);

        if($swipeStatusTest)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function checkifPending($receiverId, $senderId)
    {
        $query = $this->bdd->prepare("
                                    SELECT 
                                        *
                                    FROM 
                                        `friendrequest`
                                    WHERE 
                                        (fr_senderId = ? AND fr_receiverId = ?) OR (fr_senderId = ? AND fr_receiverId = ?)
                                    AND 
                                        `fr_status` = 'pending'
        ");
    
        $query->execute([$senderId, $receiverId, $senderId, $receiverId]);
        $checkPendingTest = $query->fetch();
        
        if ($checkPendingTest)
        {
            return true;
        }
        else
        {
            return false;  
        }
    }

    public function updateFriendRequest($receiverId, $senderId, $status) 
    {
        $query = $this -> bdd -> prepare("
                                        UPDATE
                                            `friendrequest`
                                        SET
                                            `fr_status` = ?
                                        WHERE
                                            (fr_senderId = ? AND fr_receiverId = ?) OR (fr_senderId = ? AND fr_receiverId = ?)
        ");

        
        $acceptedFriendRequestTest =  $query->execute([$status, $senderId, $receiverId, $receiverId, $senderId]);

        if($acceptedFriendRequestTest)
        {
            return  $acceptedFriendRequestTest;
        }
        else
        {
            return false;
        }        
    }

    public function getFriendStatus($receiverId, $senderId)
    {
        $query = $this->bdd->prepare("
                                        SELECT 
                                            `fr_status`
                                        FROM 
                                            `friendrequest`
                                        WHERE 
                                            (fr_senderId = ? AND fr_receiverId = ?) OR (fr_senderId = ? AND fr_receiverId = ?)
        ");
    
        $query->execute([$senderId, $receiverId, $senderId, $receiverId]);
        $friendStatusTest = $query->fetch();
        
        if ($friendStatusTest)
        {
            return $friendStatusTest['fr_status'];
        }
        else
        {
            return false;  
        }
    }

}
