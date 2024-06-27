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
                                            COUNT(*)
                                        AS
                                            `unread_count`
                                        FROM
                                            `chatmessage`
                                        WHERE
                                            `chat_receiverId` = ? 
                                        AND
                                            `chat_status` = 'unread'
        ");

        $query -> execute([$userId]);
        $unreadTest = $query -> fetch();

        if($unreadTest)
        {
            return $unreadTest['unread_count'];
        }
        else
        {
            return false;
        }
    }

}
