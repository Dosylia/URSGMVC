<?php
namespace models;

use config\DataBase;

class Items extends DataBase
{
    private \PDO $bdd;
    
    public function __construct() {
        $this->bdd = $this->getBdd();
    }


    public function getItems()
    {
        $query = $this->bdd->prepare("
                                    SELECT
                                        items_id,
                                        items_name,
                                        items_price,
                                        items_desc,
                                        items_picture,
                                        items_category,
                                        items_discount,
                                        items_isActive,
                                        items_createdAt
                                    FROM
                                        `items`
        ");
    
        $query->execute([]);
        $ItemslistTest = $query->fetchAll();
    
        if ($ItemslistTest) {
            return $ItemslistTest;
        } else {
            return false;
        }
    }

    public function getItemById($itemId)
    {
        $query = $this->bdd->prepare("
                                    SELECT
                                        items_id,
                                        items_name,
                                        items_price,
                                        items_desc,
                                        items_picture,
                                        items_category,
                                        items_discount,
                                        items_isActive,
                                        items_createdAt
                                    FROM
                                        `items`
                                    WHERE
                                        items_id = ?
        ");
    
        $query->execute([$itemId]);
        $ItemslistTest = $query->fetch();
    
        if ($ItemslistTest) {
            return $ItemslistTest;
        } else {
            return false;
        }
    }

    public function buyItem($itemId, $userId)
    {
        $query = $this->bdd->prepare("
                                    INSERT INTO
                                        `user_items`
                                    (
                                        userItems_userId,
                                        userItems_itemId,
                                        userItems_boughtAt
                                    )
                                    VALUES
                                    (
                                        ?,
                                        ?,
                                        NOW()
                                    )
        ");
    
        $query->execute([$userId, $itemId]);
    }

    public function getOwnedItems($userId)
    {
        $query = $this->bdd->prepare("
                                    SELECT
                                        items_id,
                                        items_name,
                                        items_price,
                                        items_desc,
                                        items_picture,
                                        items_category,
                                        items_discount,
                                        items_isActive,
                                        items_createdAt,
                                        userItems_id,
                                        userItems_isUsed
                                    FROM
                                        `items`
                                    JOIN
                                        `user_items`
                                    ON
                                        items_id = userItems_itemId
                                    WHERE
                                        userItems_userId = ?
        ");
    
        $query->execute([$userId]);
        $ItemslistTest = $query->fetchAll();
    
        if ($ItemslistTest) {
            return $ItemslistTest;
        } else {
            return false;
        }
    }

    public function useItems($itemId, $userId) {

        $query = $this->bdd->prepare("
                                        UPDATE
                                            `user_items`
                                        SET
                                            `userItems_isUsed` = 1
                                        WHERE
                                            `userItems_id` = ? AND `userItems_userId` = ?
    ");

    $useItemTest = $query->execute([$itemId, $userId]);

    return $useItemTest ? true : false;

    }

    public function removeItems($itemId, $userId) {

        $query = $this->bdd->prepare("
                                        UPDATE
                                            `user_items`
                                        SET
                                            `userItems_isUsed` = 0
                                        WHERE
                                            `userItems_id` = ? AND `userItems_userId` = ?
    ");

    $useItemTest = $query->execute([$itemId, $userId]);

    return $useItemTest ? true : false;

    }

    public function addItemToUserAsPartner($userId, $itemId)
    {
        $query = $this->bdd->prepare("
                                        INSERT INTO
                                            `user_items`
                                        (
                                            userItems_userId,
                                            userItems_itemId,
                                            userItems_boughtAt,
                                            userItems_isUsed,
                                            userItems_givenAsPartner
                                        )
                                        VALUES
                                        (
                                            ?,
                                            ?,
                                            NOW(),
                                            0,
                                            1
                                        )
        ");
    
        $query->execute([$userId, $itemId]);
    }

    public function removePartnerItems($userId) 
    {
        $query = $this->bdd->prepare("
                                        DELETE FROM
                                            `user_items`
                                        WHERE
                                            `userItems_userId` = ? AND `userItems_givenAsPartner` = 1
        ");
    
        $query->execute([$userId]);
    }

}
