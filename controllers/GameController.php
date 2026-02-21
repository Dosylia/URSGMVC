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

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }
    
    public function getGameUser() 
    {
        if (isset($_POST['userId']) && isset($_POST['game'])) 
        {
            $gameData = $_POST['game'];
            $date = date("Y-m-d");
            $userId = $_POST['userId'];

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }


            $user = $this-> user -> getUserById($userId);

            if ($user['user_game'] !== $gameData)
            {
                echo json_encode(['message' => 'Game not available, switch to League of Legends.']);
                return;
            }

            if ($user['user_lastCompletedGame'] == $date)
            {
                echo json_encode(['message' => 'Already played']);
                return;
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
                
                echo json_encode([
                    'message' => 'Success',
                    'hints' => $hints,
                    'tryCount' => $tryCount
                ]);
                return;  
            } 
            else 
            {
                echo json_encode(['message' => 'No game today']);
                return;  
            }
    
        } 
        else 
        {
            echo json_encode(['message' => 'Cant access this']);
            return;  
        }
    }

    public function submitGuess() 
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            $gameData = $data->game;
            $guess = $data->guess;
            $tryCount = $data->tryCount;
            $userId = $data->userId;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }
            
            // Fetch game user data
            $date = date("Y-m-d");

            $user = $this-> user -> getUserById($userId);

            if ($user['user_lastCompletedGame'] == $date)
            {
                echo json_encode(['message' => 'Already played']);
                return;
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
                        echo json_encode([
                            'message' => 'Correct',
                            'gameUser' => $gameUser,
                            'reward' => $currencyReward
                        ]);
                        return;
                    } else {
                        echo json_encode(['message' => 'Contact an administrator']);
                        return;
                    }
                } else {
                    if ($tryCount >= 4) {
                        $markAsPlayed = $this->user->markGameAsPlayed($userId, $date);
                        $this->game->updateTotalCompletedGame($userId);
                        echo json_encode([
                            'message' => 'Game Over',
                            'gameUser' => $gameUser
                        ]);
                        return;
                    } else {
                        $hint = $this->getHint($gameUser, $tryCount);
                        if ($close) {
                            echo json_encode([
                                'message' => 'Close',
                                'hint' => $hint
                            ]);
                            return;
                        } else {
                            echo json_encode([
                                'message' => 'Incorrect',
                                'hint' => $hint
                            ]);
                            return;
                        }
                    }
                }
            }
        }
    
        echo json_encode(['message' => 'Invalid request']);
        return;
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
