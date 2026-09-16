<?php require_once __DIR__ . '/../includes/auth.php'; requireLogin(); require_once __DIR__ . '/../config/database.php'; /* |-------------------------------------------------------------------------- | ADD CATEGORY |-------------------------------------------------------------------------- */ if ($_SERVER['REQUEST_METHOD'] === 'POST') { $categoryName = trim($_POST['category_name'] ?? ''); $description = trim($_POST['description'] ?? ''); if ($categoryName !== '') { $stmt = $pdo->prepare(" INSERT INTO categories (category_name, description) VALUES (?, ?) "); $stmt->execute([ $categoryName, $description ]); header("Location: categories.php?success=added"); exit; } } /* |-------------------------------------------------------------------------- | DELETE CATEGORY |-------------------------------------------------------------------------- */ if (isset($_GET['delete'])) { $id = (int) $_GET['delete']; if ($id > 0) { /* * Check if category is being used by an item. */ $stmt = $pdo->prepare(" SELECT COUNT(*) FROM items WHERE category_id = ? "); $stmt->execute([$id]); $itemCount = (int) $stmt->fetchColumn(); if ($itemCount === 0) { $stmt = $pdo->prepare(" DELETE FROM categories WHERE id = ? "); $stmt->execute([$id]); header("Location: categories.php?success=deleted"); exit; } else { header("Location: categories.php?error=used"); exit; } } } /* |-------------------------------------------------------------------------- | GET CATEGORIES |-------------------------------------------------------------------------- */ $stmt = $pdo->query(" SELECT id, category_name, description, created_at FROM categories ORDER BY id DESC "); $categories = $stmt->fetchAll(PDO::FETCH_ASSOC); ?> <?php require_once __DIR__ . '/../includes/header.php'; ?> <style> /* ========================================================= CATEGORY PAGE ========================================================= */ .category-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; } .category-page-header h1 { color: #173c29; font-size: 26px; margin-top: 5px; } .category-page-header p { color: #78847d; font-size: 14px; margin-top: 5px; } /* ========================================================= ADD CATEGORY CARD ========================================================= */ .category-form-card { background: white; border: 1px solid #e4ebe6; border-radius: 14px; padding: 22px; margin-bottom: 25px; } .category-form-card h2 { color: #173c29; font-size: 18px; margin-bottom: 5px; } .category-form-card > p { color: #78847d; font-size: 13px; margin-bottom: 18px; } .category-form { display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end; } .category-form-group label { display: block; color: #536158; font-size: 13px; font-weight: 600; margin-bottom: 7px; } .category-form-group input, .category-form-group textarea { width: 100%; padding: 11px 13px; border: 1px solid #d7e0da; border-radius: 9px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; outline: none; } .category-form-group textarea { resize: vertical; min-height: 42px; } .category-form-group input:focus, .category-form-group textarea:focus { border-color: #198754; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.10); } .add-category-btn { border: none; background: #198754; color: white; padding: 11px 20px; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; white-space: nowrap; } .add-category-btn:hover { background: #146c43; } /* ========================================================= MESSAGES ========================================================= */ .category-message { padding: 12px 15px; border-radius: 9px; margin-bottom: 20px; font-size: 14px; } .category-success { background: #d1e7dd; color: #0f5132; } .category-error { background: #f8d7da; color: #842029; } /* ========================================================= CATEGORY TABLE ========================================================= */ .category-table-card { background: white; border: 1px solid #e4ebe6; border-radius: 14px; overflow: hidden; } .category-table-header { padding: 20px 22px; border-bottom: 1px solid #edf1ee; } .category-table-header h2 { color: #173c29; font-size: 18px; margin-bottom: 4px; } .category-table-header p { color: #78847d; font-size: 13px; } .category-table { width: 100%; border-collapse: collapse; } .category-table th, .category-table td { padding: 14px 18px; text-align: left; border-bottom: 1px solid #edf1ee; font-size: 14px; } .category-table th { background: #f7faf8; color: #536158; font-size: 12px; text-transform: uppercase; } .category-table td { color: #536158; } .category-table tr:last-child td { border-bottom: none; } /* ========================================================= DELETE BUTTON ========================================================= */ .delete-category { display: inline-block; padding: 6px 11px; border-radius: 7px; background: #f8d7da; color: #842029; text-decoration: none; font-size: 12px; font-weight: 600; } .delete-category:hover { background: #f1bfc3; } /* ========================================================= EMPTY ========================================================= */ .category-empty { text-align: center; padding: 45px 20px; color: #78847d; } .category-empty-icon { font-size: 32px; margin-bottom: 10px; } .category-empty strong { display: block; color: #536158; font-size: 15px; margin-bottom: 5px; } .category-empty span { font-size: 13px; } /* ========================================================= MOBILE ========================================================= */ @media (max-width: 850px) { .category-form { grid-template-columns: 1fr; } .add-category-btn { width: 100%; } } @media (max-width: 650px) { .category-page-header { display: block; } .category-table-card { overflow-x: auto; } .category-table { min-width: 650px; } } </style> <div class="app-layout">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">


    <!-- PAGE HEADER -->

    <div class="category-page-header">

        <div>

            <span class="page-label">
                INVENTORY MANAGEMENT
            </span>

            <h1>
                Categories
            </h1>

            <p>
                Manage the categories used for your inventory items.
            </p>

        </div>

    </div>



    <!-- MESSAGES -->

    <?php if (isset($_GET['success'])): ?>

        <div class="category-message category-success">

            <?php if ($_GET['success'] === 'added'): ?>

                Category added successfully.

            <?php elseif ($_GET['success'] === 'deleted'): ?>

                Category deleted successfully.

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['error']) && $_GET['error'] === 'used'): ?>

        <div class="category-message category-error">

            This category cannot be deleted because it is currently
            being used by one or more inventory items.

        </div>

    <?php endif; ?>



    <!-- ADD CATEGORY -->

    <div class="category-form-card">

        <h2>
            Add Category
        </h2>

        <p>
            Create a new category for your inventory.
        </p>


        <form
            method="POST"
            class="category-form"
        >


            <div class="category-form-group">

                <label for="category_name">
                    Category Name
                </label>

                <input
                    type="text"
                    id="category_name"
                    name="category_name"
                    placeholder="e.g. Electronics"
                    required
                >

            </div>



            <div class="category-form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Describe this category..."
                ></textarea>

            </div>



            <button
                type="submit"
                class="add-category-btn"
            >
                + Add Category
            </button>


        </form>

    </div>



    <!-- CATEGORY LIST -->

    <div class="category-table-card">


        <div class="category-table-header">

            <h2>
                Category List
            </h2>

            <p>
                <?= count($categories) ?>
                categor<?= count($categories) === 1 ? 'y' : 'ies' ?>
                registered in the system.
            </p>

        </div>



        <?php if (empty($categories)): ?>


            <div class="category-empty">

                <div class="category-empty-icon">
                    📂
                </div>

                <strong>
                    No categories yet
                </strong>

                <span>
                    Add your first inventory category above.
                </span>

            </div>


        <?php else: ?>


            <table class="category-table">


                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Created
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($categories as $category): ?>


                    <tr>

                        <td>
                            <?= (int) $category['id'] ?>
                        </td>


                        <td>

                            <strong>
                                <?= htmlspecialchars(
                                    $category['category_name']
                                ) ?>
                            </strong>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $category['description'] ?? ''
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $category['created_at']
                            ) ?>

                        </td>


                        <td>

                            <a
                                href="categories.php?delete=<?= (int) $category['id'] ?>"
                                class="delete-category"
                                onclick="return confirm('Are you sure you want to delete this category?');"
                            >
                                Delete
                            </a>

                        </td>

                    </tr>


                <?php endforeach; ?>


                </tbody>


            </table>


        <?php endif; ?>


    </div>


</main>

</div> <?php require_once __DIR__ . '/../includes/footer.php'; ?>
