<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

requireLogin();

if ($_SESSION['role'] !== 'Staff') {
    header("Location: ../admin/dashboard.php");
    exit;
}

$userId = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Find the borrower connected to this Staff account
|--------------------------------------------------------------------------
*/

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
        ON borrowers.department_id = departments.id
    WHERE borrowers.user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);
$borrower = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$borrower) {
    die("
        <div style='
            font-family: Arial;
            max-width: 600px;
            margin: 80px auto;
            padding: 30px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 10px;
        '>
            <h2>No Borrower Account Linked</h2>
            <p>Your Staff account is not yet connected to a borrower record.</p>
            <p>Please ask the administrator to link your account.</p>
            <a href='../logout.php'>Logout</a>
        </div>
    ");
}

$borrowerId = $borrower['id'];

/*
|--------------------------------------------------------------------------
| Available Items
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        items.id,
        items.item_code,
        items.item_name,
        items.category_id,
        categories.category_name,
        items.serial_number,
        items.location,
        items.item_condition,
        items.status
    FROM items
    LEFT JOIN categories
        ON items.category_id = categories.id
    WHERE items.status = 'Available'
    ORDER BY items.item_name ASC
");

$availableItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Current Borrowed Items
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        transactions.id,
        transactions.transaction_code,
        transactions.borrowed_date,
        transactions.due_date,
        transactions.status,
        transactions.remarks,
        items.item_code,
        items.item_name,
        items.serial_number
    FROM transactions
    INNER JOIN items
        ON transactions.item_id = items.id
    WHERE transactions.borrower_id = ?
      AND transactions.status IN ('Borrowed', 'Overdue')
    ORDER BY transactions.borrowed_date DESC
");

$stmt->execute([$borrowerId]);
$borrowedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Returned History
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        transactions.transaction_code,
        transactions.borrowed_date,
        transactions.due_date,
        transactions.returned_date,
        transactions.status,
        items.item_code,
        items.item_name
    FROM transactions
    INNER JOIN items
        ON transactions.item_id = items.id
    WHERE transactions.borrower_id = ?
      AND transactions.status = 'Returned'
    ORDER BY transactions.returned_date DESC
    LIMIT 10
");

$stmt->execute([$borrowerId]);
$returnedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Update overdue transactions
|--------------------------------------------------------------------------
*/

$pdo->exec("
    UPDATE transactions
    SET status = 'Overdue'
    WHERE borrower_id = " . (int)$borrowerId . "
      AND status = 'Borrowed'
      AND due_date IS NOT NULL
      AND due_date < CURDATE()
");

/*
|--------------------------------------------------------------------------
| Count Available
|--------------------------------------------------------------------------
*/

$availableCount = count($availableItems);

/*
|--------------------------------------------------------------------------
| Count Borrowed
|--------------------------------------------------------------------------
*/

$borrowedCount = count($borrowedItems);

/*
|--------------------------------------------------------------------------
| Count Returned
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM transactions
    WHERE borrower_id = ?
      AND status = 'Returned'
");

$stmt->execute([$borrowerId]);
$returnedCount = (int)$stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| My Borrow Requests
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        borrow_requests.id,
        borrow_requests.due_date,
        borrow_requests.remarks,
        borrow_requests.status,
        borrow_requests.created_at,

        items.item_code,
        items.item_name

    FROM borrow_requests

    INNER JOIN items
        ON borrow_requests.item_id = items.id

    WHERE borrow_requests.borrower_id = ?

    ORDER BY borrow_requests.created_at DESC

    LIMIT 10
");

$stmt->execute([$borrowerId]);

$myRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
            padding: 25px;
        }

        .welcome-card {
            background: #198754;
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .welcome-card h1 {
            margin: 0 0 8px;
        }

        .welcome-card p {
            margin: 5px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 5px solid #198754;
        }

        .stat-card h3 {
            margin: 0;
            color: #666;
            font-size: 15px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin-top: 10px;
            color: #198754;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .content-card h2 {
            margin-top: 0;
            color: #198754;
        }

        .borrow-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 6px;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn {
            border: none;
            border-radius: 6px;
            padding: 11px 18px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }

        .btn-primary {
            background: #198754;
            color: white;
        }

        .btn-primary:hover {
            background: #146c43;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #bb2d3b;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-borrowed {
            background: #fff3cd;
            color: #856404;
        }

        .status-overdue {
            background: #f8d7da;
            color: #842029;
        }

        .status-returned {
            background: #d1e7dd;
            color: #0f5132;
        }

        .empty-message {
            color: #777;
            padding: 15px 0;
        }

        @media (max-width: 900px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .borrow-form {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php include "../includes/header.php"; ?>

<div class="dashboard-container">

    <!-- Welcome -->

    <div class="welcome-card">

        <h1>
            Welcome, <?= htmlspecialchars($borrower['full_name']) ?>!
        </h1>

        <p>
            Department:
            <strong>
                <?= htmlspecialchars($borrower['department_name'] ?? 'N/A') ?>
            </strong>
        </p>

        <p>
            Borrower Code:
            <strong>
                <?= htmlspecialchars($borrower['borrower_code']) ?>
            </strong>
        </p>

    </div>


    <!-- Statistics -->

    <div class="stats-grid">

        <div class="stat-card">

            <h3>Available Items</h3>

            <div class="number">
                <?= $availableCount ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>Currently Borrowed</h3>

            <div class="number">
                <?= $borrowedCount ?>
            </div>

        </div>


        <div class="stat-card">

            <h3>Total Returned</h3>

            <div class="number">
                <?= $returnedCount ?>
            </div>

        </div>

    </div>


  <!-- Borrow Item -->

<div class="content-card">

    <h2>Borrow an Item</h2>

    <?php if (count($availableItems) > 0): ?>

        <form
            action="../actions/request_borrow.php"
            method="POST"
            class="borrow-form"
        >

            <input
                type="hidden"
                name="borrower_id"
                value="<?= $borrowerId ?>"
            >

            <div class="form-group">

                <label for="item_id">
                    Select Item
                </label>

                <select
                    name="item_id"
                    id="item_id"
                    required
                >

                    <option value="">
                        -- Select Item --
                    </option>

                    <?php foreach ($availableItems as $item): ?>

                        <option value="<?= $item['id'] ?>">

                            <?= htmlspecialchars($item['item_code']) ?>
                            -
                            <?= htmlspecialchars($item['item_name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="due_date">
                    Due Date
                </label>

                <input
                    type="date"
                    name="due_date"
                    id="due_date"
                    min="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Request Item
                </button>

            </div>

        </form>

    <?php else: ?>

        <p class="empty-message">
            There are currently no available items.
        </p>

    <?php endif; ?>

</div>


<!-- =========================================================
     MY BORROW REQUESTS
========================================================== -->

<div class="content-card">

    <h2>My Borrow Requests</h2>

    <?php if (count($myRequests) > 0): ?>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>Item</th>
                        <th>Requested</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($myRequests as $request): ?>

                    <tr>

                        <td>

                            <strong>
                                <?= htmlspecialchars(
                                    $request['item_name']
                                ) ?>
                            </strong>

                            <br>

                            <small>
                                <?= htmlspecialchars(
                                    $request['item_code']
                                ) ?>
                            </small>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $request['created_at']
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $request['due_date']
                            ) ?>

                        </td>


                        <td>

                            <?php

                            $statusClass = 'status-pending';

                            if ($request['status'] === 'Approved') {

                                $statusClass = 'status-approved';

                            } elseif ($request['status'] === 'Rejected') {

                                $statusClass = 'status-rejected';

                            }

                            ?>

                            <span
                                class="status <?= $statusClass ?>"
                            >

                                <?= htmlspecialchars(
                                    $request['status']
                                ) ?>

                            </span>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <p class="empty-message">
            You have no borrow requests.
        </p>

    <?php endif; ?>

</div>


<!-- Current Borrowed Items -->

<div class="content-card">

    <h2>My Borrowed Items</h2>

    <?php if (count($borrowedItems) > 0): ?>

        <!-- your existing My Borrowed Items table goes here -->

    <?php else: ?>

        <p class="empty-message">
            You currently have no borrowed items.
        </p>

    <?php endif; ?>

</div>


    <!-- Return History -->

    <div class="content-card">

        <h2>Recent Return History</h2>

        <?php if (count($returnedItems) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>Transaction</th>
                            <th>Item</th>
                            <th>Borrowed</th>
                            <th>Returned</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($returnedItems as $item): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($item['transaction_code']) ?>
                            </td>

                            <td>

                                <?= htmlspecialchars($item['item_name']) ?>

                                <br>

                                <small>
                                    <?= htmlspecialchars($item['item_code']) ?>
                                </small>

                            </td>

                            <td>
                                <?= htmlspecialchars($item['borrowed_date']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($item['returned_date']) ?>
                            </td>

                            <td>

                                <span class="status status-returned">
                                    Returned
                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="empty-message">
                No returned items yet.
            </p>

        <?php endif; ?>

    </div>

</div>

<?php include "../includes/footer.php"; ?>

</body>

</html>