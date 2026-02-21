<?php

namespace controllers;

use models\MatchingScore;
use traits\SecurityController;
use traits\Translatable;

class MatchingScoreController
{
    use SecurityController;
    use Translatable;

    private MatchingScore $matchingscore;
    private ?int $userMatching = null;
    private ?int $userMatched = null;
    private ?int $score = null;

    public function __construct()
    {
        $this->matchingscore = new MatchingScore();
    }

    public function getAlgoData()
    {
        if (isset($_POST['param'])) {
            $datas = json_decode($_POST['param']);
            $messages = [];  
    
            foreach ($datas as $data) {
                $userMatching = (int)$data->user_id;
                $userMatched = (int)$data->user_matching;
                $score = (int)$data->score;
    
                // Upsert Matching
                $result = $this->matchingscore->upsertMatching($userMatching, $userMatched, $score);
    
                if ($result) {
                    $messages[] = [
                        'message' => $this->_('messages.success'), 
                        'Action' => 'Inserted/Updated', 
                        'score' => $score, 
                        'userMatching' => $userMatching, 
                        'userMatched' => $userMatched
                    ];
                } else {
                    $messages[] = [
                        'message' => $this->_('messages.error'), 
                        'Action' => 'Insertion/Update failed', 
                        'userMatching' => $userMatching, 
                        'userMatched' => $userMatched
                    ];
                }
            }
    
            echo json_encode($messages);
            return;
        } else {
            echo json_encode(['message' => $this->_('messages.invalid_request')]);
        }
    }
    

    public function getUserMatching(): ?int
    {
        return $this->userMatching;
    }

    public function setUserMatching(?int $userMatching): void
    {
        $this->userMatching = $userMatching;
    }

    public function getUserMatched(): ?int
    {
        return $this->userMatched;
    }

    public function setUserMatched(?int $userMatched): void
    {
        $this->userMatched = $userMatched;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): void
    {
        $this->score = $score;
    }
}
