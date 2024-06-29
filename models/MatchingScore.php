<?php
namespace models;

use config\DataBase;

class MatchingScore extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function checkMatching($userMatching, $userMatched) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `matchingscore`
                                            WHERE
                                                `match_userMatching` = ?
                                            AND
                                                `match_userMatched` = ?
        ");

        $query -> execute([$userMatching, $userMatched]);
        $checkMatchingTest = $query -> fetch();

        if ($checkMatchingTest)
        {
            return $checkMatchingTest;
        } 
        else
        {
            return false;
        }
    }

    public function updateMatching($score, $userMatching, $userMatched) 
    {
        $query = $this -> bdd -> prepare("
                                        UPDATE
                                            `matchingscore`
                                        SET
                                            `match_score` = ?
                                        WHERE
                                            `match_userMatching` = ?
                                        AND
                                            `match_userMatched` = ?
        ");

        
        $updateMatchingTest =  $query->execute([$score, $userMatching, $userMatched]);

        if($$updateMatchingTest)
        {
            return  $updateMatchingTest;
        }
        else
        {
            return false;
        } 
    }

    public function insertMatching($userMatching, $userMatched, $score) 
    {

        $query = $this -> bdd -> prepare("
                                            INSERT INTO `matchingscore`(
                                                `match_userMatching`,
                                                `match_userMatched`,
                                                `match_score`                                   
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $insertMatchingUser = $query -> execute([$userMatching, $userMatched, $score]);

        if($insertMatchingUser)
        {
            return true;
        }
        else 
        {
            return false;  
        }

    }

    public function getMatchingScore($userId) 
    {
        $query = $this -> bdd -> prepare("
                                            SELECT
                                                *
                                            FROM
                                                `matchingscore`
                                            WHERE
                                                `match_userMatching` = ?
                                            ORDER BY
                                                `match_score`
                                            DESC LIMIT
                                                5
        ");

        $query -> execute([$userId]);
        $getMatchingTest = $query -> fetchAll();

        if ($getMatchingTest)
        {
            return $getMatchingTest;
        } 
        else
        {
            return false;
        }
    }
}
