<?php

require_once __DIR__ . '/../includes/auth.php';
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


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            font-family: Arial, sans-serif;

            background: #f4f7f5;

            margin: 0;

            padding: 20px;

        }


        .scanner-container {

            max-width: 600px;

            margin: 30px auto;

        }


        .scanner-card {

            background: white;

            padding: 30px;

            border-radius: 15px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, 0.08);

            text-align: center;

        }


        h1 {

            margin-bottom: 8px;

        }


        .subtitle {

            color: #666;

            margin-bottom: 25px;

        }


        #reader {

            width: 100%;

            max-width: 450px;

            margin: auto;

        }


        .result {

            margin-top: 25px;

            padding: 20px;

            border-radius: 10px;

            background: #e8f5e9;

            display: none;

        }


        .result h2 {

            margin-top: 0;

            color: #198754;

        }


        .item-info {

            text-align: left;

            margin-top: 15px;

        }


        .item-info p {

            margin: 8px 0;

        }


        .scan-again {

            margin-top: 20px;

            padding: 12px 20px;

            border: none;

            border-radius: 8px;

            background: #198754;

            color: white;

            cursor: pointer;

        }


        .error {

            margin-top: 20px;

            padding: 15px;

            background: #f8d7da;

            color: #842029;

            border-radius: 8px;

            display: none;

        }

    </style>

</head>


<body>


<div class="scanner-container">


    <div class="scanner-card">


        <h1>
            QR Scanner
        </h1>


        <p class="subtitle">

            Scan an inventory item's QR code.

        </p>


        <div id="reader"></div>


        <div
            id="error"
            class="error"
        ></div>


        <div
            id="result"
            class="result"
        >

            <h2>
                Item Found
            </h2>


            <div
                id="item-info"
                class="item-info"
            ></div>


            <button
                class="scan-again"
                onclick="scanAgain()"
            >
                Scan Another Item
            </button>

        </div>


    </div>

</div>


<script src="https://unpkg.com/html5-qrcode"></script>


<script>

let scanner;


function startScanner() {

    scanner = new Html5Qrcode("reader");


    scanner.start(

        {
            facingMode: "environment"
        },

        {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            }
        },

        function(decodedText) {

            scanner.stop();

            findItem(decodedText);

        },

        function(errorMessage) {

            // Ignore continuous scanning errors.

        }

    ).catch(function(error) {

        showError(
            "Unable to access the camera. " +
            "Please allow camera permission."
        );

    });

}


function findItem(qrCode) {

    fetch(
        "find_item.php?qr=" +
        encodeURIComponent(qrCode)
    )

    .then(response => response.json())

    .then(data => {

        if (data.success) {

            showItem(data.item);

        } else {

            showError(
                data.message ||
                "Item not found."
            );

            startScanner();

        }

    })

    .catch(error => {

        showError(
            "Unable to connect to the server."
        );

    });

}


function showItem(item) {

    document.getElementById(
        "result"
    ).style.display = "block";


    document.getElementById(
        "reader"
    ).style.display = "none";


    document.getElementById(
        "item-info"
    ).innerHTML = `

        <p>
            <strong>Item Code:</strong>
            ${escapeHtml(item.item_code)}
        </p>

        <p>
            <strong>Item:</strong>
            ${escapeHtml(item.item_name)}
        </p>

        <p>
            <strong>Category:</strong>
            ${escapeHtml(item.category_name)}
        </p>

        <p>
            <strong>Serial Number:</strong>
            ${escapeHtml(
                item.serial_number || '—'
            )}
        </p>

        <p>
            <strong>Location:</strong>
            ${escapeHtml(
                item.location || '—'
            )}
        </p>

        <p>
            <strong>Condition:</strong>
            ${escapeHtml(
                item.item_condition
            )}
        </p>

        <p>
            <strong>Status:</strong>
            ${escapeHtml(
                item.status
            )}
        </p>
${item.status === 'Available'
    ? `
        <div style="margin-top:20px;">

            <a
                href="../borrower/borrow.php?id=${item.id}"
                style="
                    display:inline-block;
                    background:#198754;
                    color:white;
                    padding:12px 25px;
                    border-radius:8px;
                    text-decoration:none;
                "
            >
                Borrow This Item
            </a>

        </div>
    `
    : item.status === 'Borrowed'
    ? `
        <div style="margin-top:20px;">

            <a
                href="../borrower/return.php?id=${item.id}"
                style="
                    display:inline-block;
                    background:#dc3545;
                    color:white;
                    padding:12px 25px;
                    border-radius:8px;
                    text-decoration:none;
                "
            >
                Return This Item
            </a>

        </div>
    `
    : ''
}
    `;

}


function scanAgain() {

    document.getElementById(
        "result"
    ).style.display = "none";


    document.getElementById(
        "reader"
    ).style.display = "block";


    document.getElementById(
        "error"
    ).style.display = "none";


    startScanner();

}


function showError(message) {

    const error =
        document.getElementById("error");

    error.innerText = message;

    error.style.display = "block";

}


function escapeHtml(text) {

    const div =
        document.createElement("div");

    div.innerText = text;

    return div.innerHTML;

}


startScanner();

</script>


</body>

</html>
