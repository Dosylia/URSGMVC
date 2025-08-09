<?php
namespace models;

use config\DataBase;

class RatingGames extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function insertOrUpdateRating($raterId, $ratedUserId, $matchId, $rating)
    {
        $query = $this -> bdd -> prepare("
                                            INSERT INTO `user_ratings`(
                                                rater_id,
                                                rated_user_id,
                                                match_id,
                                                rating
                                            )
                                            VALUES (
                                                ?,
                                                ?,
                                                ?,
                                                ?
                                            )
                                        ");

        $insertOrUpdateRating = $query -> execute([$raterId, $ratedUserId, $matchId, $rating]);


        if($insertOrUpdateRating)
        {
            return true;
        }
        else 
        {
            return false;  
        }
    }

    public function getAverageRatingForUser($friendId)
    {
        $query = $this->bdd->prepare("
                                    SELECT AVG(rating) AS average, COUNT(*) AS count
                                    FROM user_ratings
                                    WHERE rated_user_id = ?
        ");
        
        $query->execute([$friendId]);
        $result = $query->fetch();
        
        return [
            'average' => round(floatval($result['average']), 2),
            'count' => intval($result['count'])
        ];
    }

}
