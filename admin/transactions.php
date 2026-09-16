<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';


// --------------------------------------------------
// FILTERS
// --------------------------------------------------

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');


// --------------------------------------------------
// AUTOMATICALLY MARK OVERDUE TRANSACTIONS
// --------------------------------------------------
//
// Only Borrowed transactions can become Overdue.
// Returned and Lost transactions are left unchanged.
//

$overdueStmt = $pdo->prepare("
    UPDATE transactions
    SET status = 'Overdue'
    WHERE status = 'Borrowed'
      AND due_date < NOW()
");

$overdueStmt->execute();


// --------------------------------------------------
// BUILD QUERY
// --------------------------------------------------

$sql = "
    SELECT
        t.id,
        t.transaction_code,

        i.item_code,
        i.item_name,

        b.borrower_code,
        b.full_name AS borrower_name,

        d.department_name,

        u.full_name AS processed_by,

        t.borrowed_date,
        t.due_date,
        t.returned_date,

        t.condition_before,
        t.condition_after,

        t.remarks,
        t.status

    FROM transactions t

    INNER JOIN items i
        ON t.item_id = i.id

    INNER JOIN borrowers b
        ON t.borrower_id = b.id

    INNER JOIN departments d
        ON b.department_id = d.id

    INNER JOIN users u
        ON t.processed_by = u.id

    WHERE 1 = 1
";

$params = [];


// --------------------------------------------------
// SEARCH FILTER
// --------------------------------------------------

if ($search !== '') {

    $sql .= "
        AND (
            t.transaction_code LIKE ?
            OR i.item_code LIKE ?
            OR i.item_name LIKE ?
            OR b.borrower_code LIKE ?
            OR b.full_name LIKE ?
            OR d.department_name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


// --------------------------------------------------
// STATUS FILTER
// --------------------------------------------------

$allowedStatuses = [
    'Borrowed',
    'Returned',
    'Overdue',
    'Lost'
];

if (
    $status !== '' &&
    in_array($status, $allowedStatuses, true)
) {

    $sql .= "
        AND t.status = ?
    ";

    $params[] = $status;
}


// --------------------------------------------------
// ORDER
// --------------------------------------------------

$sql .= "
    ORDER BY t.borrowed_date DESC, t.id DESC
";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$transactions = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


// --------------------------------------------------
// SUMMARY COUNTS
// --------------------------------------------------

$totalStmt = $pdo->query("
    SELECT COUNT(*)
    FROM transactions
");

$totalTransactions = (int) $totalStmt->fetchColumn();


$borrowedStmt = $pdo->query("
    SELECT COUNT(*)
    FROM transactions
    WHERE status = 'Borrowed'
");

$totalBorrowed = (int) $borrowedStmt->fetchColumn();


$returnedStmt = $pdo->query("
    SELECT COUNT(*)
    FROM transactions
    WHERE status = 'Returned'
");

$totalReturned = (int) $returnedStmt->fetchColumn();


$overdueStmt = $pdo->query("
    SELECT COUNT(*)
    FROM transactions
    WHERE status = 'Overdue'
");

$totalOverdue = (int) $overdueStmt->fetchColumn();


$lostStmt = $pdo->query("
    SELECT COUNT(*)
    FROM transactions
    WHERE status = 'Lost'
");

$totalLost = (int) $lostStmt->fetchColumn();


// --------------------------------------------------
// HELPER FUNCTIONS
// --------------------------------------------------

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatDateTime($date)
{
    if (!$date) {
        return '-';
    }

    return date(
        'M d, Y h:i A',
        strtotime($date)
    );
}


function statusClass($status)
{
    switch ($status) {

        case 'Borrowed':
            return 'status-borrowed';

        case 'Returned':
            return 'status-returned';

        case 'Overdue':
            return 'status-overdue';

        case 'Lost':
            return 'status-lost';

        default:
            return '';
    }
}


function conditionClass($condition)
{
    switch ($condition) {

        case 'Excellent':
            return 'condition-excellent';

        case 'Good':
            return 'condition-good';

        case 'Fair':
            return 'condition-fair';

        case 'Damaged':
            return 'condition-damaged';

        default:
            return '';
    }
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<style>

    .transactions-page {
        padding: 25px;
    }


    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }


    .page-header h1 {
        margin: 0;
        color: #1f2937;
    }


    .page-header p {
        margin: 5px 0 0;
        color: #6b7280;
    }


    /* SUMMARY */

    .summary-grid {
        display: grid;
        grid-template-columns:
            repeat(5, minmax(150px, 1fr));

        gap: 15px;
        margin-bottom: 25px;
    }


    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 20px;

        box-shadow:
            0 3px 12px rgba(0, 0, 0, 0.08);

        border-left: 5px solid #198754;
    }


    .summary-card h3 {
        margin: 0 0 8px;
        font-size: 14px;
        color: #6b7280;
    }


    .summary-number {
        font-size: 28px;
        font-weight: bold;
        color: #1f2937;
    }


    .summary-borrowed {
        border-left-color: #0d6efd;
    }


    .summary-returned {
        border-left-color: #198754;
    }


    .summary-overdue {
        border-left-color: #dc3545;
    }


    .summary-lost {
        border-left-color: #6f42c1;
    }


    /* FILTER */

    .filter-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;

        box-shadow:
            0 3px 12px rgba(0, 0, 0, 0.08);
    }


    .filter-form {
        display: flex;
        gap: 12px;
        align-items: end;
        flex-wrap: wrap;
    }


    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }


    .filter-group label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }


    .filter-group input,
    .filter-group select {
        min-width: 220px;
        padding: 10px 12px;

        border: 1px solid #d1d5db;
        border-radius: 7px;

        font-size: 14px;
    }


    .filter-group select {
        min-width: 160px;
    }


    .btn {
        border: none;
        border-radius: 7px;
        padding: 10px 18px;

        font-weight: 600;
        cursor: pointer;

        text-decoration: none;
        display: inline-block;
    }


    .btn-primary {
        background: #198754;
        color: white;
    }


    .btn-primary:hover {
        background: #157347;
    }


    .btn-secondary {
        background: #6c757d;
        color: white;
    }


    .btn-secondary:hover {
        background: #5c636a;
    }


    /* TABLE */

    .table-card {
        background: white;
        border-radius: 12px;

        box-shadow:
            0 3px 12px rgba(0, 0, 0, 0.08);

        overflow: hidden;
    }


    .table-header {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;

        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }


    .table-header h2 {
        margin: 0;
        color: #1f2937;
        font-size: 20px;
    }


    .record-count {
        color: #6b7280;
        font-size: 14px;
    }


    .table-wrapper {
        width: 100%;
        overflow-x: auto;
    }


    table {
        width: 100%;
        min-width: 1500px;
        border-collapse: collapse;
    }


    th {
        background: #f8f9fa;
        color: #374151;

        padding: 13px 12px;

        text-align: left;
        font-size: 13px;

        border-bottom: 2px solid #e5e7eb;

        white-space: nowrap;
    }


    td {
        padding: 13px 12px;

        border-bottom: 1px solid #e5e7eb;

        font-size: 13px;
        color: #374151;

        vertical-align: top;
    }


    tbody tr:hover {
        background: #f8fafc;
    }


    .transaction-code {
        font-weight: bold;
        color: #198754;
        white-space: nowrap;
    }


    .item-name {
        font-weight: 600;
        color: #1f2937;
    }


    .small-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 3px;
    }


    /* STATUS */

    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;

        font-size: 12px;
        font-weight: 700;

        white-space: nowrap;
    }


    .status-borrowed {
        background: #dbeafe;
        color: #1d4ed8;
    }


    .status-returned {
        background: #dcfce7;
        color: #166534;
    }


    .status-overdue {
        background: #fee2e2;
        color: #b91c1c;
    }


    .status-lost {
        background: #ede9fe;
        color: #6d28d9;
    }


    /* CONDITION */

    .condition-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;

        font-size: 11px;
        font-weight: 600;

        white-space: nowrap;
    }


    .condition-excellent {
        background: #dcfce7;
        color: #166534;
    }


    .condition-good {
        background: #dbeafe;
        color: #1d4ed8;
    }


    .condition-fair {
        background: #fef3c7;
        color: #92400e;
    }


    .condition-damaged {
        background: #fee2e2;
        color: #b91c1c;
    }


    .empty-state {
        padding: 50px 20px;
        text-align: center;
        color: #6b7280;
    }


    .empty-state-icon {
        font-size: 40px;
        margin-bottom: 10px;
    }


    /* MOBILE */

    @media (max-width: 900px) {

        .transactions-page {
            padding: 15px;
        }


        .summary-grid {
            grid-template-columns:
                repeat(2, minmax(140px, 1fr));
        }


        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }


        .filter-group,
        .filter-group input,
        .filter-group select {
            width: 100%;
        }

    }


    @media (max-width: 500px) {

        .summary-grid {
            grid-template-columns: 1fr;
        }

    }

</style>


<div class="transactions-page">

    <div class="page-header">

        <div>

            <h1>
                Transaction History
            </h1>

            <p>
                View and monitor all inventory borrowing
                and return transactions.
            </p>

        </div>

    </div>


    <!-- SUMMARY CARDS -->

    <div class="summary-grid">

        <div class="summary-card">

            <h3>
                Total Transactions
            </h3>

            <div class="summary-number">
                <?= $totalTransactions ?>
            </div>

        </div>


        <div class="summary-card summary-borrowed">

            <h3>
                Borrowed
            </h3>

            <div class="summary-number">
                <?= $totalBorrowed ?>
            </div>

        </div>


        <div class="summary-card summary-returned">

            <h3>
                Returned
            </h3>

            <div class="summary-number">
                <?= $totalReturned ?>
            </div>

        </div>


        <div class="summary-card summary-overdue">

            <h3>
                Overdue
            </h3>

            <div class="summary-number">
                <?= $totalOverdue ?>
            </div>

        </div>


        <div class="summary-card summary-lost">

            <h3>
                Lost
            </h3>

            <div class="summary-number">
                <?= $totalLost ?>
            </div>

        </div>

    </div>


    <!-- FILTER -->

    <div class="filter-card">

        <form
            method="GET"
            class="filter-form"
        >

            <div class="filter-group">

                <label for="search">
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Transaction, item, borrower..."
                    value="<?= e($search) ?>"
                >

            </div>


            <div class="filter-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                >

                    <option value="">
                        All Statuses
                    </option>

                    <?php foreach ($allowedStatuses as $option): ?>

                        <option
                            value="<?= e($option) ?>"
                            <?= $status === $option ? 'selected' : '' ?>
                        >
                            <?= e($option) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                🔍 Search
            </button>


            <a
                href="transactions.php"
                class="btn btn-secondary"
            >
                Reset
            </a>

        </form>

    </div>


    <!-- TRANSACTION TABLE -->

    <div class="table-card">

        <div class="table-header">

            <h2>
                Transaction Records
            </h2>

            <span class="record-count">
                Showing <?= count($transactions) ?> record(s)
            </span>

        </div>


        <?php if (empty($transactions)): ?>

            <div class="empty-state">

                <div class="empty-state-icon">
                    📋
                </div>

                <strong>
                    No transactions found
                </strong>

                <p>
                    Borrowed and returned transactions
                    will appear here.
                </p>

            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Transaction
                            </th>

                            <th>
                                Item
                            </th>

                            <th>
                                Borrower
                            </th>

                            <th>
                                Department
                            </th>

                            <th>
                                Processed By
                            </th>

                            <th>
                                Borrowed Date
                            </th>

                            <th>
                                Due Date
                            </th>

                            <th>
                                Returned Date
                            </th>

                            <th>
                                Condition Before
                            </th>

                            <th>
                                Condition After
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Remarks
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($transactions as $transaction): ?>

                            <tr>

                                <!-- TRANSACTION -->

                                <td>

                                    <div class="transaction-code">

                                        <?= e(
                                            $transaction['transaction_code']
                                        ) ?>

                                    </div>

                                </td>


                                <!-- ITEM -->

                                <td>

                                    <div class="item-name">

                                        <?= e(
                                            $transaction['item_name']
                                        ) ?>

                                    </div>

                                    <div class="small-text">

                                        Code:
                                        <?= e(
                                            $transaction['item_code']
                                        ) ?>

                                    </div>

                                </td>


                                <!-- BORROWER -->

                                <td>

                                    <div class="item-name">

                                        <?= e(
                                            $transaction['borrower_name']
                                        ) ?>

                                    </div>

                                    <div class="small-text">

                                        <?= e(
                                            $transaction['borrower_code']
                                        ) ?>

                                    </div>

                                </td>


                                <!-- DEPARTMENT -->

                                <td>

                                    <?= e(
                                        $transaction['department_name']
                                    ) ?>

                                </td>


                                <!-- PROCESSED BY -->

                                <td>

                                    <?= e(
                                        $transaction['processed_by']
                                    ) ?>

                                </td>


                                <!-- BORROWED DATE -->

                                <td>

                                    <?= formatDateTime(
                                        $transaction['borrowed_date']
                                    ) ?>

                                </td>


                                <!-- DUE DATE -->

                                <td>

                                    <?= formatDateTime(
                                        $transaction['due_date']
                                    ) ?>

                                </td>


                                <!-- RETURNED DATE -->

                                <td>

                                    <?= formatDateTime(
                                        $transaction['returned_date']
                                    ) ?>

                                </td>


                                <!-- CONDITION BEFORE -->

                                <td>

                                    <span
                                        class="condition-badge <?= e(
                                            conditionClass(
                                                $transaction['condition_before']
                                            )
                                        ) ?>"
                                    >

                                        <?= e(
                                            $transaction['condition_before']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- CONDITION AFTER -->

                                <td>

                                    <?php if (
                                        $transaction['condition_after']
                                    ): ?>

                                        <span
                                            class="condition-badge <?= e(
                                                conditionClass(
                                                    $transaction['condition_after']
                                                )
                                            ) ?>"
                                        >

                                            <?= e(
                                                $transaction['condition_after']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="status-badge <?= e(
                                            statusClass(
                                                $transaction['status']
                                            )
                                        ) ?>"
                                    >

                                        <?= e(
                                            $transaction['status']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- REMARKS -->

                                <td>

                                    <?= $transaction['remarks']
                                        ? nl2br(
                                            e(
                                                $transaction['remarks']
                                            )
                                        )
                                        : '-'
                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
