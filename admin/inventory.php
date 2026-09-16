<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$items = [];
$itemCount = 0;
$categoryList = [];
$editItem = null;

/*
|--------------------------------------------------------------------------
| LOAD INVENTORY ITEMS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            i.id,
            i.item_code,
            i.item_name,
            i.category_id,
            i.serial_number,
            i.description,
            i.location,
            i.item_condition,
            i.status,
            i.qr_code,
            i.created_at,
            i.updated_at,
            c.category_name
        FROM items i
        LEFT JOIN categories c
            ON i.category_id = c.id
        ORDER BY i.created_at DESC
    ");

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $itemCount = count($items);

} catch (PDOException $e) {

    $items = [];
    $itemCount = 0;
}


/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            id,
            category_name
        FROM categories
        ORDER BY category_name ASC
    ");

    $categoryList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $categoryList = [];
}


/*
|--------------------------------------------------------------------------
| EDIT ITEM
|--------------------------------------------------------------------------
*/

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    if ($editId > 0) {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    item_code,
                    item_name,
                    category_id,
                    serial_number,
                    description,
                    location,
                    item_condition,
                    status,
                    qr_code
                FROM items
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$editId]);

            $editItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {

            $editItem = null;
        }
    }
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <span class="inventory-label">
                    INVENTORY MANAGEMENT
                </span>

                <h1>Inventory</h1>

                <p>
                    Manage company equipment and inventory items.
                </p>

            </div>

            <div class="inventory-count">

                <strong>
                    <?= $itemCount ?>
                </strong>

                <span>
                    Registered Items
                </span>

            </div>

        </div>


        <!-- SUCCESS MESSAGE -->

        <?php if (isset($_GET['success'])): ?>

            <div class="inventory-alert success-alert">

                <?php if ($_GET['success'] === 'added'): ?>

                    ✓ Inventory item added successfully.

                <?php elseif ($_GET['success'] === 'updated'): ?>

                    ✓ Inventory item updated successfully.

                <?php elseif ($_GET['success'] === 'deleted'): ?>

                    ✓ Inventory item deleted successfully.

                <?php elseif ($_GET['success'] === 'borrowed'): ?>

                    ✓ Item borrowed successfully.

                <?php elseif ($_GET['success'] === 'returned'): ?>

                    ✓ Item returned successfully.

                <?php else: ?>

                    ✓ Operation completed successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if (isset($_GET['error'])): ?>

            <div class="inventory-alert error-alert">

                <?php if ($_GET['error'] === 'duplicate'): ?>

                    Item code already exists.

                <?php elseif ($_GET['error'] === 'required'): ?>

                    Please complete all required fields.

                <?php elseif ($_GET['error'] === 'invalid'): ?>

                    Invalid inventory information.

                <?php elseif ($_GET['error'] === 'not_found'): ?>

                    Inventory item was not found.

                <?php elseif ($_GET['error'] === 'borrowed'): ?>

                    This item is currently borrowed and cannot be changed or deleted.

                <?php elseif ($_GET['error'] === 'has_transactions'): ?>

                    This item cannot be deleted because it has transaction history.

                <?php else: ?>

                    Unable to complete the operation.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ADD / EDIT FORM -->

        <div class="inventory-card">

            <div class="inventory-card-header">

                <h2>

                    <?= $editItem
                        ? 'Edit Inventory Item'
                        : 'Add Inventory Item'
                    ?>

                </h2>

                <p>

                    <?= $editItem
                        ? 'Update the information of this inventory item.'
                        : 'Register a new company item or equipment.'
                    ?>

                </p>

            </div>


            <form
                method="POST"
                action="../actions/<?= $editItem
                    ? 'update_item.php'
                    : 'save_item.php'
                ?>"
                class="inventory-form"
            >

                <?php if ($editItem): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $editItem['id'] ?>"
                    >

                <?php endif; ?>


                <div class="inventory-grid">


                    <!-- ITEM CODE -->

                    <div class="inventory-field">

                        <label>
                            Item Code *
                        </label>

                        <input
                            type="text"
                            name="item_code"
                            class="form-control"
                            placeholder="Example: ITM-001"
                            value="<?= htmlspecialchars(
                                $editItem['item_code'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- ITEM NAME -->

                    <div class="inventory-field">

                        <label>
                            Item Name *
                        </label>

                        <input
                            type="text"
                            name="item_name"
                            class="form-control"
                            placeholder="Example: Laptop Computer"
                            value="<?= htmlspecialchars(
                                $editItem['item_name'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- CATEGORY -->

                    <div class="inventory-field">

                        <label>
                            Category *
                        </label>

                        <select
                            name="category_id"
                            class="form-control"
                            required
                        >

                            <option value="">
                                Select category
                            </option>

                            <?php foreach ($categoryList as $category): ?>

                                <option
                                    value="<?= (int) $category['id'] ?>"
                                    <?= (
                                        isset($editItem['category_id']) &&
                                        (int) $editItem['category_id'] ===
                                        (int) $category['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $category['category_name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- SERIAL NUMBER -->

                    <div class="inventory-field">

                        <label>
                            Serial Number
                        </label>

                        <input
                            type="text"
                            name="serial_number"
                            class="form-control"
                            placeholder="Optional"
                            value="<?= htmlspecialchars(
                                $editItem['serial_number'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- LOCATION -->

                    <div class="inventory-field">

                        <label>
                            Location
                        </label>

                        <input
                            type="text"
                            name="location"
                            class="form-control"
                            placeholder="Example: IT Office"
                            value="<?= htmlspecialchars(
                                $editItem['location'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- CONDITION -->

                    <div class="inventory-field">

                        <label>
                            Item Condition *
                        </label>

                        <select
                            name="item_condition"
                            class="form-control"
                            required
                        >

                            <option value="">
                                Select condition
                            </option>

                            <option
                                value="Excellent"
                                <?= (
                                    ($editItem['item_condition'] ?? '') ===
                                    'Excellent'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Excellent
                            </option>

                            <option
                                value="Good"
                                <?= (
                                    ($editItem['item_condition'] ?? '') ===
                                    'Good'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Good
                            </option>

                            <option
                                value="Fair"
                                <?= (
                                    ($editItem['item_condition'] ?? '') ===
                                    'Fair'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Fair
                            </option>

                            <option
                                value="Damaged"
                                <?= (
                                    ($editItem['item_condition'] ?? '') ===
                                    'Damaged'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Damaged
                            </option>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="inventory-field">

                        <label>
                            Status *
                        </label>

                        <select
                            name="status"
                            class="form-control"
                            required
                        >

                            <option
                                value="Available"
                                <?= (
                                    ($editItem['status'] ?? 'Available') ===
                                    'Available'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Available
                            </option>

                            <option
                                value="Borrowed"
                                <?= (
                                    ($editItem['status'] ?? '') ===
                                    'Borrowed'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Borrowed
                            </option>

                            <option
                                value="Maintenance"
                                <?= (
                                    ($editItem['status'] ?? '') ===
                                    'Maintenance'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Maintenance
                            </option>

                            <option
                                value="Lost"
                                <?= (
                                    ($editItem['status'] ?? '') ===
                                    'Lost'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Lost
                            </option>

                            <option
                                value="Retired"
                                <?= (
                                    ($editItem['status'] ?? '') ===
                                    'Retired'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Retired
                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="inventory-field inventory-field-full">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                            placeholder="Optional description of the item..."
                        ><?= htmlspecialchars(
                            $editItem['description'] ?? ''
                        ) ?></textarea>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="inventory-buttons">

                    <?php if ($editItem): ?>

                        <a
                            href="inventory.php"
                            class="btn inventory-cancel"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary inventory-save"
                        >
                            Save Changes
                        </button>

                    <?php else: ?>

                        <button
                            type="submit"
                            class="btn btn-primary inventory-save"
                        >
                            + Add Inventory
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>


        <!-- INVENTORY LIST -->

        <div class="inventory-card">

            <div class="inventory-card-header">

                <h2>
                    Inventory List
                </h2>

                <p>
                    All registered company items and equipment.
                </p>

            </div>


            <div class="table-container">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>Item Code</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Serial Number</th>
                            <th>Location</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>QR</th>
                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($items)): ?>

                        <tr>

                            <td colspan="9">

                                <div class="inventory-empty">

                                    <div class="inventory-empty-icon">
                                        📦
                                    </div>

                                    <strong>
                                        No inventory items yet
                                    </strong>

                                    <p>
                                        Add your first inventory item using
                                        the form above.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($items as $item): ?>

                            <tr>

                                <!-- CODE -->

                                <td>

                                    <strong class="item-code">

                                        <?= htmlspecialchars(
                                            $item['item_code']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- ITEM -->

                                <td>

                                    <?= htmlspecialchars(
                                        $item['item_name']
                                    ) ?>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $item['category_name'] ?? '—'
                                    ) ?>

                                </td>


                                <!-- SERIAL -->

                                <td>

                                    <?= htmlspecialchars(
                                        $item['serial_number'] ?: '—'
                                    ) ?>

                                </td>


                                <!-- LOCATION -->

                                <td>

                                    <?= htmlspecialchars(
                                        $item['location'] ?: '—'
                                    ) ?>

                                </td>


                                <!-- CONDITION -->

                                <td>

                                    <?= htmlspecialchars(
                                        $item['item_condition'] ?: '—'
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $statusClass = 'status-available';

                                    if ($item['status'] === 'Borrowed') {

                                        $statusClass = 'status-borrowed';

                                    } elseif (
                                        $item['status'] === 'Maintenance'
                                    ) {

                                        $statusClass = 'status-maintenance';

                                    } elseif (
                                        $item['status'] === 'Lost'
                                    ) {

                                        $statusClass = 'status-overdue';

                                    } elseif (
                                        $item['status'] === 'Retired'
                                    ) {

                                        $statusClass = 'status-overdue';

                                    }

                                    ?>

                                    <span class="status <?= $statusClass ?>">

                                        <?= htmlspecialchars(
                                            $item['status']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- QR -->

                                <td>

                                    <a
                                        href="../qr/generate.php?id=<?= $item['id'] ?>"
                                        class="btn btn-primary"
                                                                >
                                    QR Code
                                    </a>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="inventory-actions">

                                        <a
                                            href="inventory.php?edit=<?= (int) $item['id'] ?>"
                                            class="inventory-edit"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="../actions/delete_item.php?id=<?= (int) $item['id'] ?>"
                                            class="inventory-delete"
                                            onclick="return confirm('Are you sure you want to delete this inventory item?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>


<style>

/* PAGE HEADER */

.inventory-label {
    display: block;
    color: #198754;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.inventory-count {
    background: #ffffff;
    border: 1px solid #e4ebe6;
    border-radius: 12px;
    padding: 12px 20px;
    min-width: 160px;
    text-align: center;
}

.inventory-count strong {
    display: block;
    color: #173c29;
    font-size: 26px;
}

.inventory-count span {
    display: block;
    color: #78847d;
    font-size: 12px;
    margin-top: 3px;
}


/* ALERTS */

.inventory-alert {
    padding: 13px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
}

.success-alert {
    background: #d1e7dd;
    color: #0f5132;
}

.error-alert {
    background: #f8d7da;
    color: #842029;
}


/* CARDS */

.inventory-card {
    background: #ffffff;
    border: 1px solid #e4ebe6;
    border-radius: 14px;
    margin-bottom: 25px;
    overflow: hidden;
}

.inventory-card-header {
    padding: 20px 22px;
    border-bottom: 1px solid #edf1ee;
}

.inventory-card-header h2 {
    color: #173c29;
    font-size: 18px;
    margin-bottom: 5px;
}

.inventory-card-header p {
    color: #78847d;
    font-size: 13px;
}


/* FORM */

.inventory-form {
    padding: 22px;
}

.inventory-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.inventory-field label {
    display: block;
    margin-bottom: 7px;
    color: #536158;
    font-size: 13px;
    font-weight: 600;
}

.inventory-field-full {
    grid-column: 1 / -1;
}

.inventory-field textarea {
    resize: vertical;
    min-height: 90px;
}

.inventory-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.inventory-save {
    width: auto;
}

.inventory-cancel {
    background: #e9ecef;
    color: #41464b;
    text-decoration: none;
}

.inventory-cancel:hover {
    background: #dfe3e6;
}


/* ITEM CODE */

.item-code {
    color: #198754;
}


/* QR */

.inventory-qr {
    display: inline-block;
    text-decoration: none;
    background: #e8f5e9;
    color: #198754;
    border: 1px solid #c8e6d0;
    padding: 6px 11px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
}

.inventory-qr:hover {
    background: #d1e7dd;
}


/* ACTIONS */

.inventory-actions {
    display: flex;
    gap: 7px;
}

.inventory-actions a {
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
}

.inventory-edit {
    background: #d1e7dd;
    color: #0f5132;
}

.inventory-edit:hover {
    background: #b9dfce;
}

.inventory-delete {
    background: #f8d7da;
    color: #842029;
}

.inventory-delete:hover {
    background: #f1bfc4;
}


/* EMPTY STATE */

.inventory-empty {
    text-align: center;
    padding: 40px 20px;
}

.inventory-empty-icon {
    font-size: 34px;
    margin-bottom: 10px;
}

.inventory-empty strong {
    display: block;
    color: #536158;
    font-size: 15px;
    margin-bottom: 5px;
}

.inventory-empty p {
    color: #78847d;
    font-size: 13px;
}


/* MOBILE */

@media (max-width: 1100px) {

    .inventory-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 800px) {

    .inventory-card {
        overflow-x: auto;
    }

    .table-container {
        overflow-x: auto;
    }

    .data-table {
        min-width: 950px;
    }

}

@media (max-width: 650px) {

    .inventory-count {
        margin-top: 12px;
    }

    .inventory-grid {
        grid-template-columns: 1fr;
    }

    .inventory-field-full {
        grid-column: auto;
    }

    .inventory-buttons {
        flex-direction: column;
    }

    .inventory-buttons .btn {
        width: 100%;
        text-align: center;
    }

    .inventory-actions {
        flex-direction: column;
    }

}

</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>