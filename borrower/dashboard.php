<?php

require_once __DIR__ . '/../includes/auth.php';

requireStaff();

require_once __DIR__ . '/../config/database.php';


$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];


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
        items.serial_number,
        items.location,
        items.item_condition,
        categories.category_name
    FROM items

    INNER JOIN categories
        ON items.category_id = categories.id

    WHERE items.status = 'Available'

    ORDER BY items.item_name ASC
");

$availableItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Count Available Items
|--------------------------------------------------------------------------
*/

$availableCount = count($availableItems);


/*
|--------------------------------------------------------------------------
| Transactions processed by this Staff account
|--------------------------------------------------------------------------
|
| This is temporary until we connect users to borrowers.
|
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM transactions
    WHERE processed_by = ?
");

$stmt->execute([$userId]);

$myProcessedTransactions = (int) $stmt->fetchColumn();

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="app-layout">

    <aside class="sidebar">

        <div class="sidebar-logo">

            <h2>
                📦 Inventory
            </h2>

            <p>
                Borrow & Return System
            </p>

        </div>


        <nav>

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="borrow.php">
                📦 Borrow Item
            </a>

            <a href="profile.php">
                👤 My Profile
            </a>

            <a href="../logout.php">
                🚪 Logout
            </a>

        </nav>

    </aside>


    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>
                    Welcome, <?= htmlspecialchars($fullName) ?>!
                </h1>

                <p>
                    Staff Dashboard
                </p>

            </div>

        </div>


        <!-- STAT CARDS -->

        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(220px, 1fr));
                gap:20px;
                margin-bottom:30px;
            "
        >

            <div
                class="login-card"
                style="padding:25px;"
            >

                <div style="font-size:30px;">
                    📦
                </div>

                <h2
                    style="
                        margin-top:10px;
                        color:#0f5132;
                    "
                >
                    <?= $availableCount ?>
                </h2>

                <p style="color:#718078;">
                    Available Items
                </p>

            </div>


            <div
                class="login-card"
                style="padding:25px;"
            >

                <div style="font-size:30px;">
                    📋
                </div>

                <h2
                    style="
                        margin-top:10px;
                        color:#0f5132;
                    "
                >
                    <?= $myProcessedTransactions ?>
                </h2>

                <p style="color:#718078;">
                    My Transactions
                </p>

            </div>

        </div>


        <!-- AVAILABLE ITEMS -->

        <div class="login-card">

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    margin-bottom:20px;
                    flex-wrap:wrap;
                "
            >

                <div>

                    <h2>
                        Available Inventory
                    </h2>

                    <p
                        style="
                            color:#718078;
                            margin-top:5px;
                        "
                    >
                        Items currently available for borrowing.
                    </p>

                </div>

            </div>


            <?php if (!$availableItems): ?>

                <div
                    style="
                        padding:30px;
                        text-align:center;
                        color:#718078;
                        background:#f4f7f5;
                        border-radius:10px;
                    "
                >

                    <div style="font-size:40px;">
                        📦
                    </div>

                    <p>
                        No items are currently available.
                    </p>

                </div>

            <?php else: ?>

                <div style="overflow-x:auto;">

                    <table
                        style="
                            width:100%;
                            border-collapse:collapse;
                        "
                    >

                        <thead>

                            <tr
                                style="
                                    text-align:left;
                                    border-bottom:
                                        2px solid #d7e0da;
                                "
                            >

                                <th style="padding:12px;">
                                    Item Code
                                </th>

                                <th style="padding:12px;">
                                    Item
                                </th>

                                <th style="padding:12px;">
                                    Category
                                </th>

                                <th style="padding:12px;">
                                    Condition
                                </th>

                                <th style="padding:12px;">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $availableItems
                            as $item
                        ): ?>

                            <tr
                                style="
                                    border-bottom:
                                        1px solid #e5ebe7;
                                "
                            >

                                <td style="padding:12px;">

                                    <?= htmlspecialchars(
                                        $item['item_code']
                                    ) ?>

                                </td>


                                <td style="padding:12px;">

                                    <strong>

                                        <?= htmlspecialchars(
                                            $item['item_name']
                                        ) ?>

                                    </strong>

                                    <?php if (
                                        !empty(
                                            $item['serial_number']
                                        )
                                    ): ?>

                                        <br>

                                        <small
                                            style="
                                                color:#718078;
                                            "
                                        >

                                            Serial:
                                            <?= htmlspecialchars(
                                                $item['serial_number']
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <td style="padding:12px;">

                                    <?= htmlspecialchars(
                                        $item['category_name']
                                    ) ?>

                                </td>


                                <td style="padding:12px;">

                                    <?= htmlspecialchars(
                                        $item['item_condition']
                                    ) ?>

                                </td>


                                <td style="padding:12px;">

                                    <a
                                        href="borrow.php?id=<?= $item['id'] ?>"
                                        class="btn btn-primary"
                                        style="
                                            display:inline-block;
                                            width:auto;
                                            text-decoration:none;
                                            padding:
                                                9px 14px;
                                            font-size:13px;
                                        "
                                    >

                                        Borrow

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>