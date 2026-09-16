<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| CREATE USER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Staff';
    $borrowerId = !empty($_POST['borrower_id'])
        ? (int) $_POST['borrower_id']
        : null;

    if ($fullName === '' || $username === '' || $password === '') {

        $error = 'Please complete all required fields.';

    } elseif (!in_array($role, ['Admin', 'Staff'], true)) {

        $error = 'Invalid user role.';

    } elseif (strlen($password) < 6) {

        $error = 'Password must be at least 6 characters.';

    } elseif ($role === 'Staff' && !$borrowerId) {

        $error = 'Please select a borrower for the Staff account.';

    } else {

        try {

            $pdo->beginTransaction();

            /*
             * Check if username already exists.
             */

            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([$username]);

            if ($stmt->fetch()) {

                throw new Exception(
                    'Username already exists.'
                );
            }


            /*
             * If Staff, check borrower.
             */

            if ($role === 'Staff') {

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        full_name,
                        user_id

                    FROM borrowers

                    WHERE id = ?

                    AND status = 'Active'

                    LIMIT 1
                ");

                $stmt->execute([$borrowerId]);

                $borrower = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$borrower) {

                    throw new Exception(
                        'Selected borrower does not exist or is inactive.'
                    );
                }

                if (!empty($borrower['user_id'])) {

                    throw new Exception(
                        'This borrower is already connected to another account.'
                    );
                }
            }


            /*
             * Create password hash.
             */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
             * Insert user.
             */

            $stmt = $pdo->prepare("
                INSERT INTO users
                (
                    full_name,
                    username,
                    password,
                    role,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    'Active'
                )
            ");

            $stmt->execute([
                $fullName,
                $username,
                $hashedPassword,
                $role
            ]);

            $newUserId = $pdo->lastInsertId();


            /*
             * Connect Staff account to borrower.
             */

            if ($role === 'Staff') {

                $stmt = $pdo->prepare("
                    UPDATE borrowers

                    SET user_id = ?

                    WHERE id = ?
                ");

                $stmt->execute([
                    $newUserId,
                    $borrowerId
                ]);
            }


            $pdo->commit();

            $message = 'User account created successfully.';

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVATE / DEACTIVATE USER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {

    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($userId > 0) {

        $stmt = $pdo->prepare("
            SELECT status
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            $newStatus =
                $user['status'] === 'Active'
                ? 'Inactive'
                : 'Active';

            $stmt = $pdo->prepare("
                UPDATE users
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $newStatus,
                $userId
            ]);

            $message = 'User status updated.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| DELETE USER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {

    $userId = (int) ($_POST['user_id'] ?? 0);

    /*
     * Prevent deleting the currently logged-in Admin.
     */

    if ($userId === (int) $_SESSION['user_id']) {

        $error = 'You cannot delete your own account.';

    } elseif ($userId > 0) {

        try {

            $pdo->beginTransaction();

            /*
             * Disconnect borrower first.
             */

            $stmt = $pdo->prepare("
                UPDATE borrowers
                SET user_id = NULL
                WHERE user_id = ?
            ");

            $stmt->execute([$userId]);


            /*
             * Delete user.
             */

            $stmt = $pdo->prepare("
                DELETE FROM users
                WHERE id = ?
            ");

            $stmt->execute([$userId]);

            $pdo->commit();

            $message = 'User account deleted successfully.';

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Unable to delete the user account.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET AVAILABLE BORROWERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        borrowers.id,
        borrowers.borrower_code,
        borrowers.full_name,
        departments.department_name

    FROM borrowers

    LEFT JOIN departments
        ON departments.id = borrowers.department_id

    WHERE borrowers.status = 'Active'

    AND borrowers.user_id IS NULL

    ORDER BY borrowers.full_name ASC
");

$availableBorrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GET USERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        users.id,
        users.full_name,
        users.username,
        users.role,
        users.status,
        users.created_at,

        borrowers.id AS borrower_id,
        borrowers.borrower_code,

        departments.department_name

    FROM users

    LEFT JOIN borrowers
        ON borrowers.user_id = users.id

    LEFT JOIN departments
        ON departments.id = borrowers.department_id

    ORDER BY users.created_at DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Management - Inventory System</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .page-container {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
        }

        .page-header p {
            margin: 0;
            color: #666;
        }

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .content-card h2 {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 7px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            padding: 11px 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 14px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .btn-submit {
            border: none;
            background: #198754;
            color: white;
            padding: 12px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-submit:hover {
            background: #157347;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px;
            border-bottom: 1px solid #eee;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f7f7f7;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-admin {
            background: #e7f1ff;
            color: #084298;
        }

        .badge-staff {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #842029;
        }

        .action-form {
            display: inline;
        }

        .btn-small {
            border: none;
            padding: 7px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 4px;
        }

        .btn-status {
            background: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 25px;
            color: #777;
        }

        @media (max-width: 768px) {

            .page-container {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

        }

    </style>

</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>


<main class="main-content">

    <div class="page-container">


        <div class="page-header">

            <h1>
                User Management
            </h1>

            <p>
                Create and manage Admin and Staff accounts.
            </p>

        </div>


        <?php if ($message): ?>

            <div class="alert alert-success">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert alert-error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- CREATE USER -->

        <div class="content-card">

            <h2>
                Create User Account
            </h2>


            <form method="POST">

                <div class="form-grid">


                    <div class="form-group">

                        <label for="full_name">
                            Full Name *
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            placeholder="Enter full name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="username">
                            Username *
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter username"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password *
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimum 6 characters"
                            minlength="6"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="role">
                            Role *
                        </label>

                        <select
                            id="role"
                            name="role"
                            required
                            onchange="toggleBorrowerField()"
                        >

                            <option value="Staff">
                                Staff
                            </option>

                            <option value="Admin">
                                Admin
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group full-width"
                        id="borrower-field"
                    >

                        <label for="borrower_id">
                            Connect to Borrower *
                        </label>

                        <select
                            id="borrower_id"
                            name="borrower_id"
                        >

                            <option value="">
                                -- Select Borrower --
                            </option>


                            <?php foreach ($availableBorrowers as $borrower): ?>

                                <option
                                    value="<?= $borrower['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $borrower['borrower_code']
                                    ) ?>

                                    -
                                    <?= htmlspecialchars(
                                        $borrower['full_name']
                                    ) ?>

                                    <?php if (!empty($borrower['department_name'])): ?>

                                        (
                                        <?= htmlspecialchars(
                                            $borrower['department_name']
                                        ) ?>
                                        )

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="full-width">

                        <button
                            type="submit"
                            name="create_user"
                            class="btn-submit"
                        >
                            Create Account
                        </button>

                    </div>


                </div>

            </form>

        </div>


        <!-- USERS TABLE -->

        <div class="content-card">

            <h2>
                System Users
            </h2>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Name
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Borrower
                            </th>

                            <th>
                                Department
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!empty($users)): ?>

                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $user['full_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $user['username']
                                    ) ?>
                                </td>


                                <td>

                                    <?php if ($user['role'] === 'Admin'): ?>

                                        <span class="badge badge-admin">
                                            Admin
                                        </span>

                                    <?php else: ?>

                                        <span class="badge badge-staff">
                                            Staff
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if ($user['borrower_id']): ?>

                                        <?= htmlspecialchars(
                                            $user['borrower_code']
                                        ) ?>

                                    <?php elseif ($user['role'] === 'Staff'): ?>

                                        <span style="color:#dc3545;">
                                            Not Connected
                                        </span>

                                    <?php else: ?>

                                        —
                                        
                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $user['department_name']
                                        ?? '—'
                                    ) ?>

                                </td>


                                <td>

                                    <?php if ($user['status'] === 'Active'): ?>

                                        <span class="badge badge-active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge badge-inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>


                                    <form
                                        method="POST"
                                        class="action-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= $user['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="toggle_status"
                                            class="btn-small btn-status"
                                        >

                                            <?= $user['status'] === 'Active'
                                                ? 'Deactivate'
                                                : 'Activate'
                                            ?>

                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        class="action-form"
                                        onsubmit="return confirm(
                                            'Are you sure you want to delete this account?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= $user['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_user"
                                            class="btn-small btn-delete"
                                        >
                                            Delete
                                        </button>

                                    </form>


                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="7"
                                class="no-data"
                            >
                                No users found.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    </div>

</main>


<script>

function toggleBorrowerField() {

    const role =
        document.getElementById('role').value;

    const borrowerField =
        document.getElementById('borrower-field');

    const borrowerSelect =
        document.getElementById('borrower_id');


    if (role === 'Staff') {

        borrowerField.style.display = 'flex';

        borrowerSelect.required = true;

    } else {

        borrowerField.style.display = 'none';

        borrowerSelect.required = false;

        borrowerSelect.value = '';

    }

}


/*
 * Run once when page loads.
 */

toggleBorrowerField();

</script>


</body>

</html>
