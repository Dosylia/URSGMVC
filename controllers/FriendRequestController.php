<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use models\Block;
use traits\SecurityController;

class FriendRequestController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private Block $block;
    private ?int $frId = null;
    private ?int $userId = null;
    private ?int $senderId = null;
    private ?int $receiverId = null;
    private ?int $friendId = null;

    public function __construct()
    {
        $this->friendrequest = new FriendRequest();
        $this->user = new User();
        $this->block = new Block();
    }

    public function pageFriendlist(): void
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf()) {
            // Get important datas
            $user = $this->user->getUserByUsername($_SESSION['username']);
            $allUsers = $this->user->getAllUsers();
            $getFriendlist = $this->friendrequest->getFriendlist($_SESSION['userId']);
            $getBlocklist = $this->block->getBlocklist($_SESSION['userId']);
            $current_url = "https://ur-sg.com/friendlistPage";
            $template = "views/swiping/swiping_friendlist";
            $page_title = "URSG - Friendlist";
            require "views/layoutSwiping.phtml";
        } else {
            header("Location: /");
            exit();
        }
    }

    public function swipeStatus(): void
    {
        if (isset($_POST['swipe_yes'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';

            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);

            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->updateFriendRequest($this->getSenderId(), $this->getReceiverId());

                echo json_encode(['success' => true, 'error' => 'Swipped yes']);
            } else {
                $swipeStatusYes = $this->friendrequest->swipeStatus($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                echo json_encode(['success' => true, 'error' => 'Swipped yes']);
            }

        } elseif (isset($_POST['swipe_no'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'rejected';

            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);

            $swipeStatusNo = $this->friendrequest->swipeStatus($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);

            echo json_encode(['success' => true, 'error' => 'Swipped No']);

        } else {
            echo json_encode(['success' => false, 'error' => 'Proper data were not sent']);
        }
    }

    public function acceptFriendRequest(): void
    {
        $frId = $this->validateInput($_GET["fr_id"]);
        $friendId = $this->validateInput($_GET["friend_id"]);
        $this->setFrId((int)$frId);
        $this->setFriendId((int)$friendId);

        $updateStatus = $this->friendrequest->acceptFriendRequest($this->getFrId());

        if ($updateStatus) {
            header("Location: /persoChat?friend_id=" . $this->getFriendId() . "&mark_as_read=true");
            exit();
        } else {
            header("location:/userProfile?message=Could not accept it");
            exit();
        }
    }

    public function rejectFriendRequest(): void
    {
        $frId = $this->validateInput($_GET["fr_id"]);
        $this->setFrId((int)$frId);

        $updateStatus = $this->friendrequest->rejectFriendRequest($this->getFrId());

        if ($updateStatus) {
            header("location:/userProfile?message=Friend request rejected");
            exit();
        } else {
            header("location:/userProfile?message=Could not accept it");
            exit();
        }
    }

    public function getFriendRequest(): void
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

            $pendingCount = $this->friendrequest->countFriendRequest($this->getUserId());

            if ($pendingCount !== false) {
                $data = [
                    'success' => true,
                    'pendingCount' => [
                        'pendingFriendRequest' => $pendingCount,
                    ]
                ];

                // Send JSON response
                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No friend requests found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function deleteFriendRequestAfterWeek()
    {
        try {
            $deleteFriendRequest = $this->friendrequest->deleteFriendRequestAfterWeek();

            if ($deleteFriendRequest) {
                echo "Old friend requests deleted successfully.";
            } else {
                throw new \Exception("Failed to delete old friend requests.");
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo "An error occurred: " . $e->getMessage();
        }
    }

    public function validateInput(string $input): string
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function getFrId(): ?int
    {
        return $this->frId;
    }

    public function setFrId(int $frId): void
    {
        $this->frId = $frId;
    }

    public function getSenderId(): ?int
    {
        return $this->senderId;
    }

    public function setSenderId(int $senderId): void
    {
        $this->senderId = $senderId;
    }

    public function getReceiverId(): ?int
    {
        return $this->receiverId;
    }

    public function setReceiverId(int $receiverId): void
    {
        $this->receiverId = $receiverId;
    }

    public function getFriendId(): ?int
    {
        return $this->friendId;
    }

    public function setFriendId(int $friendId): void
    {
        $this->friendId = $friendId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
}
