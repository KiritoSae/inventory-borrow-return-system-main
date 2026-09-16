
<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/inventory.php");
    exit;
}


// Get form values
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
$itemCondition = trim($_POST['item_condition'] ?? 'Good');
$status = trim($_POST['status'] ?? 'Available');


// Required fields
if (
    $itemCode === '' ||
    $itemName === '' ||
    !$categoryId
) {
    header("Location: ../admin/inventory.php?error=required");
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
    $itemCondition = 'Good';
}


if (!in_array($status, $allowedStatuses, true)) {
    $status = 'Available';
}


try {

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
        header("Location: ../admin/inventory.php?error=category");
        exit;
    }


    // Check duplicate item code
    $duplicateStmt = $pdo->prepare("
        SELECT id
        FROM items
        WHERE item_code = ?
        LIMIT 1
    ");

    $duplicateStmt->execute([
        $itemCode
    ]);

    if ($duplicateStmt->fetch()) {
        header("Location: ../admin/inventory.php?error=duplicate");
        exit;
    }


    // Insert item
    $stmt = $pdo->prepare("
        INSERT INTO items
        (
            item_code,
            item_name,
            category_id,
            serial_number,
            description,
            location,
            item_condition,
            status
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?)
    ");


    $stmt->execute([
        $itemCode,
        $itemName,
        $categoryId,
        $serialNumber !== '' ? $serialNumber : null,
        $description !== '' ? $description : null,
        $location !== '' ? $location : null,
        $itemCondition,
        $status
    ]);


    header(
        "Location: ../admin/inventory.php?success=added"
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
        "Unable to save inventory item: " .
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );
}