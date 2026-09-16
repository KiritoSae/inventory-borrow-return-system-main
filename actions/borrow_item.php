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


$itemId = filter_input(
    INPUT_POST,
    'item_id',
    FILTER_VALIDATE_INT
);


$borrowerId = filter_input(
    INPUT_POST,
    'borrower_id',
    FILTER_VALIDATE_INT
);


$dueDate = trim(
    $_POST['due_date'] ?? ''
);


$remarks = trim(
    $_POST['remarks'] ?? ''
);


if (
    !$itemId ||
    !$borrowerId ||
    $dueDate === ''
) {

    die(
        "Item, borrower, and due date are required."
    );

}


try {

    $pdo->beginTransaction();


    /*
     * Check the item.
     */

    $itemStmt = $pdo->prepare("
        SELECT
            id,
            status,
            item_condition
        FROM items
        WHERE id = ?
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


    if ($item['status'] !== 'Available') {

        throw new Exception(
            "This item is no longer available."
        );

    }


    /*
     * Check borrower.
     */

    $borrowerStmt = $pdo->prepare("
        SELECT
            id,
            status
        FROM borrowers
        WHERE id = ?
        LIMIT 1
    ");

    $borrowerStmt->execute([
        $borrowerId
    ]);

    $borrower = $borrowerStmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$borrower) {

        throw new Exception(
            "Borrower not found."
        );

    }


    if ($borrower['status'] !== 'Active') {

        throw new Exception(
            "This borrower is inactive."
        );

    }


    /*
     * Create transaction.
     */

    $transactionStmt = $pdo->prepare("
        INSERT INTO transactions
        (
            item_id,
            borrower_id,
            borrow_date,
            due_date,
            borrow_condition,
            remarks,
            status,
            processed_by
        )
        VALUES
        (
            ?,
            ?,
            NOW(),
            ?,
            ?,
            ?,
            'Borrowed',
            ?
        )
    ");


    $transactionStmt->execute([

        $itemId,

        $borrowerId,

        $dueDate,

        $item['item_condition'],

        $remarks ?: null,

        $_SESSION['user_id']

    ]);


    /*
     * Change inventory status.
     */

    $updateStmt = $pdo->prepare("
        UPDATE items

        SET status = 'Borrowed'

        WHERE id = ?
    ");


    $updateStmt->execute([
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
        "Borrowing failed: " .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}
