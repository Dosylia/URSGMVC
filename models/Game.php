<?php
namespace models;

use config\DataBase;

class Game extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function getGameUser($date, $game)
    {
        $query = $this->bdd->prepare("
                                    SELECT
                                        game_username,
                                        game_main,
                                        hint_affiliation,
                                        hint_gender,
                                        hint_guess,
                                        game_picture,
                                        game_date,
                                        game_game
                                    FROM
                                        `game`
                                    WHERE
                                        `game_date` = ? AND `game_game` = ?
        ");
    
        $query->execute([$date, $game]);
        $gameUser = $query->fetch();
    
        if ($gameUser) {
            return $gameUser;
        } else {
            return false;
        }
    }

    public function updateTotalCompletedGame($userId)
    {
        $query = $this->bdd->prepare("
                                        UPDATE 
                                            `user`
                                        SET 
                                            `user_totalCompletedGame` = `user_totalCompletedGame` + 1
                                        WHERE 
                                            `user_id` = ?
        ");
    
        $addCompletedGameTest = $query->execute([$userId]);
    
        if ($addCompletedGameTest) {
            return true;
        } else {
            return false;
        }
    }
}
