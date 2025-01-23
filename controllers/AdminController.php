<?php

namespace controllers;

use models\Admin;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;

use traits\SecurityController;

class AdminController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private Admin $admin;

    public function __construct()
    {
        $this->friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();
        $this->chatmessage = new ChatMessage();
        $this->admin = new Admin();
    }

    public function adminLandingPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersOnline = $this-> admin -> countOnlineUsers();
            $purchases = $this-> admin -> countPurchases();
            $pendingReports = $this-> admin -> countPendingReports();
            $adminActions = $this-> admin -> getLastAdminActions();

            $current_url = "https://ur-sg.com/admin";
            $template = "views/admin/admin_landing";
            $page_title = "URSG - Admin";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminGamePage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {

            $totalPlayers = $this-> admin -> countTotalPlayers();
            $totalCharacters = $this-> admin -> countTotalCharacters();
            $lastGameDate = $this-> admin -> getLastGameDate();

            if ($lastGameDate) {
                $date = date("Y-m-d"); 
                $lastGameDatePlusOne = date("Y-m-d", strtotime($date . " +1 day")); 
            }

            $current_url = "https://ur-sg.com/adminGame";
            $template = "views/admin/admin_game";
            $page_title = "URSG - Admin Game";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function addCharGame()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        ) {
            if (isset($_POST['game_username'])) {
                $gameUsername = $_POST['game_username'];
                $gameMain = $_POST['game_main'];
                $hintAffiliation = $_POST['hint_affiliation'] ?? null;
                $hintGender = $_POST['hint_gender'] ?? null;
                $hintGuess = $_POST['hint_guess'] ?? null;
                $gameDate = $_POST['game_date'];
                $gameGame = $_POST['game_game'];
    
                // Handle the uploaded picture
                if (isset($_FILES['game_picture']) && $_FILES['game_picture']['error'] === UPLOAD_ERR_OK) {
                    $pictureTmpName = $_FILES['game_picture']['tmp_name'];
                    $pictureFileName = "public/upload/{$gameUsername}.jpg";
    
                    // Resize and save the image
                    if ($this->resizeAndSaveImage($pictureTmpName, $pictureFileName, 50, 50)) {
                        $addChar = $this->admin->addCharacterGame($gameUsername, $gameMain, $hintAffiliation, $hintGender, $hintGuess, $gameDate, $gameGame
                        );
    
                        if ($addChar) {
                            $this->admin->logAdminActionGame($_SESSION['userId'], $gameUsername, "Added Character");
                            header("Location: /adminGame?message=Character added successfully");
                            exit();
                        } else {
                            header("Location: /adminGame?message=Error adding character");
                            exit();
                        }
                    } else {
                        header("Location: /adminGame?message=Error resizing image");
                        exit();
                    }
                } else {
                    header("Location: /adminGame?message=Image upload failed");
                    exit();
                }
            } else {
                header("Location: /adminGame?message=Invalid input data");
                exit();
            }
        } else {
            header("Location: /");
            exit();
        }
    }

    public function resizeAndSaveImage($sourcePath, $destinationPath, $width, $height)
{
    try {
        $imageInfo = getimagesize($sourcePath);
        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $imageType = $imageInfo[2];

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $srcImage = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $srcImage = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $srcImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        $dstImage = imagecreatetruecolor($width, $height);

        imagecopyresampled(
            $dstImage,
            $srcImage,
            0, 0, 0, 0,
            $width, $height,
            $srcWidth, $srcHeight
        );

        $result = imagejpeg($dstImage, $destinationPath, 90);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $result;
    } catch (Exception $e) {
        return false;
    }
}
    

    public function adminUpdateCurrency()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isAdmin()
        )
        {
            if (isset($_POST['currency']) && isset($_POST['user_id']))
            {
                $currency = $this->user->updateCurrency($_POST['user_id'], $_POST['currency']);

                if ($currency)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Updated Currency");
                    header("Location: /adminUsers?message=Money updated");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error updating money");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error updating money");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminCensorBio() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorBio = $this->admin->censorBio($_POST['user_id']);

                if ($censorBio)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Censor Bio");
                    header("Location: /adminUsers?message=Bio censored");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error censoring bio");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error censoring bio");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminCensorPicture() 
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {
            if (isset($_POST['user_id']))
            {
                $censorPicture = $this->admin->censorPicture($_POST['user_id']);

                if ($censorPicture)
                {
                    $this-> admin -> logAdminAction($_SESSION['userId'],  $_POST['user_id'], "Censor Picture");
                    header("Location: /adminUsers?message=Picture censored");
                    exit();
                }
                else
                {
                    header("Location: /adminUsers?message=Error censoring picture");
                    exit();
                }
            }
            else
            {
                header("Location: /adminUsers?message=Error censoring picture");
                exit();
            }
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }

    public function adminUsersPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);
            $users = $this-> user -> getAllUsers();

            $current_url = "https://ur-sg.com/admin_users";
            $template = "views/admin/admin_users";
            $page_title = "URSG - Admin Users";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }
}
