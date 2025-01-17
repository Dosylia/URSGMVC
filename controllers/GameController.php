<?php

namespace controllers;

use models\Game;
use models\GoogleUser;
use models\User;

use traits\SecurityController;

class GameController
{
    use SecurityController;

    private Game $game;
    private User $user;
    private GoogleUser $googleUser;
    private int $userId;
    private int $numberTry;
    private string $gamePlayedByUser;

    public function __construct()
    {
        $this->game = new Game();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();
    }

    public function pageGame(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);

            $current_url = "https://ur-sg.com/game";
            $template = "views/swiping/test_game";
            $page_title = "URSG - Game";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function getGameUser() 
    {
        $response = array('message' => 'Error');
        
        if (isset($_POST['userId']) && isset($_POST['game'])) 
        {
            $gameData = $_POST['game'];
            $date = date("Y-m-d");
            $userId = $_POST['userId'];

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

            $token = $matches[1];

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }


            $user = $this-> user -> getUserById($userId);

            if ($user['user_lastCompletedGame'] == $date)
            {
                $response = array('message' => 'Already played');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            
            $this->setGamePlayedByUser($gameData);
            $gameUser = $this->game->getGameUser($date, $gameData);
            $tryCount = $_POST['tryCount'];
            $hints = [];

    
            if ($gameUser) 
            {

                switch ($tryCount) {
                    case 1:
                        $hints['game_main'] = $gameUser['game_main'];
                        $hints['hint_affiliation'] = $gameUser['hint_affiliation'];
                        break;
                    case 2:
                        $hints['game_main'] = $gameUser['game_main'];
                        $hints['hint_affiliation'] = $gameUser['hint_affiliation'];
                        $hints['hint_gender'] = $gameUser['hint_gender'];
                        break;
                    case 3:
                        $hints['game_main'] = $gameUser['game_main'];
                        $hints['hint_affiliation'] = $gameUser['hint_affiliation'];
                        $hints['hint_gender'] = $gameUser['hint_gender'];
                        $hints['hint_guess'] = $gameUser['hint_guess'];
                        break;
                    default:
                        $hints['game_main'] = $gameUser['game_main'];
                        break;
                }
                
                $response = array(
                    'message' => 'Success',
                    'hints' => $hints,
                    'tryCount' => $tryCount
                );
    
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } 
            else 
            {
                $response = array('message' => 'No game today');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }
    
        } 
        else 
        {
            $response = array('message' => 'Cant access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;  
        }
    }

    public function submitGuess() 
    {
        $response = array('message' => 'Error');
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            $gameData = $data->game;
            $guess = $data->guess;
            $tryCount = $data->tryCount;
            $userId = $data->userId;

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

            $token = $matches[1];

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }
            
            // Fetch game user data
            $date = date("Y-m-d");

            $user = $this-> user -> getUserById($userId);

            if ($user['user_lastCompletedGame'] == $date)
            {
                $response = array('message' => 'Already played');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            
            $gameUser = $this->game->getGameUser($date, $gameData);
    
            if ($gameUser) {
                if (strtolower($guess) === strtolower($gameUser['game_username'])) {
                    // Calculate currency reward
                    $baseReward = 500; // Reward for one-shot guess
                    $penaltyPerTry = 100; // Penalty for each additional attempt
                    $currencyReward = max($baseReward - ($tryCount * $penaltyPerTry), 0); // Ensure reward doesn't go below 0
                    
                    // Add currency to the user's account
                    $this->user->addCurrency($userId, $currencyReward);
                    $markAsPlayed = $this->user->markGameAsPlayed($userId, $date);
    
                    // Correct guess response
                    $response = array(
                        'message' => 'Correct',
                        'gameUser' => $gameUser,
                        'reward' => $currencyReward
                    );
                } else {
                    // Incorrect guess, provide a hint
                    $hint = $this->getHint($gameUser, $tryCount);
                    if ($tryCount >= 4) {
                        $markAsPlayed = $this->user->markGameAsPlayed($userId, $date);
                        $response = array(
                            'message' => 'Game Over',
                            'gameUser' => $gameUser
                        );
                    } else {
                        $response = array(
                            'message' => 'Incorrect',
                            'hint' => $hint
                        );
                    }
                }
    
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
        }
    
        // Error response if required data isn't provided
        $response = array('message' => 'Invalid request');
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    

    public function getHint($gameUser, $tryCount) {
        $hints = array(
            1 => ['affiliation' => $gameUser['hint_affiliation']],
            2 => ['gender' => $gameUser['hint_gender']],
            3 => ['guess' => $gameUser['hint_guess']],
        );
        return isset($hints[$tryCount]) ? $hints[$tryCount] : [];
    }

    public function validateToken($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterToken'])) {
            $storedToken = $storedTokenData['google_masterToken'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }

    public function validateTokenWebsite($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenWebsiteByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterTokenWebsite'])) {
            $storedToken = $storedTokenData['google_masterTokenWebsite'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }


    public function validateInput(string $input): string
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getNumberTry(): int
    {
        return $this->numberTry;
    }

    public function setNumberTry(int $numberTry): void
    {
        $this->numberTry = $numberTry;
    }

    public function getGamePlayedByUser(): string
    {
        return $this->gamePlayedByUser;
    }

    public function setGamePlayedByUser(string $gamePlayedByUser): void
    {
        $this->gamePlayedByUser = $gamePlayedByUser;
    }

}
