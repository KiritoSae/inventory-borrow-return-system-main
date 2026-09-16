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


// Validate required fields
if (!$itemId || !$borrowerId || $dueDate === '') {

    die("Item, borrower, and due date are required.");

}


// Make sure the due date is valid
$dueDateTime = DateTime::createFromFormat(
    'Y-m-d',
    $dueDate
);

if (
    !$dueDateTime ||
    $dueDateTime->format('Y-m-d') !== $dueDate
) {

    die("Invalid due date.");

}


try {

    /*
     * Start database transaction.
     *
     * This makes sure the transaction record
     * and inventory status are updated together.
     */
    $pdo->beginTransaction();


    /*
     * 1. Check the inventory item.
     *
     * FOR UPDATE prevents another transaction
     * from borrowing the same item at the same time.
     */
    $itemStmt = $pdo->prepare("
        SELECT
            id,
            item_code,
            item_name,
            item_condition,
            status
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


    /*
     * Item must still be available.
     */
    if ($item['status'] !== 'Available') {

        throw new Exception(
            "This item is no longer available."
        );

    }


    /*
     * 2. Check borrower.
     */
    $borrowerStmt = $pdo->prepare("
        SELECT
            id,
            borrower_code,
            full_name,
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


    /*
     * Borrower must be active.
     */
    if ($borrower['status'] !== 'Active') {

        throw new Exception(
            "This borrower is inactive."
        );

    }


    /*
     * 3. Check logged-in user.
     */
    if (!isset($_SESSION['user_id'])) {

        throw new Exception(
            "Your session has expired. Please log in again."
        );

    }

    $processedBy = (int) $_SESSION['user_id'];


    /*
     * 4. Generate a unique transaction code.
     *
     * Example:
     * TRX-20260916-ABC123
     */
    do {

        $transactionCode =
            'TRX-' .
            date('Ymd') .
            '-' .
            strtoupper(
                substr(
                    bin2hex(random_bytes(4)),
                    0,
                    6
                )
            );


        $checkTransaction = $pdo->prepare("
            SELECT id
            FROM transactions
            WHERE transaction_code = ?
            LIMIT 1
        ");

        $checkTransaction->execute([
            $transactionCode
        ]);

        $transactionExists =
            $checkTransaction->fetchColumn();

    } while ($transactionExists);


    /*
     * 5. Insert borrowing transaction.
     *
     * IMPORTANT:
     * These column names match schema.sql.
     */
    $transactionStmt = $pdo->prepare("
        INSERT INTO transactions
        (
            transaction_code,
            item_id,
            borrower_id,
            processed_by,
            borrowed_date,
            due_date,
            condition_before,
            remarks,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            NOW(),
            ?,
            ?,
            ?,
            'Borrowed'
        )
    ");


    $transactionStmt->execute([

        $transactionCode,

        $itemId,

        $borrowerId,

        $processedBy,

        $dueDate . ' 23:59:59',

        $item['item_condition'],

        $remarks !== ''
            ? $remarks
            : null

    ]);


    /*
     * 6. Change inventory status.
     */
    $updateItem = $pdo->prepare("
        UPDATE items
        SET status = 'Borrowed'
        WHERE id = ?
    ");

    $updateItem->execute([
        $itemId
    ]);


    /*
     * 7. Make sure the inventory was actually updated.
     */
    if ($updateItem->rowCount() !== 1) {

        throw new Exception(
            "Unable to update inventory status."
        );

    }


    /*
     * 8. Everything worked.
     */
    $pdo->commit();


    /*
     * Return to inventory page.
     */
    header(
        "Location: ../admin/inventory.php?success=borrowed"
    );

    exit;


} catch (Exception $e) {

    /*
     * Undo database changes if something failed.
     */
    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    die(
        "Borrowing failed: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );

}