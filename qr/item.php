<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

requireLogin();

header("Content-Type: application/json");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid item ID."
    ]);

    exit;
}

$itemId = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT
        items.id,
        items.item_code,
        items.item_name,
        items.serial_number,
        items.location,
        items.item_condition,
        items.status,
        categories.category_name
    FROM items
    LEFT JOIN categories
        ON items.category_id = categories.id
    WHERE items.id = ?
    LIMIT 1
");

$stmt->execute([$itemId]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {

    echo json_encode([
        "success" => false,
        "message" => "Item not found."
    ]);

    exit;
}

echo json_encode([
    "success" => true,
    "item" => $item
]);