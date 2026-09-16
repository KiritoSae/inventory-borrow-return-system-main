<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$departments = [];
$editDepartment = null;


/*
|--------------------------------------------------------------------------
| ADD DEPARTMENT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_department'])) {

    $departmentName = trim($_POST['department_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if (
        $departmentName === '' ||
        !in_array($status, ['Active', 'Inactive'], true)
    ) {
        header("Location: departments.php?error=required");
        exit;
    }

    try {

        /* Check duplicate */

        $stmt = $pdo->prepare("
            SELECT id
            FROM departments
            WHERE department_name = ?
            LIMIT 1
        ");

        $stmt->execute([$departmentName]);

        if ($stmt->fetch()) {
            header("Location: departments.php?error=duplicate");
            exit;
        }


        /* Insert */

        $stmt = $pdo->prepare("
            INSERT INTO departments (
                department_name,
                description,
                status
            )
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $departmentName,
            $description,
            $status
        ]);

        header("Location: departments.php?success=added");
        exit;

    } catch (PDOException $e) {

        header("Location: departments.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE DEPARTMENT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_department'])) {

    $id = (int) ($_POST['id'] ?? 0);

    $departmentName = trim($_POST['department_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if (
        $id <= 0 ||
        $departmentName === '' ||
        !in_array($status, ['Active', 'Inactive'], true)
    ) {
        header("Location: departments.php?error=required");
        exit;
    }

    try {

        /* Check department exists */

        $stmt = $pdo->prepare("
            SELECT id
            FROM departments
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            header("Location: departments.php?error=not_found");
            exit;
        }


        /* Check duplicate name */

        $stmt = $pdo->prepare("
            SELECT id
            FROM departments
            WHERE department_name = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $departmentName,
            $id
        ]);

        if ($stmt->fetch()) {
            header("Location: departments.php?error=duplicate");
            exit;
        }


        /* Update */

        $stmt = $pdo->prepare("
            UPDATE departments
            SET
                department_name = ?,
                description = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $departmentName,
            $description,
            $status,
            $id
        ]);

        header("Location: departments.php?success=updated");
        exit;

    } catch (PDOException $e) {

        header("Location: departments.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| DELETE DEPARTMENT
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $deleteId = (int) $_GET['delete'];

    if ($deleteId <= 0) {
        header("Location: departments.php?error=invalid");
        exit;
    }

    try {

        /*
        | Check if borrowers are using this department.
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM borrowers
            WHERE department_id = ?
        ");

        $stmt->execute([$deleteId]);

        $borrowerCount = (int) $stmt->fetchColumn();

        if ($borrowerCount > 0) {

            header("Location: departments.php?error=has_borrowers");
            exit;
        }


        /*
        | Delete department.
        */

        $stmt = $pdo->prepare("
            DELETE FROM departments
            WHERE id = ?
        ");

        $stmt->execute([$deleteId]);

        header("Location: departments.php?success=deleted");
        exit;

    } catch (PDOException $e) {

        header("Location: departments.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD DEPARTMENTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            d.id,
            d.department_name,
            d.description,
            d.status,
            d.created_at,

            COUNT(b.id) AS borrower_count

        FROM departments d

        LEFT JOIN borrowers b
            ON d.id = b.department_id

        GROUP BY
            d.id,
            d.department_name,
            d.description,
            d.status,
            d.created_at

        ORDER BY d.department_name ASC
    ");

    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $departments = [];
}


/*
|--------------------------------------------------------------------------
| LOAD DEPARTMENT FOR EDIT
|--------------------------------------------------------------------------
*/

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    if ($editId > 0) {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    department_name,
                    description,
                    status
                FROM departments
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$editId]);

            $editDepartment =
                $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {

            $editDepartment = null;
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

                <span class="department-label">
                    DEPARTMENT MANAGEMENT
                </span>

                <h1>Departments</h1>

                <p>
                    Manage departments used by inventory borrowers.
                </p>

            </div>


            <div class="department-count">

                <strong>
                    <?= count($departments) ?>
                </strong>

                <span>
                    Departments
                </span>

            </div>

        </div>


        <!-- SUCCESS -->

        <?php if (isset($_GET['success'])): ?>

            <div class="department-alert success-alert">

                <?php if ($_GET['success'] === 'added'): ?>

                    ✓ Department added successfully.

                <?php elseif ($_GET['success'] === 'updated'): ?>

                    ✓ Department updated successfully.

                <?php elseif ($_GET['success'] === 'deleted'): ?>

                    ✓ Department deleted successfully.

                <?php else: ?>

                    ✓ Operation completed successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if (isset($_GET['error'])): ?>

            <div class="department-alert error-alert">

                <?php if ($_GET['error'] === 'required'): ?>

                    Please complete all required fields.

                <?php elseif ($_GET['error'] === 'duplicate'): ?>

                    Department name already exists.

                <?php elseif ($_GET['error'] === 'not_found'): ?>

                    Department was not found.

                <?php elseif ($_GET['error'] === 'has_borrowers'): ?>

                    This department cannot be deleted because borrowers are assigned to it.

                <?php elseif ($_GET['error'] === 'invalid'): ?>

                    Invalid department information.

                <?php else: ?>

                    Unable to complete the operation.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ADD / EDIT -->

        <div class="department-card">

            <div class="department-card-header">

                <h2>

                    <?= $editDepartment
                        ? 'Edit Department'
                        : 'Add Department'
                    ?>

                </h2>

                <p>

                    <?= $editDepartment
                        ? 'Update department information.'
                        : 'Create a department for your organization.'
                    ?>

                </p>

            </div>


            <form
                method="POST"
                class="department-form"
            >

                <?php if ($editDepartment): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $editDepartment['id'] ?>"
                    >

                <?php endif; ?>


                <div class="department-grid">


                    <!-- NAME -->

                    <div class="department-field">

                        <label>
                            Department Name *
                        </label>

                        <input
                            type="text"
                            name="department_name"
                            placeholder="Example: Information Technology"
                            value="<?= htmlspecialchars(
                                $editDepartment['department_name'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="department-field">

                        <label>
                            Status *
                        </label>

                        <select
                            name="status"
                            required
                        >

                            <option
                                value="Active"
                                <?= (
                                    ($editDepartment['status'] ?? 'Active')
                                    === 'Active'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= (
                                    ($editDepartment['status'] ?? '')
                                    === 'Inactive'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="department-field full-field">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Optional department description..."
                        ><?= htmlspecialchars(
                            $editDepartment['description'] ?? ''
                        ) ?></textarea>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="department-buttons">

                    <?php if ($editDepartment): ?>

                        <a
                            href="departments.php"
                            class="cancel-button"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            name="update_department"
                            class="save-button"
                        >
                            Save Changes
                        </button>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="save_department"
                            class="save-button"
                        >
                            + Add Department
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>


        <!-- DEPARTMENT LIST -->

        <div class="department-card">

            <div class="department-card-header">

                <h2>
                    Department List
                </h2>

                <p>
                    All departments registered in the system.
                </p>

            </div>


            <div class="department-table-container">

                <table class="department-table">

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Department</th>
                            <th>Description</th>
                            <th>Borrowers</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($departments)): ?>

                        <tr>

                            <td colspan="7">

                                <div class="department-empty">

                                    <div class="empty-icon">
                                        🏢
                                    </div>

                                    <strong>
                                        No departments registered
                                    </strong>

                                    <p>
                                        Add a department using the form above.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($departments as $department): ?>

                            <tr>

                                <td>

                                    #<?= (int) $department['id'] ?>

                                </td>


                                <td>

                                    <strong class="department-name">

                                        <?= htmlspecialchars(
                                            $department['department_name']
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $department['description'] ?: '—'
                                    ) ?>

                                </td>


                                <td>

                                    <span class="borrower-number">

                                        <?= (int) $department['borrower_count'] ?>

                                    </span>

                                </td>


                                <td>

                                    <?php if (
                                        $department['status'] === 'Active'
                                    ): ?>

                                        <span class="department-status active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="department-status inactive">
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


                                <td>

                                    <div class="department-actions">

                                        <a
                                            href="departments.php?edit=<?= (int) $department['id'] ?>"
                                            class="edit-button"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="departments.php?delete=<?= (int) $department['id'] ?>"
                                            class="delete-button"
                                            onclick="return confirm('Are you sure you want to delete this department?');"
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

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.department-label {
    display: block;
    color: #198754;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.department-count {
    background: white;
    border: 1px solid #e4ebe6;
    border-radius: 12px;
    padding: 12px 20px;
    min-width: 150px;
    text-align: center;
}

.department-count strong {
    display: block;
    color: #173c29;
    font-size: 26px;
}

.department-count span {
    color: #78847d;
    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| ALERTS
|--------------------------------------------------------------------------
*/

.department-alert {
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


/*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/

.department-card {
    background: white;
    border: 1px solid #e4ebe6;
    border-radius: 14px;
    margin-bottom: 25px;
    overflow: hidden;
}

.department-card-header {
    padding: 20px 22px;
    border-bottom: 1px solid #edf1ee;
}

.department-card-header h2 {
    color: #173c29;
    font-size: 18px;
    margin-bottom: 5px;
}

.department-card-header p {
    color: #78847d;
    font-size: 13px;
}


/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

.department-form {
    padding: 22px;
}

.department-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 18px;
}

.full-field {
    grid-column: 1 / -1;
}

.department-field label {
    display: block;
    color: #536158;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
}

.department-field input,
.department-field select,
.department-field textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 12px;
    border: 1px solid #d9e2dc;
    border-radius: 8px;
    background: white;
    color: #26332b;
    font-size: 14px;
    outline: none;
}

.department-field textarea {
    resize: vertical;
}

.department-field input:focus,
.department-field select:focus,
.department-field textarea:focus {
    border-color: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08);
}

.department-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.save-button,
.cancel-button {
    border: none;
    border-radius: 8px;
    padding: 10px 17px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.save-button {
    background: #198754;
    color: white;
}

.save-button:hover {
    background: #146c43;
}

.cancel-button {
    background: #e9ecef;
    color: #41464b;
}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.department-table-container {
    width: 100%;
    overflow-x: auto;
}

.department-table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

.department-table th {
    background: #f6f9f7;
    color: #536158;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    padding: 13px 15px;
    border-bottom: 1px solid #e4ebe6;
    white-space: nowrap;
}

.department-table td {
    padding: 14px 15px;
    color: #3f4b44;
    font-size: 13px;
    border-bottom: 1px solid #edf1ee;
    vertical-align: middle;
}

.department-table tbody tr:hover {
    background: #fafcfb;
}

.department-name {
    color: #198754;
}


/*
|--------------------------------------------------------------------------
| BORROWER COUNT
|--------------------------------------------------------------------------
*/

.borrower-number {
    display: inline-block;
    min-width: 28px;
    text-align: center;
    padding: 5px 8px;
    background: #e8f5e9;
    color: #198754;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

.department-status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.department-status.active {
    background: #d1e7dd;
    color: #0f5132;
}

.department-status.inactive {
    background: #e9ecef;
    color: #495057;
}


/*
|--------------------------------------------------------------------------
| ACTIONS
|--------------------------------------------------------------------------
*/

.department-actions {
    display: flex;
    gap: 7px;
}

.department-actions a {
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
}

.edit-button {
    background: #d1e7dd;
    color: #0f5132;
}

.edit-button:hover {
    background: #b9dfce;
}

.delete-button {
    background: #f8d7da;
    color: #842029;
}

.delete-button:hover {
    background: #f1bfc4;
}


/*
|--------------------------------------------------------------------------
| EMPTY
|--------------------------------------------------------------------------
*/

.department-empty {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    font-size: 35px;
    margin-bottom: 10px;
}

.department-empty strong {
    display: block;
    color: #536158;
    margin-bottom: 5px;
}

.department-empty p {
    color: #78847d;
    font-size: 13px;
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 700px) {

    .department-grid {
        grid-template-columns: 1fr;
    }

    .full-field {
        grid-column: auto;
    }

    .department-buttons {
        flex-direction: column;
    }

    .save-button,
    .cancel-button {
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }

}

</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>