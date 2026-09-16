<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| Make sure only Staff can access this page
|--------------------------------------------------------------------------
*/

if ($_SESSION['role'] !== 'Staff') {
    header("Location: ../admin/dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get logged-in user's information
|--------------------------------------------------------------------------
*/

$userId = $_SESSION['user_id'];

$fullName = $_SESSION['full_name'] ?? 'Staff User';

$borrowerId = $_SESSION['borrower_id'] ?? null;


/*
|--------------------------------------------------------------------------
| Get borrower's department
|--------------------------------------------------------------------------
*/

$borrower = null;

if ($borrowerId) {

    $stmt = $pdo->prepare("
        SELECT
            borrowers.id,
            borrowers.borrower_code,
            borrowers.full_name,
            borrowers.position,
            borrowers.contact_number,
            borrowers.email,
            departments.department_name

        FROM borrowers

        LEFT JOIN departments
            ON departments.id = borrowers.department_id

        WHERE borrowers.id = ?

        LIMIT 1
    ");

    $stmt->execute([$borrowerId]);

    $borrower = $stmt->fetch(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| Count available inventory
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM items
    WHERE status = 'Available'
");

$availableItems = (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Count current borrowed items
|--------------------------------------------------------------------------
*/

$borrowedItems = 0;

if ($borrowerId) {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)

        FROM transactions

        WHERE borrower_id = ?

        AND status = 'Borrowed'
    ");

    $stmt->execute([$borrowerId]);

    $borrowedItems = (int) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| Count returned transactions
|--------------------------------------------------------------------------

*/

$returnedItems = 0;

if ($borrowerId) {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)

        FROM transactions

        WHERE borrower_id = ?

        AND status = 'Returned'
    ");

    $stmt->execute([$borrowerId]);

    $returnedItems = (int) $stmt->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| Get current borrowed items
|--------------------------------------------------------------------------
*/

$currentBorrowed = [];

if ($borrowerId) {

    $stmt = $pdo->prepare("
        SELECT
            transactions.id,
            transactions.transaction_code,
            transactions.borrowed_date,
            transactions.due_date,

            items.item_code,
            items.item_name

        FROM transactions

        INNER JOIN items
            ON items.id = transactions.item_id

        WHERE transactions.borrower_id = ?

        AND transactions.status = 'Borrowed'

        ORDER BY transactions.borrowed_date DESC
    ");

    $stmt->execute([$borrowerId]);

    $currentBorrowed = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Staff Dashboard - Inventory System</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .dashboard-container {
            padding: 30px;
        }

        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .welcome-card h1 {
            margin: 0 0 8px;
        }

        .welcome-card p {
            margin: 0;
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .stat-card h3 {
            margin: 0 0 10px;
            color: #666;
            font-size: 15px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: bold;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .content-card h2 {
            margin-top: 0;
        }

        .borrower-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-box {
            background: #f7f7f7;
            padding: 15px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f7f7f7;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            background: #fff3cd;
            color: #856404;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        @media (max-width: 768px) {

            .dashboard-container {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .borrower-info {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">

    <div class="dashboard-container">


        <!-- Welcome -->

        <div class="welcome-card">

            <h1>
                Welcome, <?= htmlspecialchars($fullName) ?>!
            </h1>

            <p>
                This is your Staff Dashboard.
            </p>

        </div>


        <!-- Statistics -->

        <div class="stats-grid">


            <div class="stat-card">

                <h3>
                    Available Items
                </h3>

                <div class="stat-number">
                    <?= $availableItems ?>
                </div>

            </div>


            <div class="stat-card">

                <h3>
                    My Borrowed Items
                </h3>

                <div class="stat-number">
                    <?= $borrowedItems ?>
                </div>


        
            </div>


            <div class="stat-card">

                <h3>
                    Returned Items
                </h3>

                <div class="stat-number">
                    <?= $returnedItems ?>
                </div>

            </div>


        </div>


        <!-- Borrower Information -->

        <div class="content-card">

            <h2>
                My Information
            </h2>


            <?php if ($borrower): ?>

                <div class="borrower-info">


                    <div class="info-box">

                        <div class="info-label">
                            Borrower Code
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $borrower['borrower_code']
                            ) ?>

                        </div>

                    </div>


                    <div class="info-box">

                        <div class="info-label">
                            Full Name
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $borrower['full_name']
                            ) ?>

                        </div>

                    </div>


                    <div class="info-box">

                        <div class="info-label">
                            Department
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $borrower['department_name']
                                ?? 'Not Assigned'
                            ) ?>

                        </div>

                    </div>


                    <div class="info-box">

                        <div class="info-label">
                            Position
                        </div>

                        <div class="info-value">

                            <?= htmlspecialchars(
                                $borrower['position']
                                ?? 'Not Provided'
                            ) ?>

                        </div>

                    </div>
                </div>


            <?php else: ?>

                <div class="empty-message">

                    Your Staff account is not yet connected
                    to a borrower record.

                </div>

            <?php endif; ?>


        </div>


        <!-- Current Borrowed Items -->

        <div class="content-card">

            <h2>
                My Borrowed Items
            </h2>


            <?php if (!empty($currentBorrowed)): ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Transaction
                                </th>

                                <th>
                                    Item Code
                                </th>

                                <th>
                                    Item
                                </th>

                                <th>
                                    Borrowed Date
                                </th>

                                <th>
                                    Due Date
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($currentBorrowed as $transaction): ?>

                            <tr>

                                <td>

                                    <?= htmlspecialchars(
                                        $transaction['transaction_code']
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $transaction['item_code']
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $transaction['item_name']
                                    ) ?>
                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $transaction['borrowed_date']
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $transaction['due_date']
                                    ) ?>

                                </td>

                                <td>

                                    <span class="status-badge">
                                        Borrowed
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>

                <div class="empty-message">

                    You currently have no borrowed items.

                </div>

            <?php endif; ?>


        </div>


    </div>

</main>


</body>

</html>