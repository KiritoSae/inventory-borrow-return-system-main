<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$borrowers = [];
$departments = [];
$editBorrower = null;

/*
|--------------------------------------------------------------------------
| ADD BORROWER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_borrower'])) {

    $borrowerCode = trim($_POST['borrower_code'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    $allowedStatuses = ['Active', 'Inactive'];

    if (
        $borrowerCode === '' ||
        $fullName === '' ||
        $departmentId <= 0 ||
        !in_array($status, $allowedStatuses, true)
    ) {
        header("Location: borrowers.php?error=required");
        exit;
    }

    try {

        /*
        |--------------------------------------------------------------------------
        | CHECK DEPARTMENT
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM departments
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$departmentId]);

        if (!$stmt->fetch()) {
            header("Location: borrowers.php?error=department");
            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE BORROWER CODE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM borrowers
            WHERE borrower_code = ?
            LIMIT 1
        ");

        $stmt->execute([$borrowerCode]);

        if ($stmt->fetch()) {
            header("Location: borrowers.php?error=duplicate");
            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT BORROWER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO borrowers (
                borrower_code,
                full_name,
                department_id,
                position,
                contact_number,
                email,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $borrowerCode,
            $fullName,
            $departmentId,
            $position,
            $contactNumber,
            $email,
            $status
        ]);

        header("Location: borrowers.php?success=added");
        exit;

    } catch (PDOException $e) {

        header("Location: borrowers.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE BORROWER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_borrower'])) {

    $id = (int) ($_POST['id'] ?? 0);

    $borrowerCode = trim($_POST['borrower_code'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $position = trim($_POST['position'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    $allowedStatuses = ['Active', 'Inactive'];

    if (
        $id <= 0 ||
        $borrowerCode === '' ||
        $fullName === '' ||
        $departmentId <= 0 ||
        !in_array($status, $allowedStatuses, true)
    ) {
        header("Location: borrowers.php?error=required");
        exit;
    }

    try {

        /*
        |--------------------------------------------------------------------------
        | CHECK BORROWER EXISTS
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM borrowers
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            header("Location: borrowers.php?error=not_found");
            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE BORROWER CODE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT id
            FROM borrowers
            WHERE borrower_code = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $borrowerCode,
            $id
        ]);

        if ($stmt->fetch()) {
            header("Location: borrowers.php?error=duplicate");
            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE borrowers
            SET
                borrower_code = ?,
                full_name = ?,
                department_id = ?,
                position = ?,
                contact_number = ?,
                email = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $borrowerCode,
            $fullName,
            $departmentId,
            $position,
            $contactNumber,
            $email,
            $status,
            $id
        ]);

        header("Location: borrowers.php?success=updated");
        exit;

    } catch (PDOException $e) {

        header("Location: borrowers.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| DELETE BORROWER
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $deleteId = (int) $_GET['delete'];

    if ($deleteId <= 0) {
        header("Location: borrowers.php?error=invalid");
        exit;
    }

    try {

        /*
        |--------------------------------------------------------------------------
        | CHECK FOR TRANSACTION HISTORY
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM transactions
            WHERE borrower_id = ?
        ");

        $stmt->execute([$deleteId]);

        $transactionCount = (int) $stmt->fetchColumn();

        if ($transactionCount > 0) {

            header("Location: borrowers.php?error=has_transactions");
            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | DISCONNECT LINKED USER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE borrowers
            SET user_id = NULL
            WHERE id = ?
        ");

        $stmt->execute([$deleteId]);


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            DELETE FROM borrowers
            WHERE id = ?
        ");

        $stmt->execute([$deleteId]);

        header("Location: borrowers.php?success=deleted");
        exit;

    } catch (PDOException $e) {

        header("Location: borrowers.php?error=database");
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
            id,
            department_name
        FROM departments
        WHERE status = 'Active'
        ORDER BY department_name ASC
    ");

    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $departments = [];
}


/*
|--------------------------------------------------------------------------
| LOAD BORROWERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            b.id,
            b.borrower_code,
            b.full_name,
            b.department_id,
            b.position,
            b.contact_number,
            b.email,
            b.status,
            b.user_id,
            b.created_at,

            d.department_name,

            u.username

        FROM borrowers b

        LEFT JOIN departments d
            ON b.department_id = d.id

        LEFT JOIN users u
            ON b.user_id = u.id

        ORDER BY b.created_at DESC
    ");

    $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $borrowers = [];
}


/*
|--------------------------------------------------------------------------
| EDIT BORROWER
|--------------------------------------------------------------------------
*/

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    if ($editId > 0) {

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    borrower_code,
                    full_name,
                    department_id,
                    position,
                    contact_number,
                    email,
                    status
                FROM borrowers
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$editId]);

            $editBorrower = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {

            $editBorrower = null;
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

                <span class="borrower-label">
                    BORROWER MANAGEMENT
                </span>

                <h1>Borrowers</h1>

                <p>
                    Manage employees and users who can borrow inventory items.
                </p>

            </div>

            <div class="borrower-count">

                <strong>
                    <?= count($borrowers) ?>
                </strong>

                <span>
                    Registered Borrowers
                </span>

            </div>

        </div>


        <!-- SUCCESS MESSAGE -->

        <?php if (isset($_GET['success'])): ?>

            <div class="borrower-alert success-alert">

                <?php if ($_GET['success'] === 'added'): ?>

                    ✓ Borrower added successfully.

                <?php elseif ($_GET['success'] === 'updated'): ?>

                    ✓ Borrower updated successfully.

                <?php elseif ($_GET['success'] === 'deleted'): ?>

                    ✓ Borrower deleted successfully.

                <?php else: ?>

                    ✓ Operation completed successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ERROR MESSAGE -->

        <?php if (isset($_GET['error'])): ?>

            <div class="borrower-alert error-alert">

                <?php if ($_GET['error'] === 'required'): ?>

                    Please complete all required fields.

                <?php elseif ($_GET['error'] === 'duplicate'): ?>

                    Borrower code already exists.

                <?php elseif ($_GET['error'] === 'department'): ?>

                    Selected department does not exist.

                <?php elseif ($_GET['error'] === 'not_found'): ?>

                    Borrower was not found.

                <?php elseif ($_GET['error'] === 'has_transactions'): ?>

                    This borrower cannot be deleted because they have transaction history.

                <?php elseif ($_GET['error'] === 'invalid'): ?>

                    Invalid borrower information.

                <?php else: ?>

                    Unable to complete the operation.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ADD / EDIT FORM -->

        <div class="borrower-card">

            <div class="borrower-card-header">

                <h2>

                    <?= $editBorrower
                        ? 'Edit Borrower'
                        : 'Add Borrower'
                    ?>

                </h2>

                <p>

                    <?= $editBorrower
                        ? 'Update borrower information.'
                        : 'Register an employee who can borrow inventory.'
                    ?>

                </p>

            </div>


            <form method="POST" class="borrower-form">

                <?php if ($editBorrower): ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $editBorrower['id'] ?>"
                    >

                <?php endif; ?>


                <div class="borrower-grid">


                    <!-- BORROWER CODE -->

                    <div class="borrower-field">

                        <label>
                            Borrower Code *
                        </label>

                        <input
                            type="text"
                            name="borrower_code"
                            placeholder="Example: EMP-001"
                            value="<?= htmlspecialchars(
                                $editBorrower['borrower_code'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- FULL NAME -->

                    <div class="borrower-field">

                        <label>
                            Full Name *
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Example: Juan Dela Cruz"
                            value="<?= htmlspecialchars(
                                $editBorrower['full_name'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- DEPARTMENT -->

                    <div class="borrower-field">

                        <label>
                            Department *
                        </label>

                        <select
                            name="department_id"
                            required
                        >

                            <option value="">
                                Select department
                            </option>

                            <?php foreach ($departments as $department): ?>

                                <option
                                    value="<?= (int) $department['id'] ?>"
                                    <?= (
                                        isset($editBorrower['department_id']) &&
                                        (int) $editBorrower['department_id'] ===
                                        (int) $department['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $department['department_name']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- POSITION -->

                    <div class="borrower-field">

                        <label>
                            Position
                        </label>

                        <input
                            type="text"
                            name="position"
                            placeholder="Example: IT Staff"
                            value="<?= htmlspecialchars(
                                $editBorrower['position'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- CONTACT -->

                    <div class="borrower-field">

                        <label>
                            Contact Number
                        </label>

                        <input
                            type="text"
                            name="contact_number"
                            placeholder="Example: 09123456789"
                            value="<?= htmlspecialchars(
                                $editBorrower['contact_number'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="borrower-field">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="Example: employee@email.com"
                            value="<?= htmlspecialchars(
                                $editBorrower['email'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- STATUS -->

                    <div class="borrower-field">

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
                                    ($editBorrower['status'] ?? 'Active') ===
                                    'Active'
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
                                    ($editBorrower['status'] ?? '') ===
                                    'Inactive'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="borrower-buttons">

                    <?php if ($editBorrower): ?>

                        <a
                            href="borrowers.php"
                            class="borrower-cancel"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            name="update_borrower"
                            class="borrower-save"
                        >
                            Save Changes
                        </button>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="save_borrower"
                            class="borrower-save"
                        >
                            + Add Borrower
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>


        <!-- BORROWER LIST -->

        <div class="borrower-card">

            <div class="borrower-card-header">

                <h2>
                    Borrower List
                </h2>

                <p>
                    Employees registered in the inventory system.
                </p>

            </div>


            <div class="borrower-table-container">

                <table class="borrower-table">

                    <thead>

                        <tr>

                            <th>Code</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>System User</th>
                            <th>Status</th>
                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($borrowers)): ?>

                        <tr>

                            <td colspan="9">

                                <div class="borrower-empty">

                                    <div>
                                        👤
                                    </div>

                                    <strong>
                                        No borrowers registered
                                    </strong>

                                    <p>
                                        Add a borrower using the form above.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($borrowers as $borrower): ?>

                            <tr>

                                <!-- CODE -->

                                <td>

                                    <strong class="borrower-code">

                                        <?= htmlspecialchars(
                                            $borrower['borrower_code']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- NAME -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $borrower['full_name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- DEPARTMENT -->

                                <td>

                                    <?= htmlspecialchars(
                                        $borrower['department_name'] ?? '—'
                                    ) ?>

                                </td>


                                <!-- POSITION -->

                                <td>

                                    <?= htmlspecialchars(
                                        $borrower['position'] ?: '—'
                                    ) ?>

                                </td>


                                <!-- CONTACT -->

                                <td>

                                    <?= htmlspecialchars(
                                        $borrower['contact_number'] ?: '—'
                                    ) ?>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?= htmlspecialchars(
                                        $borrower['email'] ?: '—'
                                    ) ?>

                                </td>


                                <!-- USER -->

                                <td>

                                    <?php if (!empty($borrower['username'])): ?>

                                        <span class="linked-user">

                                            <?= htmlspecialchars(
                                                $borrower['username']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="not-linked">
                                            Not linked
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        $borrower['status'] === 'Active'
                                    ): ?>

                                        <span class="borrower-status active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="borrower-status inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="borrower-actions">

                                        <a
                                            href="borrowers.php?edit=<?= (int) $borrower['id'] ?>"
                                            class="edit-button"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="borrowers.php?delete=<?= (int) $borrower['id'] ?>"
                                            class="delete-button"
                                            onclick="return confirm('Are you sure you want to delete this borrower?');"
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

.borrower-label {
    display: block;
    color: #198754;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.borrower-count {
    background: white;
    border: 1px solid #e4ebe6;
    border-radius: 12px;
    padding: 12px 20px;
    min-width: 170px;
    text-align: center;
}

.borrower-count strong {
    display: block;
    color: #173c29;
    font-size: 26px;
}

.borrower-count span {
    color: #78847d;
    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| ALERTS
|--------------------------------------------------------------------------
*/

.borrower-alert {
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

.borrower-card {
    background: white;
    border: 1px solid #e4ebe6;
    border-radius: 14px;
    margin-bottom: 25px;
    overflow: hidden;
}

.borrower-card-header {
    padding: 20px 22px;
    border-bottom: 1px solid #edf1ee;
}

.borrower-card-header h2 {
    color: #173c29;
    font-size: 18px;
    margin-bottom: 5px;
}

.borrower-card-header p {
    color: #78847d;
    font-size: 13px;
}


/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

.borrower-form {
    padding: 22px;
}

.borrower-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.borrower-field label {
    display: block;
    color: #536158;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
}

.borrower-field input,
.borrower-field select {
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

.borrower-field input:focus,
.borrower-field select:focus {
    border-color: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08);
}

.borrower-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.borrower-save,
.borrower-cancel {
    border: none;
    border-radius: 8px;
    padding: 10px 17px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.borrower-save {
    background: #198754;
    color: white;
}

.borrower-save:hover {
    background: #146c43;
}

.borrower-cancel {
    background: #e9ecef;
    color: #41464b;
}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.borrower-table-container {
    width: 100%;
    overflow-x: auto;
}

.borrower-table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

.borrower-table th {
    background: #f6f9f7;
    color: #536158;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    padding: 13px 15px;
    border-bottom: 1px solid #e4ebe6;
    white-space: nowrap;
}

.borrower-table td {
    padding: 14px 15px;
    color: #3f4b44;
    font-size: 13px;
    border-bottom: 1px solid #edf1ee;
    vertical-align: middle;
}

.borrower-table tbody tr:hover {
    background: #fafcfb;
}

.borrower-code {
    color: #198754;
}


/*
|--------------------------------------------------------------------------
| LINKED USER
|--------------------------------------------------------------------------
*/

.linked-user {
    display: inline-block;
    background: #e8f5e9;
    color: #198754;
    padding: 5px 9px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.not-linked {
    color: #8a938d;
    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

.borrower-status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.borrower-status.active {
    background: #d1e7dd;
    color: #0f5132;
}

.borrower-status.inactive {
    background: #e9ecef;
    color: #495057;
}


/*
|--------------------------------------------------------------------------
| ACTIONS
|--------------------------------------------------------------------------
*/

.borrower-actions {
    display: flex;
    gap: 7px;
}

.borrower-actions a {
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
| EMPTY STATE
|--------------------------------------------------------------------------
*/

.borrower-empty {
    text-align: center;
    padding: 40px 20px;
}

.borrower-empty > div {
    font-size: 35px;
    margin-bottom: 10px;
}

.borrower-empty strong {
    display: block;
    color: #536158;
    margin-bottom: 5px;
}

.borrower-empty p {
    color: #78847d;
    font-size: 13px;
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 1000px) {

    .borrower-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 650px) {

    .borrower-grid {
        grid-template-columns: 1fr;
    }

    .borrower-buttons {
        flex-direction: column;
    }

    .borrower-save,
    .borrower-cancel {
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }

}

</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>