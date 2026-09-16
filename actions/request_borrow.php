<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

requireLogin();

if ($_SESSION['role'] !== 'Staff') {
    header("Location: ../admin/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../borrower/dashboard.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

$itemId = isset($_POST['item_id'])
    ? (int)$_POST['item_id']
    : 0;

$dueDate = trim($_POST['due_date'] ?? '');

$remarks = trim($_POST['remarks'] ?? '');


/*
|--------------------------------------------------------------------------
| Find connected borrower
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM borrowers
    WHERE user_id = ?
      AND status = 'Active'
    LIMIT 1
");

$stmt->execute([$userId]);

$borrower = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$borrower) {
    die("No active borrower account is connected to this Staff account.");
}

$borrowerId = (int)$borrower['id'];


/*
|--------------------------------------------------------------------------
| Validate Item
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, item_name, status
    FROM items
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$itemId]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Item not found.");
}

if ($item['status'] !== 'Available') {
    header("Location: ../borrower/dashboard.php?error=item_unavailable");
    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Due Date
|--------------------------------------------------------------------------
*/

if ($dueDate === '') {
    header("Location: ../borrower/dashboard.php?error=missing_date");
    exit;
}

$today = date('Y-m-d');

if ($dueDate < $today) {
    header("Location: ../borrower/dashboard.php?error=invalid_date");
    exit;
}


/*
|--------------------------------------------------------------------------
| Prevent duplicate pending request
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM borrow_requests
    WHERE item_id = ?
      AND borrower_id = ?
      AND status = 'Pending'
    LIMIT 1
");

$stmt->execute([
    $itemId,
    $borrowerId
]);

if ($stmt->fetch()) {

    header(
        "Location: ../borrower/dashboard.php?error=request_exists"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Create Request
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    INSERT INTO borrow_requests
    (
        item_id,
        borrower_id,
        requested_by,
        due_date,
        remarks,
        status
    )
    VALUES (?, ?, ?, ?, ?, 'Pending')
");

$stmt->execute([
    $itemId,
    $borrowerId,
    $userId,
    $dueDate,
    $remarks
]);


header(
    "Location: ../borrower/dashboard.php?success=requested"
);

exit;