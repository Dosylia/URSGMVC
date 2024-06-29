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

    public function skipUserSwipping($userId) // TEST ON SWIPPING PAGE LATER
    {
        $query = $this -> bdd -> prepare("
                                        SELECT
                                            *
                                        FROM
                                            `friendrequest`
                                        WHERE
                                             (`fr_receiverId` = ? OR `fr_senderId` = ?)
                                        AND
                                            `fr_status` IN ('accepted', 'rejected', 'pending')
        ");

        $query -> execute([$userId, $userId]);
        $friendRequestTest = $query -> fetchAll();

        $friendRequests = [];

        if($friendRequestTest)
        {
            foreach ($friendRequestTests as $friendRequestTest) 
            {
                if ($friendRequestTest['fr_senderId'] == $userId) 
                {
                    $friendRequests[] = $friendRequestTest['fr_receiverId'];
                } 
                else 
                {
                    $friendRequests[] = $friendRequestTest['fr_senderId'];
                }
            }
            return $friendRequests;
        }
        else
        {
            return false;
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
                                            u.user_username
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
                                        u.user_id,
                                        u.user_username,
                                        u.user_picture
                                    FROM
                                        `friendrequest` AS fr
                                    INNER JOIN
                                        `user` AS u
                                    ON 
                                        fr.fr_senderId = u.user_id
                                    WHERE
                                        (fr.fr_senderId = ? OR fr.fr_receiverId = ?)
                                    AND
                                        fr.fr_status = 'accepted'
                                    ORDER BY
                                        fr.fr_date DESC
        ");
    
        $query->execute([$userId, $userId]);
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
                                            `fr_status` = 'accepted'
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
                                            `fr_status` = 'rejected'
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

    public function updateFriend($senderId, $receiverId) 
    {
        $query = $this -> bdd -> prepare("
                                        UPDATE
                                            `friendrequest`
                                        SET
                                            `fr_status` = 'rejected'
                                        WHERE
                                            (fr_senderId = ? AND fr_receiverId = ?) OR (fr_senderId = ? AND fr_receiverId = ?)
        ");

        
        $updateFriendTest =  $query->execute([$receiverId, $senderId, $receiverId, $senderId]);

        if($updateFriendTest)
        {
            return  $updateFriendTest;
        }
        else
        {
            return false;
        }        
    }

}
