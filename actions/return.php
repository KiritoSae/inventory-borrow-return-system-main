<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';


// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ../admin/inventory.php");
    exit;

}


// Get submitted values
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


// Validate required fields
if (
    !$transactionId ||
    !$itemId ||
    $returnCondition === ''
) {

    die(
        "Transaction, item, and return condition are required."
    );

}


// Make sure condition is valid
$allowedConditions = [
    'Excellent',
    'Good',
    'Fair',
    'Damaged'
];

if (!in_array($returnCondition, $allowedConditions, true)) {

    die("Invalid return condition.");

}


try {

    /*
     * Start transaction.
     */
    $pdo->beginTransaction();


    /*
     * 1. Find the active borrowing transaction.
     *
     * FOR UPDATE prevents another return
     * from processing the same transaction.
     */
    $stmt = $pdo->prepare("
        SELECT
            id,
            item_id,
            borrower_id,
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
     * 2. Make sure the item is actually borrowed.
     */
    $itemStmt = $pdo->prepare("
        SELECT
            id,
            status
        FROM items
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $itemStmt->execute([
        $itemId
    ]);

    $item = $itemStmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$item) {

        throw new Exception(
            "Inventory item not found."
        );

    }


    if ($item['status'] !== 'Borrowed') {

        throw new Exception(
            "This item is not currently marked as borrowed."
        );

    }


    /*
     * 3. Update the transaction.
     *
     * These column names match schema.sql.
     */
    $updateTransaction = $pdo->prepare("
        UPDATE transactions
        SET
            returned_date = NOW(),
            condition_after = ?,
            remarks = ?,
            status = 'Returned'
        WHERE id = ?
    ");

    $updateTransaction->execute([

        $returnCondition,

        $returnRemarks !== ''
            ? $returnRemarks
            : null,

        $transactionId

    ]);


    /*
     * 4. Change the inventory item back to Available.
     *
     * The item's current condition is also updated
     * to the condition reported upon return.
     */
    $updateItem = $pdo->prepare("
        UPDATE items
        SET
            status = 'Available',
            item_condition = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $updateItem->execute([

        $returnCondition,

        $itemId

    ]);


    /*
     * 5. Make sure the item was updated.
     */
    if ($updateItem->rowCount() !== 1) {

        throw new Exception(
            "Unable to update inventory item."
        );

    }


    /*
     * 6. Everything succeeded.
     */
    $pdo->commit();


    /*
     * Return to inventory page.
     */
    header(
        "Location: ../admin/inventory.php?success=returned"
    );

    exit;


} catch (Exception $e) {

    /*
     * Undo all changes if something failed.
     */
    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    die(
        "Return failed: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );

}