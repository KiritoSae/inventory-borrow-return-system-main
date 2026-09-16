<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$qrCode = trim($_GET['qr'] ?? '');

if ($qrCode === '') {

    echo json_encode([
        'success' => false,
        'message' => 'No QR code received.'
    ]);

    exit;
}


$stmt = $pdo->prepare("
    SELECT
        items.id,
        items.item_code,
        items.item_name,
        categories.category_name,
        items.serial_number,
        items.location,
        items.item_condition,
        items.status
    FROM items

    INNER JOIN categories
        ON items.category_id = categories.id

    WHERE items.qr_code = ?

    LIMIT 1
");

$stmt->execute([$qrCode]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$item) {

    echo json_encode([
        'success' => false,
        'message' => 'Inventory item not found.'
    ]);

    exit;
}


echo json_encode([
    'success' => true,
    'item' => $item
]);
