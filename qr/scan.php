<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

requireLogin();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>QR Scanner - Inventory System</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>

        .scanner-container {
            max-width: 650px;
            margin: 30px auto;
            padding: 20px;
        }

        .scanner-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.10);
        }

        .scanner-card h1 {
            text-align: center;
            color: #198754;
            margin-bottom: 10px;
        }

        .scanner-card p {
            text-align: center;
            color: #666;
        }

        #reader {
            width: 100%;
            max-width: 500px;
            margin: 25px auto;
        }

        .result-card {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .result-card h2 {
            color: #198754;
        }

        .item-details p {
            margin: 8px 0;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 7px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .btn-primary {
            background: #198754;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .error-message {
            display: none;
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 7px;
            margin-top: 15px;
            text-align: center;
        }

    </style>

</head>

<body>

<?php include "../includes/header.php"; ?>

<div class="scanner-container">

    <div class="scanner-card">

        <h1>QR Code Scanner</h1>

        <p>
            Point your phone camera at an inventory QR code.
        </p>

        <div id="reader"></div>

        <div
            id="errorMessage"
            class="error-message"
        >
            Item not found.
        </div>

        <div
            id="resultCard"
            class="result-card"
        >

            <h2>Item Found</h2>

            <div class="item-details">

                <p>
                    <strong>Item Code:</strong>
                    <span id="itemCode"></span>
                </p>

                <p>
                    <strong>Item Name:</strong>
                    <span id="itemName"></span>
                </p>

                <p>
                    <strong>Category:</strong>
                    <span id="category"></span>
                </p>

                <p>
                    <strong>Serial Number:</strong>
                    <span id="serialNumber"></span>
                </p>

                <p>
                    <strong>Location:</strong>
                    <span id="location"></span>
                </p>

                <p>
                    <strong>Condition:</strong>
                    <span id="condition"></span>
                </p>

                <p>
                    <strong>Status:</strong>
                    <span id="status"></span>
                </p>

            </div>

            <a
                id="borrowLink"
                href="#"
                class="btn btn-primary"
            >
                Borrow This Item
            </a>

        </div>

    </div>

</div>

<?php include "../includes/footer.php"; ?>


<script>

let scanner;

function onScanSuccess(decodedText) {

    console.log("QR scanned:", decodedText);

    /*
    |--------------------------------------------------------------------------
    | If QR contains our scan.php URL
    |--------------------------------------------------------------------------
    */

    try {

        const url = new URL(decodedText);

        const itemId = url.searchParams.get("id");

        if (itemId) {

            loadItem(itemId);

            if (scanner) {
                scanner.clear();
            }

            return;
        }

    } catch (error) {

        console.log("Not a URL QR code.");

    }


    /*
    |--------------------------------------------------------------------------
    | If QR contains only an item ID
    |--------------------------------------------------------------------------
    */

    if (/^\d+$/.test(decodedText)) {

        loadItem(decodedText);

        if (scanner) {
            scanner.clear();
        }

    }

}


function onScanFailure(error) {

    // Scanner continuously checks the camera.
    // No message needed for every failed frame.

}


function loadItem(itemId) {

    fetch("item.php?id=" + encodeURIComponent(itemId))

        .then(response => response.json())

        .then(data => {

            if (!data.success) {

                document.getElementById("errorMessage").style.display =
                    "block";

                return;
            }

            document.getElementById("errorMessage").style.display =
                "none";

            document.getElementById("resultCard").style.display =
                "block";

            document.getElementById("itemCode").textContent =
                data.item.item_code;

            document.getElementById("itemName").textContent =
                data.item.item_name;

            document.getElementById("category").textContent =
                data.item.category_name || "N/A";

            document.getElementById("serialNumber").textContent =
                data.item.serial_number || "N/A";

            document.getElementById("location").textContent =
                data.item.location || "N/A";

            document.getElementById("condition").textContent =
                data.item.item_condition;

            document.getElementById("status").textContent =
                data.item.status;


            /*
            |--------------------------------------------------------------------------
            | Borrow button
            |--------------------------------------------------------------------------
            */

            const borrowLink =
                document.getElementById("borrowLink");

            if (data.item.status === "Available") {

                borrowLink.href =
                    "../borrower/dashboard.php?item_id="
                    + encodeURIComponent(data.item.id);

                borrowLink.style.display = "inline-block";

            } else {

                borrowLink.style.display = "none";

            }

        })

        .catch(error => {

            console.error(error);

            document.getElementById("errorMessage").textContent =
                "Unable to load item information.";

            document.getElementById("errorMessage").style.display =
                "block";

        });

}


/*
|--------------------------------------------------------------------------
| Start Scanner
|--------------------------------------------------------------------------
*/

scanner = new Html5QrcodeScanner(
    "reader",
    {
        fps: 10,
        qrbox: {
            width: 250,
            height: 250
        }
    },
    false
);

scanner.render(
    onScanSuccess,
    onScanFailure
);

</script>

</body>

</html>