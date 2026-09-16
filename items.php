<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';


// ================================
// ADD ITEM
// ================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $itemCode = trim($_POST['item_code'] ?? '');
    $itemName = trim($_POST['item_name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $serialNumber = trim($_POST['serial_number'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $itemCondition = trim($_POST['item_condition'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');
    $qrCode = trim($_POST['qr_code'] ?? '');

    if ($itemCode === '' || $itemName === '' || $categoryId <= 0) {

        header('Location: items.php?error=required');
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO items
            (
                item_code,
                item_name,
                category_id,
                serial_number,
                description,
                location,
                item_condition,
                status,
                qr_code
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $itemCode,
            $itemName,
            $categoryId,
            $serialNumber,
            $description,
            $location,
            $itemCondition,
            $status,
            $qrCode
        ]);

        header('Location: items.php?success=added');
        exit;

    } catch (PDOException $e) {

        if ($e->getCode() == 23000) {

            header('Location: items.php?error=duplicate');
            exit;
        }

        die("Database error: " . $e->getMessage());
    }
}


// ================================
// DELETE ITEM
// ================================

if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    if ($id > 0) {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM items
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            header('Location: items.php?success=deleted');
            exit;

        } catch (PDOException $e) {

            header('Location: items.php?error=delete');
            exit;
        }
    }
}


// ================================
// GET CATEGORIES
// ================================

$stmt = $pdo->query("
    SELECT id, category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ================================
// SEARCH
// ================================

$search = trim($_GET['search'] ?? '');

$statusFilter = trim($_GET['status'] ?? '');


// ================================
// GET ITEMS
// ================================

$sql = "
    SELECT
        items.*,
        categories.category_name

    FROM items

    LEFT JOIN categories
        ON categories.id = items.category_id

    WHERE 1 = 1
";

$params = [];


if ($search !== '') {

    $sql .= "
        AND (
            items.item_code LIKE ?
            OR items.item_name LIKE ?
            OR items.serial_number LIKE ?
            OR items.location LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


if ($statusFilter !== '') {

    $sql .= "
        AND items.status = ?
    ";

    $params[] = $statusFilter;
}


$sql .= "
    ORDER BY items.id DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


    <main class="main-content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <span class="page-label">
                    INVENTORY MANAGEMENT
                </span>

                <h1>
                    Inventory
                </h1>

                <p>
                    Manage all items in your inventory.
                </p>

            </div>

        </div>


        <!-- MESSAGES -->

        <?php if (isset($_GET['success'])): ?>

            <div class="inventory-message success-message">

                <?php if ($_GET['success'] === 'added'): ?>

                    Item added successfully.

                <?php elseif ($_GET['success'] === 'deleted'): ?>

                    Item deleted successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET['error'])): ?>

            <div class="inventory-message error-message">

                <?php if ($_GET['error'] === 'required'): ?>

                    Please fill in the required fields.

                <?php elseif ($_GET['error'] === 'duplicate'): ?>

                    That item code already exists.

                <?php elseif ($_GET['error'] === 'delete'): ?>

                    This item cannot be deleted.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ADD ITEM -->

        <div class="inventory-form-card">

            <h2>
                Add Inventory Item
            </h2>

            <p>
                Register a new item in the inventory.
            </p>


            <form method="POST">


                <div class="inventory-form-grid">


                    <div class="form-group">

                        <label>
                            Item Code *
                        </label>

                        <input
                            type="text"
                            name="item_code"
                            placeholder="ITM-001"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Item Name *
                        </label>

                        <input
                            type="text"
                            name="item_name"
                            placeholder="Laptop"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Category *
                        </label>

                        <select
                            name="category_id"
                            required
                        >

                            <option value="">
                                Select category
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option value="<?= $category['id'] ?>">

                                    <?= htmlspecialchars(
                                        $category['category_name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Serial Number
                        </label>

                        <input
                            type="text"
                            name="serial_number"
                            placeholder="SN-12345"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            placeholder="Computer Laboratory"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Condition
                        </label>

                        <select name="item_condition">

                            <option value="">
                                Select condition
                            </option>

                            <option value="New">
                                New
                            </option>

                            <option value="Good">
                                Good
                            </option>

                            <option value="Fair">
                                Fair
                            </option>

                            <option value="Poor">
                                Poor
                            </option>

                            <option value="Damaged">
                                Damaged
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Status
                        </label>

                        <select name="status">

                            <option value="Available">
                                Available
                            </option>

                            <option value="Borrowed">
                                Borrowed
                            </option>

                            <option value="Maintenance">
                                Maintenance
                            </option>

                            <option value="Lost">
                                Lost
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            QR Code
                        </label>

                        <input
                            type="text"
                            name="qr_code"
                            placeholder="Optional"
                        >

                    </div>


                    <div class="form-group full-width">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            placeholder="Item description..."
                        ></textarea>

                    </div>


                </div>


                <div class="inventory-form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary inventory-add-button"
                    >
                        + Add Item
                    </button>

                </div>


            </form>

        </div>


        <!-- INVENTORY LIST -->

        <div class="table-container inventory-table-card">


            <div class="inventory-table-header">

                <div>

                    <h2>
                        Inventory List
                    </h2>

                    <p>
                        <?= count($items) ?> item(s)
                    </p>

                </div>


                <form
                    method="GET"
                    class="inventory-filter"
                >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search..."
                        value="<?= htmlspecialchars($search) ?>"
                    >


                    <select name="status">

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="Available"
                            <?= $statusFilter === 'Available'
                                ? 'selected'
                                : '' ?>
                        >
                            Available
                        </option>

                        <option
                            value="Borrowed"
                            <?= $statusFilter === 'Borrowed'
                                ? 'selected'
                                : '' ?>
                        >
                            Borrowed
                        </option>

                        <option
                            value="Maintenance"
                            <?= $statusFilter === 'Maintenance'
                                ? 'selected'
                                : '' ?>
                        >
                            Maintenance
                        </option>

                        <option
                            value="Lost"
                            <?= $statusFilter === 'Lost'
                                ? 'selected'
                                : '' ?>
                        >
                            Lost
                        </option>

                    </select>


                    <button type="submit">
                        Filter
                    </button>

                </form>

            </div>


            <?php if (empty($items)): ?>

                <div class="inventory-empty">

                    <div>
                        📦
                    </div>

                    <strong>
                        No inventory items yet
                    </strong>

                    <p>
                        Add your first item using the form above.
                    </p>

                </div>

            <?php else: ?>


                <div style="overflow-x:auto;">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>
                                    Code
                                </th>

                                <th>
                                    Item
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Serial Number
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Condition
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($items as $item): ?>

                            <tr>

                                <td>

                                    <strong style="color:#198754;">
                                        <?= htmlspecialchars(
                                            $item['item_code']
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $item['item_name']
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $item['category_name']
                                        ?? 'Uncategorized'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $item['serial_number']
                                        ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $item['location']
                                        ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $item['item_condition']
                                        ?? '-'
                                    ) ?>

                                </td>


                                <td>

                                    <?php

                                    $status = $item['status'];

                                    $statusClass =
                                        strtolower($status);

                                    ?>

                                    <span
                                        class="status status-<?= htmlspecialchars(
                                            $statusClass
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars($status) ?>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="items.php?delete=<?= $item['id'] ?>"
                                        onclick="return confirm('Delete this item?');"
                                        style="
                                            color:#dc3545;
                                            text-decoration:none;
                                            font-size:13px;
                                            font-weight:600;
                                        "
                                    >
                                        Delete
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


<style>

/* ================================
   INVENTORY
================================ */

.inventory-form-card {
    background: #ffffff;
    border: 1px solid #e4ebe6;
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 25px;
}

.inventory-form-card h2 {
    color: #173c29;
    font-size: 18px;
    margin-bottom: 5px;
}

.inventory-form-card > p {
    color: #78847d;
    font-size: 13px;
    margin-bottom: 20px;
}

.inventory-form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.inventory-form-grid .form-group {
    margin-bottom: 0;
}

.inventory-form-grid .form-group.full-width {
    grid-column: 1 / -1;
}

.inventory-form-grid input,
.inventory-form-grid select,
.inventory-form-grid textarea {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid #d7e0da;
    border-radius: 9px;
    font-size: 14px;
    outline: none;
    font-family: Arial, Helvetica, sans-serif;
    background: #ffffff;
}

.inventory-form-grid textarea {
    min-height: 80px;
    resize: vertical;
}

.inventory-form-grid input:focus,
.inventory-form-grid select:focus,
.inventory-form-grid textarea:focus {
    border-color: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.10);
}

.inventory-form-actions {
    margin-top: 18px;
    display: flex;
    justify-content: flex-end;
}

.inventory-add-button {
    width: auto;
    padding: 11px 20px;
}

.inventory-table-card {
    overflow: hidden;
}

.inventory-table-header {
    padding: 20px 22px;
    border-bottom: 1px solid #edf1ee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.inventory-table-header h2 {
    color: #173c29;
    font-size: 18px;
    margin-bottom: 4px;
}

.inventory-table-header p {
    color: #78847d;
    font-size: 13px;
}

.inventory-filter {
    display: flex;
    gap: 8px;
}

.inventory-filter input,
.inventory-filter select {
    padding: 9px 11px;
    border: 1px solid #d7e0da;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
}

.inventory-filter button {
    border: none;
    background: #198754;
    color: #ffffff;
    border-radius: 8px;
    padding: 9px 14px;
    cursor: pointer;
    font-weight: 600;
}

.inventory-message {
    padding: 12px 15px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 14px;
}

.success-message {
    background: #d1e7dd;
    color: #0f5132;
}

.error-message {
    background: #f8d7da;
    color: #842029;
}

.inventory-empty {
    text-align: center;
    padding: 45px 20px;
}

.inventory-empty div {
    font-size: 35px;
    margin-bottom: 10px;
}

.inventory-empty strong {
    display: block;
    color: #536158;
    font-size: 15px;
    margin-bottom: 5px;
}

.inventory-empty p {
    color: #8a958e;
    font-size: 13px;
}

.status-available {
    background: #d1e7dd;
    color: #0f5132;
}

.status-borrowed {
    background: #fff3cd;
    color: #856404;
}

.status-maintenance {
    background: #e2e3e5;
    color: #41464b;
}

.status-lost {
    background: #f8d7da;
    color: #842029;
}

@media (max-width: 900px) {

    .inventory-form-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 650px) {

    .inventory-form-grid {
        grid-template-columns: 1fr;
    }

    .inventory-form-grid .form-group.full-width {
        grid-column: auto;
    }

    .inventory-table-header {
        display: block;
    }

    .inventory-filter {
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .inventory-filter input {
        width: 100%;
    }

}

</style>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
