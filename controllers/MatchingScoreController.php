<?php

namespace controllers;

use models\MatchingScore;


use traits\SecurityController;

class MatchingScoreController
{
    use SecurityController;

    private MatchingScore $matchingscore;
    private $userMatching;
    private $userMatched;
    private $score;


    
    public function __construct()
    {
        $this -> matchingscore = new MatchingScore();
    }

    public function getAlgoData()
    {
        if (isset($_POST['param']))
        {
            $datas = json_decode($_POST['param']);

            foreach ($datas as $data)
            {
                $userMatching = $data->user_id;
                $this->setUserMatching($userMatching);
                $userMatched = $data->user_matching;
                $this->setUserMatched($userMatched);
                $score = $data->score;
                $this->setScore($score);

                $checkMatching = $this->matchingscore->checkMatching($this->getUserMatching(), $this->getUserMatched());

                if($checkMatching)
                {
                    if($checkMatching['match_score'] !== $this->getScore())
                    {
                        $updateMatching = $this->matchingscore->updateMatching($this->getScore(), $this->getUserMatching(), $this->getUserMatched());
                    }

                }
                else
                {
                    $insertMatching = $this->matchingscore->insertMatching($this->getUserMatching(), $this->getUserMatched(), $this->getScore());
                }


            }
        }
    }

    public function getUserMatching()
    {
        return $this->userMatching;
    }

    public function setUserMatching($userMatching)
    {
        $this->userMatching = $userMatching;
    }

    public function getUserMatched()
    {
        return $this->userMatched;
    }

    public function setUserMatched($userMatched)
    {
        $this->userMatched = $userMatched;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setScore($score)
    {
        $this->score = $score;
    }

}
