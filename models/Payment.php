<?php
namespace models;

use config\DataBase;

class Payment extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }

    public function createTransaction($userId, $sessionId, $amount, $reward, $type)
    {
        $query = $this->bdd->prepare("
            INSERT INTO payment_transactions (
                user_id,
                stripe_session_id,
                amount,
                soulhard_amount,
                type,
                status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");

        return $query->execute([$userId, $sessionId, $amount, $reward, $type]);
    }

    public function getTransactionBySessionId($sessionId)
    {
        $query = $this->bdd->prepare("
            SELECT user_id, soulhard_amount, type, amount, status
            FROM payment_transactions
            WHERE stripe_session_id = ?
        ");
        $query->execute([$sessionId]);
        return $query->fetch();
    }

    public function updateTransactionStatus($sessionId, $status)
    {
        $query = $this->bdd->prepare("
            UPDATE payment_transactions
            SET status = ?, updated_at = NOW()
            WHERE stripe_session_id = ?
        ");
        return $query->execute([$status, $sessionId]);
    }
}
