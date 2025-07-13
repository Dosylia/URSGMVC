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
            $picture = "ursg-preview-small";
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

            if ($user['user_game'] !== $gameData)
            {
                $response = array('message' => 'Game not available, switch to League of Legends.');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }

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
                $correct = false;
                $close = false;

                $normalizedCorrect = $this->normalizeGuess($gameUser['game_username']);
                $normalizedGuess = $this->normalizeGuess($guess);

                if ($normalizedGuess === $normalizedCorrect) {
                    $correct = true;
                } else {
                    $words = $this->splitIntoWords($gameUser['game_username']);

                    foreach ($words as $word) {
                        // Accept as correct only if:
                        // - exact match
                        // - word is at least 5 chars
                        // - guess and correct name are close in length (e.g., same word chunk)
                        if (
                            $normalizedGuess === $word &&
                            mb_strlen($word) >= 5 &&
                            abs(mb_strlen($word) - mb_strlen($normalizedCorrect)) <= 3
                        ) {
                            $correct = true;
                            break;
                        }
                    }

                    // 3. Check similarity ratio (Levenshtein)
                    if (!$correct) {
                        if (mb_strlen($normalizedGuess) >= 5 && mb_strlen($normalizedCorrect) >= 5) {
                            $distance = levenshtein($normalizedGuess, $normalizedCorrect);
                            $minLength = min(mb_strlen($normalizedGuess), mb_strlen($normalizedCorrect));
                            $similarityRatio = $minLength > 0 ? $distance / $minLength : 1;

                            if ($similarityRatio <= 0.3) {
                                $close = true;
                            }
                        }
                    }
                }

                if ($correct) {
                    // Calculate currency reward
                    $baseReward = 500; // Reward for one-shot guess
                    $penaltyPerTry = 100; // Penalty for each additional attempt
                    $currencyReward = max($baseReward - ($tryCount * $penaltyPerTry), 0); // Ensure reward doesn't go below 0
                    
                    // Add currency to the user's account
                    $this->user->addCurrency($userId, $currencyReward);
                    $updatedStatus = $this->game->updateTotalCompletedGame($userId);
                    $markAsPlayed = $this->user->markGameAsPlayed($userId, $date);

                    if ($markAsPlayed && $updatedStatus) {
                        $response = array(
                            'message' => 'Correct',
                            'gameUser' => $gameUser,
                            'reward' => $currencyReward
                        );
                    } else {
                        $response = array('message' => 'Contact an administrator');
                    }
                } else {
                    if ($tryCount >= 4) {
                        $markAsPlayed = $this->user->markGameAsPlayed($userId, $date);
                        $this->game->updateTotalCompletedGame($userId);
                        $response = array(
                            'message' => 'Game Over',
                            'gameUser' => $gameUser
                        );
                    } else {
                        $hint = $this->getHint($gameUser, $tryCount);
                        if ($close) {
                            $response = [
                                'message' => 'Close',
                                'hint' => $hint
                            ];
                        } else {
                            $response = [
                                'message' => 'Incorrect',
                                'hint' => $hint
                            ];
                        }
                    }
                }
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
        }
    
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

    public function normalizeGuess($str) 
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[^\p{L}\p{N}]/u', '', $str);
        return $str;
    }

    public function splitIntoWords($str) 
    {
        // Split by non-alphanumeric characters
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $str, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        
        foreach ($parts as $part) {
            // Split camelCase words
            $subParts = preg_split('/(?<=\p{Ll})(?=\p{Lu})|(?<=\p{N})(?=\p{Lu})|(?<=\p{L})(?=\p{N})/u', $part);
            $result = array_merge($result, $subParts);
        }
        
        // Normalize and filter short words
        $result = array_map([$this, 'normalizeGuess'], $result);
        $result = array_filter($result, function($word) { 
            return mb_strlen($word) >= 3; 
        });
        
        return array_values($result);
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
