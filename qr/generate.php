<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid item ID.");
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
    die("Item not found.");
}

/*
|--------------------------------------------------------------------------
| QR DATA
|--------------------------------------------------------------------------
| The QR contains a simple URL pointing to scan.php.
| When scanned, the system can identify the item.
|--------------------------------------------------------------------------
*/

$baseUrl =
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        ? 'https'
        : 'http')
    . '://'
    . $_SERVER['HTTP_HOST'];

$projectPath = dirname(dirname($_SERVER['SCRIPT_NAME']));

$scanUrl =
    rtrim($baseUrl . $projectPath, '/')
    . "/qr/scan.php?id="
    . $itemId;

$qrUrl =
    "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data="
    . urlencode($scanUrl);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Generate QR - Inventory System</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .qr-container {
            max-width: 650px;
            margin: 30px auto;
            padding: 20px;
        }

        .qr-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.10);
        }

        .qr-card h1 {
            color: #198754;
            margin-bottom: 10px;
        }

        .qr-image {
            width: 300px;
            max-width: 100%;
            margin: 20px auto;
            display: block;
        }

        .item-info {
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .item-info p {
            margin: 8px 0;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #198754;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        @media print {

            body * {
                visibility: hidden;
            }

            .qr-card,
            .qr-card * {
                visibility: visible;
            }

            .qr-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
            }

            .button-group {
                display: none;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/header.php"; ?>

<div class="qr-container">

    <div class="qr-card">

        <h1>Item QR Code</h1>

        <p>
            Scan this QR code to identify the inventory item.
        </p>

        <img
            src="<?= htmlspecialchars($qrUrl) ?>"
            alt="QR Code"
            class="qr-image"
        >

        <div class="item-info">

            <p>
                <strong>Item Code:</strong>
                <?= htmlspecialchars($item['item_code']) ?>
            </p>

            <p>
                <strong>Item Name:</strong>
                <?= htmlspecialchars($item['item_name']) ?>
            </p>

            <p>
                <strong>Category:</strong>
                <?= htmlspecialchars($item['category_name'] ?? 'N/A') ?>
            </p>

            <p>
                <strong>Serial Number:</strong>
                <?= htmlspecialchars($item['serial_number'] ?? 'N/A') ?>
            </p>

            <p>
                <strong>Location:</strong>
                <?= htmlspecialchars($item['location'] ?? 'N/A') ?>
            </p>

            <p>
                <strong>Status:</strong>
                <?= htmlspecialchars($item['status']) ?>
            </p>

        </div>

        <div class="button-group">

            <button
                onclick="window.print()"
                class="btn btn-primary"
            >
                Print QR
            </button>

            <a
                href="../admin/inventory.php"
                class="btn btn-secondary"
            >
                Back to Inventory
            </a>

        </div>

    </div>

</div>

<?php include "../includes/footer.php"; ?>

</body>

</html>