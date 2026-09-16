<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

requireAdmin();


/*
|--------------------------------------------------------------------------
| Process Request
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $requestId = isset($_POST['request_id'])
        ? (int)$_POST['request_id']
        : 0;

    $action = $_POST['action'] ?? '';

    if ($requestId <= 0) {
        header("Location: requests.php?error=invalid");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Request
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            borrow_requests.*,

            items.item_code,
            items.item_name,
            items.status AS item_status,

            borrowers.full_name AS borrower_name,

            users.full_name AS requester_name

        FROM borrow_requests

        INNER JOIN items
            ON borrow_requests.item_id = items.id

        INNER JOIN borrowers
            ON borrow_requests.borrower_id = borrowers.id

        INNER JOIN users
            ON borrow_requests.requested_by = users.id

        WHERE borrow_requests.id = ?

        LIMIT 1
    ");

    $stmt->execute([$requestId]);

    $request = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$request) {
        header("Location: requests.php?error=not_found");
        exit;
    }


    if ($request['status'] !== 'Pending') {
        header("Location: requests.php?error=already_processed");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */

    if ($action === 'reject') {

        $stmt = $pdo->prepare("
            UPDATE borrow_requests
            SET
                status = 'Rejected',
                processed_by = ?,
                processed_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $requestId
        ]);

        header("Location: requests.php?success=rejected");
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */

    if ($action === 'approve') {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Check Item Again
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT id, status
                FROM items
                WHERE id = ?
                FOR UPDATE
            ");

            $stmt->execute([
                $request['item_id']
            ]);

            $item = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$item) {
                throw new Exception("Item no longer exists.");
            }


            if ($item['status'] !== 'Available') {
                throw new Exception(
                    "This item is no longer available."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Generate Transaction Code
            |--------------------------------------------------------------------------
            */

            do {

                $transactionCode =
                    'TRX-' .
                    date('Ymd') .
                    '-' .
                    strtoupper(
                        substr(
                            bin2hex(random_bytes(4)),
                            0,
                            6
                        )
                    );

                $check = $pdo->prepare("
                    SELECT id
                    FROM transactions
                    WHERE transaction_code = ?
                    LIMIT 1
                ");

                $check->execute([
                    $transactionCode
                ]);

            } while ($check->fetch());


            /*
            |--------------------------------------------------------------------------
            | Create Transaction
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO transactions
                (
                    transaction_code,
                    item_id,
                    borrower_id,
                    processed_by,
                    borrowed_date,
                    due_date,
                    condition_before,
                    status,
                    remarks
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    ?,
                    ?,
                    'Borrowed',
                    ?
                )
            ");

            $stmt->execute([

                $transactionCode,

                $request['item_id'],

                $request['borrower_id'],

                $_SESSION['user_id'],

                $request['due_date'],

                $item['item_condition'] ?? 'Good',

                $request['remarks']

            ]);


            /*
            |--------------------------------------------------------------------------
            | Update Item
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE items
                SET status = 'Borrowed'
                WHERE id = ?
            ");

            $stmt->execute([
                $request['item_id']
            ]);


            /*
            |--------------------------------------------------------------------------
            | Approve Request
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE borrow_requests
                SET
                    status = 'Approved',
                    processed_by = ?,
                    processed_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                $requestId
            ]);


            $pdo->commit();


            header("Location: requests.php?success=approved");
            exit;


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header(
                "Location: requests.php?error="
                . urlencode($e->getMessage())
            );

            exit;
        }
    }


    header("Location: requests.php?error=invalid_action");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Requests
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        borrow_requests.id,

        borrow_requests.due_date,

        borrow_requests.remarks,

        borrow_requests.status,

        borrow_requests.created_at,

        borrow_requests.processed_at,

        items.item_code,

        items.item_name,

        items.status AS item_status,

        borrowers.borrower_code,

        borrowers.full_name AS borrower_name,

        departments.department_name,

        users.full_name AS requester_name,

        processor.full_name AS processor_name

    FROM borrow_requests

    INNER JOIN items
        ON borrow_requests.item_id = items.id

    INNER JOIN borrowers
        ON borrow_requests.borrower_id = borrowers.id

    LEFT JOIN departments
        ON borrowers.department_id = departments.id

    INNER JOIN users
        ON borrow_requests.requested_by = users.id

    LEFT JOIN users AS processor
        ON borrow_requests.processed_by = processor.id

    ORDER BY

        CASE
            WHEN borrow_requests.status = 'Pending'
            THEN 1
            ELSE 2
        END,

        borrow_requests.created_at DESC
");

$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Borrow Requests - Inventory System</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .page-container {
            padding: 25px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            color: #198754;
            margin-bottom: 5px;
        }

        .content-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .message {
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .error {
            background: #f8d7da;
            color: #842029;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f5f5f5;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .approved {
            background: #d1e7dd;
            color: #0f5132;
        }

        .rejected {
            background: #f8d7da;
            color: #842029;
        }

        .btn {
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-approve {
            background: #198754;
            color: white;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .action-form {
            display: inline-block;
            margin-right: 5px;
        }

        .empty {
            color: #777;
            padding: 15px 0;
        }

    </style>

</head>

<body>

<?php include "../includes/header.php"; ?>


<div class="page-container">

    <div class="page-header">

        <h1>Borrow Requests</h1>

        <p>
            Review and process staff inventory borrowing requests.
        </p>

    </div>


    <?php if (isset($_GET['success'])): ?>

        <div class="message success">

            <?php if ($_GET['success'] === 'approved'): ?>

                Borrow request approved successfully.

            <?php elseif ($_GET['success'] === 'rejected'): ?>

                Borrow request rejected successfully.

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['error'])): ?>

        <div class="message error">

            <?= htmlspecialchars($_GET['error']) ?>

        </div>

    <?php endif; ?>


    <div class="content-card">

        <?php if (count($requests) > 0): ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>Item</th>

                            <th>Borrower</th>

                            <th>Department</th>

                            <th>Requested By</th>

                            <th>Due Date</th>

                            <th>Requested</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($requests as $request): ?>

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
                                    $request['borrower_name']
                                ) ?>

                                <br>

                                <small>
                                    <?= htmlspecialchars(
                                        $request['borrower_code']
                                    ) ?>
                                </small>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $request['department_name']
                                    ?? 'N/A'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $request['requester_name']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $request['due_date']
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $request['created_at']
                                ) ?>

                            </td>


                            <td>

                                <?php

                                $class = 'pending';

                                if ($request['status'] === 'Approved') {
                                    $class = 'approved';
                                }

                                elseif ($request['status'] === 'Rejected') {
                                    $class = 'rejected';
                                }

                                ?>

                                <span class="status <?= $class ?>">

                                    <?= htmlspecialchars(
                                        $request['status']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?php if ($request['status'] === 'Pending'): ?>

                                    <form
                                        method="POST"
                                        class="action-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= $request['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-approve"
                                            onclick="
                                                return confirm(
                                                    'Approve this borrow request?'
                                                );
                                            "
                                        >
                                            Approve
                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        class="action-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= $request['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-reject"
                                            onclick="
                                                return confirm(
                                                    'Reject this borrow request?'
                                                );
                                            "
                                        >
                                            Reject
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <small>
                                        <?= htmlspecialchars(
                                            $request['processor_name']
                                            ?? 'N/A'
                                        ) ?>
                                    </small>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="empty">
                No borrow requests found.
            </p>

        <?php endif; ?>

    </div>

</div>


<?php include "../includes/footer.php"; ?>

</body>

</html>