<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT
        id,
        department_name,
        description,
        status,
        created_at
    FROM departments
    ORDER BY department_name ASC
");

$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="page-header">

            <div>

                <h1>Departments</h1>

                <p>
                    Manage company departments.
                </p>

            </div>

        </div>


        <div
            class="login-card"
            style="max-width:800px; margin-bottom:25px;"
        >

            <h2 style="margin-bottom:20px;">
                Add Department
            </h2>

            <form
                method="POST"
                action="../actions/save_department.php"
            >

                <div class="form-group">

                    <label for="department_name">
                        Department Name *
                    </label>

                    <input
                        type="text"
                        id="department_name"
                        name="department_name"
                        class="form-control"
                        placeholder="Example: Information Technology"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <input
                        type="text"
                        id="description"
                        name="description"
                        class="form-control"
                        placeholder="Optional"
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width:auto;"
                >
                    Add Department
                </button>

            </form>

        </div>


        <div class="table-container">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>
                            Department
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Created
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($departments) > 0): ?>

                    <?php foreach ($departments as $department): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        $department['department_name']
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $department['description']
                                    ?: '—'
                                ) ?>
                            </td>

                            <td>

                                <?php if (
                                    $department['status'] === 'Active'
                                ): ?>

                                    <span class="status status-available">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status status-maintenance">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'M d, Y',
                                        strtotime(
                                            $department['created_at']
                                        )
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="4"
                            style="text-align:center;"
                        >
                            No departments found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
