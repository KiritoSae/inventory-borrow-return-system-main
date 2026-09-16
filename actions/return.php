<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: ../admin/inventory.php"
    );

    exit;

}


$transactionId = filter_input(
    INPUT_POST,
    'transaction_id',
    FILTER_VALIDATE_INT
);


$itemId = filter_input(
    INPUT_POST,
    'item_id',
    FILTER_VALIDATE_INT
);


$returnCondition = trim(
    $_POST['return_condition'] ?? ''
);


$returnRemarks = trim(
    $_POST['return_remarks'] ?? ''
);


if (
    !$transactionId ||
    !$itemId ||
    $returnCondition === ''
) {

    die(
        "Transaction, item, and return condition are required."
    );

}


try {

    $pdo->beginTransaction();


    /*
     * Find the active transaction.
     */

    $stmt = $pdo->prepare("
        SELECT
            id,
            item_id,
            status
        FROM transactions

        WHERE id = ?

        AND item_id = ?

        AND status = 'Borrowed'

        LIMIT 1

        FOR UPDATE
    ");


    $stmt->execute([
        $transactionId,
        $itemId
    ]);


    $transaction = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$transaction) {

        throw new Exception(
            "Active borrowing transaction not found."
        );

    }


    /*
     * Update the transaction.
     */

    $updateTransaction = $pdo->prepare("
        UPDATE transactions

        SET
            return_date = NOW(),
            return_condition = ?,
            return_remarks = ?,
            status = 'Returned',
            returned_by = ?

        WHERE id = ?
    ");


    $updateTransaction->execute([

        $returnCondition,

        $returnRemarks ?: null,

        $_SESSION['user_id'],

        $transactionId

    ]);


    /*
     * Make the inventory item available again.
     */

    $updateItem = $pdo->prepare("
        UPDATE items

        SET
            status = 'Available',
            item_condition = ?

        WHERE id = ?
    ");


    $newItemCondition = $returnCondition;


    $updateItem->execute([

        $newItemCondition,

        $itemId

    ]);


    $pdo->commit();


    header(
        "Location: ../admin/inventory.php"
    );

    exit;


} catch (Exception $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    die(
        "Return failed: " .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}
