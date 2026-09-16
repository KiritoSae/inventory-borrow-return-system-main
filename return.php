<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';


$itemId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$itemId) {

    die("Invalid inventory item.");

}


/*
 * Find the item and its current borrower.
 */

$stmt = $pdo->prepare("
    SELECT
        items.id,
        items.item_code,
        items.item_name,
        items.serial_number,
        items.location,
        items.item_condition,
        items.status,

        categories.category_name,

        transactions.id AS transaction_id,
        transactions.borrow_date,
        transactions.due_date,
        transactions.borrow_condition,
        transactions.remarks AS borrow_remarks,

        borrowers.borrower_code,
        borrowers.full_name,
        borrowers.position,

        departments.department_name

    FROM items

    INNER JOIN categories
        ON items.category_id = categories.id

    INNER JOIN transactions
        ON items.id = transactions.item_id

    INNER JOIN borrowers
        ON transactions.borrower_id = borrowers.id

    INNER JOIN departments
        ON borrowers.department_id = departments.id

    WHERE items.id = ?

    AND transactions.status = 'Borrowed'

    ORDER BY transactions.borrow_date DESC

    LIMIT 1
");


$stmt->execute([
    $itemId
]);


$data = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$data) {

    die(
        "No active borrowing transaction was found for this item."
    );

}


?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">


        <div class="page-header">

            <div>

                <h1>
                    Return Item
                </h1>

                <p>
                    Process the return of a borrowed inventory item.
                </p>

            </div>

        </div>


        <div
            class="login-card"
            style="max-width:800px;"
        >


            <h2>
                Item Information
            </h2>


            <div
                style="
                    background:#f4f7f5;
                    padding:20px;
                    border-radius:10px;
                    margin:20px 0;
                "
            >

                <p>

                    <strong>
                        Item Code:
                    </strong>

                    <?= htmlspecialchars(
                        $data['item_code']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Item:
                    </strong>

                    <?= htmlspecialchars(
                        $data['item_name']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Category:
                    </strong>

                    <?= htmlspecialchars(
                        $data['category_name']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Serial Number:
                    </strong>

                    <?= htmlspecialchars(
                        $data['serial_number'] ?: '—'
                    ) ?>

                </p>


                <p>

                    <strong>
                        Location:
                    </strong>

                    <?= htmlspecialchars(
                        $data['location'] ?: '—'
                    ) ?>

                </p>


                <p>

                    <strong>
                        Current Condition:
                    </strong>

                    <?= htmlspecialchars(
                        $data['item_condition']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Status:
                    </strong>

                    <span class="status status-borrowed">
                        Borrowed
                    </span>

                </p>

            </div>


            <h2>
                Borrower Information
            </h2>


            <div
                style="
                    background:#e8f5e9;
                    padding:20px;
                    border-radius:10px;
                    margin:20px 0;
                "
            >

                <p>

                    <strong>
                        Borrower:
                    </strong>

                    <?= htmlspecialchars(
                        $data['full_name']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Borrower ID:
                    </strong>

                    <?= htmlspecialchars(
                        $data['borrower_code']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Department:
                    </strong>

                    <?= htmlspecialchars(
                        $data['department_name']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Position:
                    </strong>

                    <?= htmlspecialchars(
                        $data['position'] ?: '—'
                    ) ?>

                </p>


                <p>

                    <strong>
                        Borrow Date:
                    </strong>

                    <?= htmlspecialchars(
                        date(
                            'M d, Y',
                            strtotime(
                                $data['borrow_date']
                            )
                        )
                    ) ?>

                </p>


                <p>

                    <strong>
                        Due Date:
                    </strong>

                    <?= htmlspecialchars(
                        date(
                            'M d, Y',
                            strtotime(
                                $data['due_date']
                            )
                        )
                    ) ?>

                </p>

            </div>


            <form
                method="POST"
                action="../actions/return.php"
            >


                <input
                    type="hidden"
                    name="transaction_id"
                    value="<?= $data['transaction_id'] ?>"
                >


                <input
                    type="hidden"
                    name="item_id"
                    value="<?= $data['id'] ?>"
                >


                <div class="form-group">

                    <label for="return_condition">

                        Condition Upon Return *

                    </label>


                    <select
                        name="return_condition"
                        id="return_condition"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select condition
                        </option>

                        <option value="Good">
                            Good
                        </option>

                        <option value="Fair">
                            Fair
                        </option>

                        <option value="Damaged">
                            Damaged
                        </option>

                        <option value="Needs Maintenance">
                            Needs Maintenance
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="return_remarks">

                        Return Remarks

                    </label>


                    <textarea
                        name="return_remarks"
                        id="return_remarks"
                        class="form-control"
                        rows="4"
                        placeholder="Describe any damage or other remarks..."
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width:auto;"
                >

                    Confirm Return

                </button>


                <a
                    href="../admin/inventory.php"
                    class="btn"
                    style="
                        width:auto;
                        text-decoration:none;
                        margin-left:10px;
                    "
                >

                    Cancel

                </a>


            </form>


        </div>


    </main>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
