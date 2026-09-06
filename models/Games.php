<?php

declare(strict_types=1);

namespace models;

use config\DataBase;

class Games extends DataBase
{
    private \PDO $bdd;

    public function __construct()
    {
        $this->bdd = $this->getBdd();
    }

    public function getIdBySlug(string $gameSlug): ?int
    {
        $query = $this->bdd->prepare("SELECT 
                                        game_id 
                                    FROM 
                                        games 
                                    WHERE 
                                        game_slug = ?");
        $query->execute([$gameSlug]);
        $result = $query->fetch();

        return $result ? (int) $result['game_id'] : null;
    }
}
