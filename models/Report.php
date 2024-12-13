<?php
namespace models;

use config\DataBase;

class Report extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function reportUser($userId, $reportedId, $content, $status, $reason) 
    {
        $query = $this->bdd->prepare("
                                        INSERT INTO `reports` (
                                            `reporter_id`,
                                            `reported_id`,
                                            `content_type`,   
                                            `status`,                                             
                                            `reason`,
                                            `created_at`
                                        ) VALUES (
                                            ?,
                                            ?,
                                            ?,
                                            ?,
                                            ?,
                                            NOW()
                                        )
    ");

        $reportUserTest = $query -> execute([$userId, $reportedId, $content, $status, $reason]);

        if ($reportUserTest) {
            return true;
        } else {
            return false;
        }
    }

}
