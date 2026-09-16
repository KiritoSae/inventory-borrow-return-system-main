<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';


$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$id) {
    header("Location: ../admin/inventory.php?error=invalid");
    exit;
}


try {

    /*
     * Check whether the item exists.
     */
    $itemStmt = $pdo->prepare("
        SELECT
            id,
            item_code,
            status
        FROM items
        WHERE id = ?
        LIMIT 1
    ");

    $itemStmt->execute([
        $id
    ]);

    $item = $itemStmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$item) {
        header(
            "Location: ../admin/inventory.php?error=notfound"
        );
        exit;
    }


    /*
     * Don't delete an item that is currently borrowed.
     */
    if ($item['status'] === 'Borrowed') {
        header(
            "Location: ../admin/inventory.php?error=borrowed"
        );
        exit;
    }


    /*
     * Check for existing transactions.
     *
     * We keep historical transaction records,
     * so deleting an item that already has transactions
     * would break transaction history.
     */
    $transactionStmt = $pdo->prepare("
        SELECT id
        FROM transactions
        WHERE item_id = ?
        LIMIT 1
    ");

    $transactionStmt->execute([
        $id
    ]);


    if ($transactionStmt->fetch()) {
        header(
            "Location: ../admin/inventory.php?error=has_transactions"
        );
        exit;
    }


    /*
     * Delete the item.
     */
    $deleteStmt = $pdo->prepare("
        DELETE FROM items
        WHERE id = ?
    ");

    $deleteStmt->execute([
        $id
    ]);


    header(
        "Location: ../admin/inventory.php?success=deleted"
    );

    exit;


} catch (PDOException $e) {

    /*
     * Foreign-key or database protection.
     */
    header(
        "Location: ../admin/inventory.php?error=delete"
    );

    exit;
}