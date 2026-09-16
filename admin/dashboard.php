<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

requireAdmin();

/*
|--------------------------------------------------------------------------
| Update overdue transactions
|--------------------------------------------------------------------------
*/

$pdo->exec("
    UPDATE transactions
    SET status = 'Overdue'
    WHERE status = 'Borrowed'
      AND due_date IS NOT NULL
      AND due_date < CURDATE()
");

/*
|--------------------------------------------------------------------------
| Inventory Statistics
|--------------------------------------------------------------------------
*/

$totalItems = (int)$pdo->query("
    SELECT COUNT(*)
    FROM items
")->fetchColumn();

$availableItems = (int)$pdo->query("
    SELECT COUNT(*)
    FROM items
    WHERE status = 'Available'
")->fetchColumn();

$borrowedItems = (int)$pdo->query("
    SELECT COUNT(*)
    FROM items
    WHERE status = 'Borrowed'
")->fetchColumn();

$overdueItems = (int)$pdo->query("
    SELECT COUNT(*)
    FROM transactions
    WHERE status = 'Overdue'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| Borrower Statistics
|--------------------------------------------------------------------------
*/

$totalBorrowers = (int)$pdo->query("
    SELECT COUNT(*)
    FROM borrowers
    WHERE status = 'Active'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| Department Statistics
|--------------------------------------------------------------------------
*/

$totalDepartments = (int)$pdo->query("
    SELECT COUNT(*)
    FROM departments
    WHERE status = 'Active'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| User Statistics
|--------------------------------------------------------------------------
*/

$totalUsers = (int)$pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE status = 'Active'
")->fetchColumn();


/*
|--------------------------------------------------------------------------
| Recent Transactions
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        transactions.id,
        transactions.transaction_code,
        transactions.borrowed_date,
        transactions.due_date,
        transactions.returned_date,
        transactions.status,

        items.item_code,
        items.item_name,

        borrowers.full_name AS borrower_name,

        departments.department_name

    FROM transactions

    INNER JOIN items
        ON transactions.item_id = items.id

    INNER JOIN borrowers
        ON transactions.borrower_id = borrowers.id

    LEFT JOIN departments
        ON borrowers.department_id = departments.id

    ORDER BY transactions.created_at DESC

    LIMIT 8
");

$recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Recent Borrow Requests
|--------------------------------------------------------------------------
*/

$recentRequests = [];

try {

    $stmt = $pdo->query("
        SELECT *
        FROM borrow_requests
        ORDER BY created_at DESC
        LIMIT 5
    ");

    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    /*
     * If the borrow_requests table does not exist yet,
     * simply leave this section empty.
     */

    $recentRequests = [];

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

    <title>Admin Dashboard - Inventory System</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .dashboard-container {
            padding: 25px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            margin: 0;
            color: #198754;
        }

        .page-title p {
            color: #666;
            margin-top: 5px;
        }


        /* =========================================================
           STATISTICS
        ========================================================= */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 5px solid #198754;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #198754;
            margin-top: 8px;
        }


        /* =========================================================
           SECONDARY STATISTICS
        ========================================================= */

        .secondary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .secondary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .secondary-card h3 {
            margin: 0;
            color: #555;
            font-size: 14px;
        }

        .secondary-number {
            font-size: 26px;
            font-weight: bold;
            margin-top: 8px;
            color: #198754;
        }


        /* =========================================================
           CONTENT CARDS
        ========================================================= */

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .content-card h2 {
            margin-top: 0;
            color: #198754;
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e5e5;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f5f5f5;
            color: #444;
        }

        tr:hover {
            background: #fafafa;
        }


        /* =========================================================
           STATUS
        ========================================================= */

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-borrowed {
            background: #fff3cd;
            color: #856404;
        }

        .status-returned {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-overdue {
            background: #f8d7da;
            color: #842029;
        }

        .status-lost {
            background: #f8d7da;
            color: #842029;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-rejected {
            background: #f8d7da;
            color: #842029;
        }


        /* =========================================================
           EMPTY
        ========================================================= */

        .empty-message {
            color: #777;
            padding: 15px 0;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 800px) {

            .secondary-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 600px) {

            .dashboard-container {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/header.php"; ?>


<div class="dashboard-container">


    <!-- =========================================================
         PAGE TITLE
    ========================================================== -->

    <div class="page-title">

        <h1>Admin Dashboard</h1>

        <p>
            Inventory Borrowing and Return Management System
        </p>

    </div>


    <!-- =========================================================
         MAIN INVENTORY STATISTICS
    ========================================================== -->

    <div class="stats-grid">


        <div class="stat-card">

            <h3>Total Items</h3>

            <div class="stat-number">
                <?= $totalItems ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>Available Items</h3>

            <div class="stat-number">
                <?= $availableItems ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>Borrowed Items</h3>

            <div class="stat-number">
                <?= $borrowedItems ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>Overdue Items</h3>

            <div class="stat-number">
                <?= $overdueItems ?>
            </div>

        </div>

    </div>


    <!-- =========================================================
         OTHER STATISTICS
    ========================================================== -->

    <div class="secondary-grid">


        <div class="secondary-card">

            <h3>Active Borrowers</h3>

            <div class="secondary-number">
                <?= $totalBorrowers ?>
            </div>

        </div>


        <div class="secondary-card">

            <h3>Active Departments</h3>

            <div class="secondary-number">
                <?= $totalDepartments ?>
            </div>

        </div>


        <div class="secondary-card">

            <h3>Active Users</h3>

            <div class="secondary-number">
                <?= $totalUsers ?>
            </div>

        </div>

    </div>


    <!-- =========================================================
         RECENT TRANSACTIONS
    ========================================================== -->

    <div class="content-card">

        <h2>Recent Transactions</h2>


        <?php if (count($recentTransactions) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>Transaction</th>

                            <th>Item</th>

                            <th>Borrower</th>

                            <th>Department</th>

                            <th>Borrowed</th>

                            <th>Due</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($recentTransactions as $transaction): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $transaction['transaction_code']
                                    ) ?>
                                </strong>
                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $transaction['item_name']
                                ) ?>

                                <br>

                                <small>
                                    <?= htmlspecialchars(
                                        $transaction['item_code']
                                    ) ?>
                                </small>

                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $transaction['borrower_name']
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $transaction['department_name'] ?? 'N/A'
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $transaction['borrowed_date']
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $transaction['due_date'] ?? 'N/A'
                                ) ?>
                            </td>


                            <td>

                                <?php

                                $statusClass = 'status-borrowed';

                                if ($transaction['status'] === 'Returned') {
                                    $statusClass = 'status-returned';
                                }

                                elseif ($transaction['status'] === 'Overdue') {
                                    $statusClass = 'status-overdue';
                                }

                                elseif ($transaction['status'] === 'Lost') {
                                    $statusClass = 'status-lost';
                                }

                                ?>

                                <span class="status <?= $statusClass ?>">

                                    <?= htmlspecialchars(
                                        $transaction['status']
                                    ) ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <p style="margin-top:20px;">

                <a
                    href="transactions.php"
                    style="
                        color:#198754;
                        font-weight:bold;
                        text-decoration:none;
                    "
                >
                    View All Transactions →
                </a>

            </p>


        <?php else: ?>

            <p class="empty-message">
                No transactions recorded yet.
            </p>

        <?php endif; ?>

    </div>


    <!-- =========================================================
         RECENT BORROW REQUESTS
    ========================================================== -->

    <div class="content-card">

        <h2>Recent Borrow Requests</h2>


        <?php if (count($recentRequests) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Status</th>

                            <th>Created</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($recentRequests as $request): ?>

                        <tr>

                            <td>
                                #<?= htmlspecialchars($request['id']) ?>
                            </td>


                            <td>

                                <?php

                                $requestStatus =
                                    $request['status'] ?? 'Pending';

                                $requestClass =
                                    'status-pending';

                                if ($requestStatus === 'Approved') {
                                    $requestClass =
                                        'status-approved';
                                }

                                elseif ($requestStatus === 'Rejected') {
                                    $requestClass =
                                        'status-rejected';
                                }

                                ?>

                                <span
                                    class="status <?= $requestClass ?>"
                                >
                                    <?= htmlspecialchars(
                                        $requestStatus
                                    ) ?>
                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $request['created_at'] ?? ''
                                ) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <p style="margin-top:20px;">

                <a
                    href="requests.php"
                    style="
                        color:#198754;
                        font-weight:bold;
                        text-decoration:none;
                    "
                >
                    View Borrow Requests →
                </a>

            </p>


        <?php else: ?>

            <p class="empty-message">
                No borrow requests recorded yet.
            </p>

        <?php endif; ?>

    </div>


</div>


<?php include "../includes/footer.php"; ?>

</body>

</html>