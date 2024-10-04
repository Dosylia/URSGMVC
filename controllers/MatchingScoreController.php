<?php

namespace controllers;

use models\MatchingScore;
use traits\SecurityController;

class MatchingScoreController
{
    use SecurityController;

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
                $this->setUserMatching($userMatching);
                $userMatched = (int)$data->user_matching;
                $this->setUserMatched($userMatched);
                $score = (int)$data->score;
                $this->setScore($score);
            
                $checkMatching = $this->matchingscore->checkMatching($this->getUserMatching(), $this->getUserMatched());
            
                if ($checkMatching) {
                    if ($checkMatching['match_score'] !== $this->getScore()) {
                        $updateMatching = $this->matchingscore->updateMatching($this->getScore(), $this->getUserMatching(), $this->getUserMatched());
                        $messages[] = ['message' => 'Success', 'Action' => 'Updated', 'score' => $this->getScore(), 'userMatching' => $this->getUserMatching(), 'userMatched' => $this->getUserMatched()];
                    } else {
                        $messages[] = ['message' => 'Success', 'Action' => 'No change', 'score' => $this->getScore(), 'userMatching' => $this->getUserMatching(), 'userMatched' => $this->getUserMatched()];
                    }
                } else {
                    $insertMatching = $this->matchingscore->insertMatching($this->getUserMatching(), $this->getUserMatched(), $this->getScore());
            
                    if ($insertMatching) {
                        $messages[] = ['message' => 'Success', 'Action' => 'Inserted'];
                    } else {
                        $messages[] = ['message' => 'Error', 'Action' => 'Insertion failed'];
                    }
                }
            }
            
            echo json_encode($messages);
            exit();

        } else {
            echo json_encode(['message' => 'Invalid request']);
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
