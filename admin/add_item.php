<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT id, category_name
    FROM categories
    ORDER BY category_name
");

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>Add Inventory Item</h1>

                <p>
                    Register a new physical item.
                </p>

            </div>

        </div>


        <div class="login-card" style="max-width:800px;">

            <form
                method="POST"
                action="../actions/save_item.php"
            >

                <div class="form-group">

                    <label for="item_code">
                        Item Code *
                    </label>

                    <input
                        type="text"
                        id="item_code"
                        name="item_code"
                        class="form-control"
                        placeholder="Example: IT-001"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="item_name">
                        Item Name *
                    </label>

                    <input
                        type="text"
                        id="item_name"
                        name="item_name"
                        class="form-control"
                        placeholder="Example: Dell Latitude Laptop"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category_id">
                        Category *
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Select category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= $category['id'] ?>"
                            >
                                <?= htmlspecialchars(
                                    $category['category_name']
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="serial_number">
                        Serial Number
                    </label>

                    <input
                        type="text"
                        id="serial_number"
                        name="serial_number"
                        class="form-control"
                        placeholder="Optional"
                    >

                </div>


                <div class="form-group">

                    <label for="location">
                        Location
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        class="form-control"
                        placeholder="Example: IT Office"
                    >

                </div>


                <div class="form-group">

                    <label for="item_condition">
                        Condition
                    </label>

                    <select
                        id="item_condition"
                        name="item_condition"
                        class="form-control"
                    >

                        <option value="Excellent">
                            Excellent
                        </option>

                        <option value="Good" selected>
                            Good
                        </option>

                        <option value="Fair">
                            Fair
                        </option>

                        <option value="Damaged">
                            Damaged
                        </option>

                    </select>

                </div>


                <div style="display:flex; gap:10px;">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Item
                    </button>

                    <a
                        href="inventory.php"
                        class="btn"
                        style="
                            background:#e9ecef;
                            text-decoration:none;
                            color:#333;
                        "
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </main>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
