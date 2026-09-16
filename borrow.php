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

    INNER JOIN categories
        ON items.category_id = categories.id

    WHERE items.id = ?

    LIMIT 1
");

$stmt->execute([$itemId]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$item) {
    die("Inventory item not found.");
}


if ($item['status'] !== 'Available') {
    die(
        "This item is not available for borrowing."
    );
}


$borrowerStmt = $pdo->query("
    SELECT
        borrowers.id,
        borrowers.borrower_code,
        borrowers.full_name,
        borrowers.position,
        departments.department_name
    FROM borrowers

    INNER JOIN departments
        ON borrowers.department_id = departments.id

    WHERE borrowers.status = 'Active'

    ORDER BY borrowers.full_name ASC
");

$borrowers = $borrowerStmt->fetchAll(
    PDO::FETCH_ASSOC
);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">


        <div class="page-header">

            <div>

                <h1>
                    Borrow Item
                </h1>

                <p>
                    Record an inventory item borrowing transaction.
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
                        $item['item_code']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Item:
                    </strong>

                    <?= htmlspecialchars(
                        $item['item_name']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Category:
                    </strong>

                    <?= htmlspecialchars(
                        $item['category_name']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Serial Number:
                    </strong>

                    <?= htmlspecialchars(
                        $item['serial_number'] ?: '—'
                    ) ?>

                </p>


                <p>

                    <strong>
                        Location:
                    </strong>

                    <?= htmlspecialchars(
                        $item['location'] ?: '—'
                    ) ?>

                </p>


                <p>

                    <strong>
                        Condition:
                    </strong>

                    <?= htmlspecialchars(
                        $item['item_condition']
                    ) ?>

                </p>


                <p>

                    <strong>
                        Status:
                    </strong>

                    <span class="status status-available">
                        Available
                    </span>

                </p>

            </div>


            <h2>
                Borrower Information
            </h2>


            <form
                method="POST"
                action="../actions/borrow_item.php"
            >


                <input
                    type="hidden"
                    name="item_id"
                    value="<?= $item['id'] ?>"
                >


                <div class="form-group">

                    <label for="borrower_id">

                        Borrower *

                    </label>


                    <select
                        name="borrower_id"
                        id="borrower_id"
                        class="form-control"
                        required
                        onchange="showBorrowerInfo()"
                    >

                        <option value="">
                            Select borrower
                        </option>


                        <?php foreach (
                            $borrowers
                            as $borrower
                        ): ?>

                            <option
                                value="<?= $borrower['id'] ?>"
                                data-department="<?= htmlspecialchars(
                                    $borrower['department_name']
                                ) ?>"
                                data-position="<?= htmlspecialchars(
                                    $borrower['position']
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $borrower['full_name']
                                ) ?>

                                -
                                <?= htmlspecialchars(
                                    $borrower['borrower_code']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div
                    id="borrower-info"
                    style="
                        display:none;
                        background:#e8f5e9;
                        padding:15px;
                        border-radius:8px;
                        margin-bottom:20px;
                    "
                >

                    <p>

                        <strong>
                            Department:
                        </strong>

                        <span
                            id="department"
                        ></span>

                    </p>


                    <p>

                        <strong>
                            Position:
                        </strong>

                        <span
                            id="position"
                        ></span>

                    </p>

                </div>


                <div class="form-group">

                    <label for="due_date">

                        Due Date *

                    </label>


                    <input
                        type="date"
                        name="due_date"
                        id="due_date"
                        class="form-control"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="remarks">

                        Remarks

                    </label>


                    <textarea
                        name="remarks"
                        id="remarks"
                        class="form-control"
                        rows="4"
                        placeholder="Optional remarks..."
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width:auto;"
                >

                    Confirm Borrowing

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


<script>

function showBorrowerInfo() {

    const select =
        document.getElementById(
            'borrower_id'
        );

    const selected =
        select.options[
            select.selectedIndex
        ];


    const info =
        document.getElementById(
            'borrower-info'
        );


    const department =
        document.getElementById(
            'department'
        );


    const position =
        document.getElementById(
            'position'
        );


    if (!select.value) {

        info.style.display = 'none';

        return;

    }


    department.textContent =
        selected.dataset.department || '—';


    position.textContent =
        selected.dataset.position || '—';


    info.style.display = 'block';

}

</script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
