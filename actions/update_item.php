<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/inventory.php");
    exit;
}


// Get values
$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

$itemCode = trim($_POST['item_code'] ?? '');
$itemName = trim($_POST['item_name'] ?? '');

$categoryId = filter_input(
    INPUT_POST,
    'category_id',
    FILTER_VALIDATE_INT
);

$serialNumber = trim($_POST['serial_number'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');

$itemCondition = trim(
    $_POST['item_condition'] ?? ''
);

$status = trim(
    $_POST['status'] ?? ''
);


// Validate
if (
    !$id ||
    $itemCode === '' ||
    $itemName === '' ||
    !$categoryId ||
    $itemCondition === '' ||
    $status === ''
) {
    header(
        "Location: ../admin/inventory.php?error=required"
    );
    exit;
}


// Valid database values
$allowedConditions = [
    'Excellent',
    'Good',
    'Fair',
    'Damaged'
];

$allowedStatuses = [
    'Available',
    'Borrowed',
    'Maintenance',
    'Lost',
    'Retired'
];


if (!in_array($itemCondition, $allowedConditions, true)) {
    header(
        "Location: ../admin/inventory.php?error=invalid"
    );
    exit;
}


if (!in_array($status, $allowedStatuses, true)) {
    header(
        "Location: ../admin/inventory.php?error=invalid"
    );
    exit;
}


try {

    // Check item exists
    $itemStmt = $pdo->prepare("
        SELECT id, status
        FROM items
        WHERE id = ?
        LIMIT 1
    ");

    $itemStmt->execute([
        $id
    ]);

    $existingItem = $itemStmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$existingItem) {
        header(
            "Location: ../admin/inventory.php?error=notfound"
        );
        exit;
    }


    /*
     * Don't allow an item with an active borrowing
     * transaction to be manually changed to Available.
     */
    if ($existingItem['status'] === 'Borrowed') {

        $activeTransaction = $pdo->prepare("
            SELECT id
            FROM transactions
            WHERE item_id = ?
              AND status IN ('Borrowed', 'Overdue')
            LIMIT 1
        ");

        $activeTransaction->execute([
            $id
        ]);


        if (
            $activeTransaction->fetch() &&
            $status !== 'Borrowed'
        ) {
            header(
                "Location: ../admin/inventory.php?error=borrowed"
            );
            exit;
        }
    }


    // Check duplicate item code
    $duplicateStmt = $pdo->prepare("
        SELECT id
        FROM items
        WHERE item_code = ?
          AND id <> ?
        LIMIT 1
    ");

    $duplicateStmt->execute([
        $itemCode,
        $id
    ]);


    if ($duplicateStmt->fetch()) {
        header(
            "Location: ../admin/inventory.php?error=duplicate"
        );
        exit;
    }


    // Check category
    $categoryStmt = $pdo->prepare("
        SELECT id
        FROM categories
        WHERE id = ?
        LIMIT 1
    ");

    $categoryStmt->execute([
        $categoryId
    ]);


    if (!$categoryStmt->fetch()) {
        header(
            "Location: ../admin/inventory.php?error=category"
        );
        exit;
    }


    // Update item
    $stmt = $pdo->prepare("
        UPDATE items
        SET
            item_code = ?,
            item_name = ?,
            category_id = ?,
            serial_number = ?,
            description = ?,
            location = ?,
            item_condition = ?,
            status = ?
        WHERE id = ?
    ");


    $stmt->execute([
        $itemCode,
        $itemName,
        $categoryId,
        $serialNumber !== '' ? $serialNumber : null,
        $description !== '' ? $description : null,
        $location !== '' ? $location : null,
        $itemCondition,
        $status,
        $id
    ]);


    header(
        "Location: ../admin/inventory.php?success=updated"
    );

    exit;


} catch (PDOException $e) {

    if ($e->getCode() === '23000') {
        header(
            "Location: ../admin/inventory.php?error=duplicate"
        );
        exit;
    }


    die(
        "Unable to update inventory item: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}