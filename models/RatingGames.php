<?php
namespace models;

use config\DataBase;

class RatingGames extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function insertFirstRating($raterId, $ratedUserId, $matchId, $rating)
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

    public function getRatingByUserAndFriend($raterId, $ratedUserId, $matchId)
    {
        $query = $this->bdd->prepare("
                                    SELECT rating
                                    FROM user_ratings
                                    WHERE rater_id = ? AND rated_user_id = ? AND match_id = ?
        ");
        
        $query->execute([$raterId, $ratedUserId, $matchId]);
        $getRatingByUserAndFriend = $query->fetch();

        if ($getRatingByUserAndFriend) {
            return intval($getRatingByUserAndFriend['rating']);
        } else {
            return false; // No rating found
        }
    }

    public function getAverageRatingForUser($userId)
    {
        $query = $this->bdd->prepare("
                                        SELECT AVG(rating) AS average
                                        FROM user_ratings
                                        WHERE rated_user_id = ?
        ");
        
        $query->execute([$userId]);
        $getAverageRatingForUser = $query->fetch();

        if ($getAverageRatingForUser) {
            return (int) round($getAverageRatingForUser['average']);
        } else {
            return 0;
        }
        
    }

    public function updateRating($raterId, $ratedUserId, $matchId, $rating)
    {
        $query = $this->bdd->prepare("
                                    UPDATE user_ratings
                                    SET rating = ?
                                    WHERE rater_id = ? AND rated_user_id = ? AND match_id = ?
        ");
        
        $updateRating = $query->execute([$rating, $raterId, $ratedUserId, $matchId]);

        return $updateRating;
    }

    public function getRatingByMatchId($matchId)
    {
        $query = $this->bdd->prepare("
                                    SELECT rater_id, rated_user_id, rating
                                    FROM user_ratings
                                    WHERE match_id = ?
        ");
        
        $query->execute([$matchId]);
        $getRatingByMatchId = $query->fetchAll();

        return $getRatingByMatchId;
    }

}
