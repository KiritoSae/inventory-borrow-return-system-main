<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$users = [];
$borrowers = [];


/*
|--------------------------------------------------------------------------
| ADD USER
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Staff';
    $status = $_POST['status'] ?? 'Active';
    $borrowerId = (int) ($_POST['borrower_id'] ?? 0);

    if (
        $fullName === '' ||
        $username === '' ||
        $password === '' ||
        !in_array($role, ['Admin', 'Staff'], true) ||
        !in_array($status, ['Active', 'Inactive'], true)
    ) {
        header("Location: users.php?error=required");
        exit;
    }

    try {

        /* Check username */

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            header("Location: users.php?error=duplicate");
            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF BORROWER CHECK
        |--------------------------------------------------------------------------
        */

        if ($role === 'Staff' && $borrowerId > 0) {

            $stmt = $pdo->prepare("
                SELECT id, user_id
                FROM borrowers
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$borrowerId]);

            $borrower = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$borrower) {
                header("Location: users.php?error=borrower");
                exit;
            }

            if (!empty($borrower['user_id'])) {
                header("Location: users.php?error=borrower_linked");
                exit;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE USER
        |--------------------------------------------------------------------------
        */

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            INSERT INTO users (
                full_name,
                username,
                password,
                role,
                status
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $fullName,
            $username,
            $hashedPassword,
            $role,
            $status
        ]);

        $newUserId = (int) $pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | LINK STAFF TO BORROWER
        |--------------------------------------------------------------------------
        */

        if ($role === 'Staff' && $borrowerId > 0) {

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


        header("Location: users.php?success=added");
        exit;

    } catch (PDOException $e) {

        header("Location: users.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVATE / DEACTIVATE USER
|--------------------------------------------------------------------------
*/

if (isset($_GET['toggle'])) {

    $userId = (int) $_GET['toggle'];

    if ($userId <= 0) {
        header("Location: users.php?error=invalid");
        exit;
    }

    /*
    | Prevent admin from disabling their own account.
    */

    if (
        isset($_SESSION['user_id']) &&
        $userId === (int) $_SESSION['user_id']
    ) {
        header("Location: users.php?error=self");
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            SELECT status
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header("Location: users.php?error=not_found");
            exit;
        }

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

        header("Location: users.php?success=status");
        exit;

    } catch (PDOException $e) {

        header("Location: users.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| DELETE USER
|--------------------------------------------------------------------------
*/

if (isset($_GET['delete'])) {

    $deleteId = (int) $_GET['delete'];

    if ($deleteId <= 0) {
        header("Location: users.php?error=invalid");
        exit;
    }

    /*
    | Prevent deleting current admin account.
    */

    if (
        isset($_SESSION['user_id']) &&
        $deleteId === (int) $_SESSION['user_id']
    ) {
        header("Location: users.php?error=self");
        exit;
    }

    try {

        /*
        |--------------------------------------------------------------------------
        | DISCONNECT BORROWER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE borrowers
            SET user_id = NULL
            WHERE user_id = ?
        ");

        $stmt->execute([$deleteId]);


        /*
        |--------------------------------------------------------------------------
        | DELETE USER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        $stmt->execute([$deleteId]);

        header("Location: users.php?success=deleted");
        exit;

    } catch (PDOException $e) {

        header("Location: users.php?error=database");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD UNLINKED BORROWERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            b.id,
            b.borrower_code,
            b.full_name,
            d.department_name

        FROM borrowers b

        LEFT JOIN departments d
            ON b.department_id = d.id

        WHERE b.user_id IS NULL
        AND b.status = 'Active'

        ORDER BY b.full_name ASC
    ");

    $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $borrowers = [];
}


/*
|--------------------------------------------------------------------------
| LOAD USERS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            u.id,
            u.full_name,
            u.username,
            u.role,
            u.status,
            u.created_at,

            b.id AS borrower_id,
            b.borrower_code,
            b.full_name AS borrower_name,

            d.department_name

        FROM users u

        LEFT JOIN borrowers b
            ON b.user_id = u.id

        LEFT JOIN departments d
            ON b.department_id = d.id

        ORDER BY u.created_at DESC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $users = [];
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="app-layout">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">


        <!-- HEADER -->

        <div class="page-header">

            <div>

                <span class="users-label">
                    USER MANAGEMENT
                </span>

                <h1>Users</h1>

                <p>
                    Manage administrator and staff accounts.
                </p>

            </div>

            <div class="users-count">

                <strong>
                    <?= count($users) ?>
                </strong>

                <span>
                    System Users
                </span>

            </div>

        </div>


        <!-- SUCCESS -->

        <?php if (isset($_GET['success'])): ?>

            <div class="users-alert success-alert">

                <?php if ($_GET['success'] === 'added'): ?>

                    ✓ User account created successfully.

                <?php elseif ($_GET['success'] === 'status'): ?>

                    ✓ User status updated successfully.

                <?php elseif ($_GET['success'] === 'deleted'): ?>

                    ✓ User deleted successfully.

                <?php else: ?>

                    ✓ Operation completed successfully.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if (isset($_GET['error'])): ?>

            <div class="users-alert error-alert">

                <?php if ($_GET['error'] === 'required'): ?>

                    Please complete all required fields.

                <?php elseif ($_GET['error'] === 'duplicate'): ?>

                    Username already exists.

                <?php elseif ($_GET['error'] === 'borrower'): ?>

                    Selected borrower was not found.

                <?php elseif ($_GET['error'] === 'borrower_linked'): ?>

                    This borrower is already linked to another user.

                <?php elseif ($_GET['error'] === 'self'): ?>

                    You cannot disable or delete your own account.

                <?php elseif ($_GET['error'] === 'not_found'): ?>

                    User was not found.

                <?php elseif ($_GET['error'] === 'invalid'): ?>

                    Invalid user information.

                <?php else: ?>

                    Unable to complete the operation.

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- CREATE USER -->

        <div class="users-card">

            <div class="users-card-header">

                <h2>
                    Create User Account
                </h2>

                <p>
                    Create an Admin or Staff account.
                </p>

            </div>


            <form
                method="POST"
                class="users-form"
            >

                <div class="users-grid">


                    <!-- FULL NAME -->

                    <div class="users-field">

                        <label>
                            Full Name *
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Example: Juan Dela Cruz"
                            required
                        >

                    </div>


                    <!-- USERNAME -->

                    <div class="users-field">

                        <label>
                            Username *
                        </label>

                        <input
                            type="text"
                            name="username"
                            placeholder="Example: juan123"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="users-field">

                        <label>
                            Password *
                        </label>

                        <input
                            type="password"
                            name="password"
                            placeholder="Enter password"
                            required
                        >

                    </div>


                    <!-- ROLE -->

                    <div class="users-field">

                        <label>
                            Role *
                        </label>

                        <select
                            name="role"
                            id="role"
                            onchange="toggleBorrowerField()"
                            required
                        >

                            <option value="Staff">
                                Staff
                            </option>

                            <option value="Admin">
                                Admin
                            </option>

                        </select>

                    </div>


                    <!-- BORROWER -->

                    <div
                        class="users-field"
                        id="borrowerField"
                    >

                        <label>
                            Link to Borrower
                        </label>

                        <select
                            name="borrower_id"
                        >

                            <option value="0">
                                No borrower selected
                            </option>

                            <?php foreach ($borrowers as $borrower): ?>

                                <option
                                    value="<?= (int) $borrower['id'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $borrower['full_name']
                                    ) ?>

                                    —
                                    <?= htmlspecialchars(
                                        $borrower['borrower_code']
                                    ) ?>

                                    <?php if (
                                        !empty(
                                            $borrower['department_name']
                                        )
                                    ): ?>

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


                    <!-- STATUS -->

                    <div class="users-field">

                        <label>
                            Status *
                        </label>

                        <select
                            name="status"
                            required
                        >

                            <option value="Active">
                                Active
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <div class="users-buttons">

                    <button
                        type="submit"
                        name="save_user"
                        class="save-button"
                    >
                        + Create User
                    </button>

                </div>

            </form>

        </div>


        <!-- USER LIST -->

        <div class="users-card">

            <div class="users-card-header">

                <h2>
                    System Users
                </h2>

                <p>
                    All administrator and staff accounts.
                </p>

            </div>


            <div class="users-table-container">

                <table class="users-table">

                    <thead>

                        <tr>

                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Borrower</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($users)): ?>

                        <tr>

                            <td colspan="8">

                                <div class="users-empty">

                                    <div>
                                        👤
                                    </div>

                                    <strong>
                                        No users found
                                    </strong>

                                </div>

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($users as $user): ?>

                            <tr>

                                <!-- NAME -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $user['full_name']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- USERNAME -->

                                <td>

                                    <?= htmlspecialchars(
                                        $user['username']
                                    ) ?>

                                </td>


                                <!-- ROLE -->

                                <td>

                                    <?php if (
                                        $user['role'] === 'Admin'
                                    ): ?>

                                        <span class="role admin-role">
                                            Admin
                                        </span>

                                    <?php else: ?>

                                        <span class="role staff-role">
                                            Staff
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- BORROWER -->

                                <td>

                                    <?php if (
                                        !empty($user['borrower_name'])
                                    ): ?>

                                        <span class="linked-borrower">

                                            <?= htmlspecialchars(
                                                $user['borrower_name']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="not-linked">
                                            Not linked
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- DEPARTMENT -->

                                <td>

                                    <?= htmlspecialchars(
                                        $user['department_name'] ?? '—'
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        $user['status'] === 'Active'
                                    ): ?>

                                        <span class="user-status active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="user-status inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?= htmlspecialchars(
                                        date(
                                            'M d, Y',
                                            strtotime(
                                                $user['created_at']
                                            )
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="user-actions">

                                        <?php if (
                                            (int) $user['id'] !==
                                            (int) $_SESSION['user_id']
                                        ): ?>

                                            <a
                                                href="users.php?toggle=<?= (int) $user['id'] ?>"
                                                class="toggle-button"
                                            >

                                                <?= $user['status'] === 'Active'
                                                    ? 'Disable'
                                                    : 'Activate'
                                                ?>

                                            </a>

                                            <a
                                                href="users.php?delete=<?= (int) $user['id'] ?>"
                                                class="delete-button"
                                                onclick="return confirm('Are you sure you want to delete this user?');"
                                            >
                                                Delete
                                            </a>

                                        <?php else: ?>

                                            <span class="current-user">
                                                Current Account
                                            </span>

                                        <?php endif; ?>

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


<script>

function toggleBorrowerField() {

    const role = document.getElementById('role').value;

    const borrowerField =
        document.getElementById('borrowerField');

    if (role === 'Admin') {

        borrowerField.style.display = 'none';

    } else {

        borrowerField.style.display = 'block';

    }
}

document.addEventListener(
    'DOMContentLoaded',
    toggleBorrowerField
);

</script>


<style>

.users-label {
    display: block;
    color: #198754;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.users-count {
    background: white;
    border: 1px solid #e4ebe6;
    border-radius: 12px;
    padding: 12px 20px;
    min-width: 150px;
    text-align: center;
}

.users-count strong {
    display: block;
    color: #173c29;
    font-size: 26px;
}

.users-count span {
    color: #78847d;
    font-size: 12px;
}

.users-alert {
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

.users-card {
    background: white;
    border: 1px solid #e4ebe6;
    border-radius: 14px;
    margin-bottom: 25px;
    overflow: hidden;
}

.users-card-header {
    padding: 20px 22px;
    border-bottom: 1px solid #edf1ee;
}

.users-card-header h2 {
    color: #173c29;
    font-size: 18px;
    margin-bottom: 5px;
}

.users-card-header p {
    color: #78847d;
    font-size: 13px;
}

.users-form {
    padding: 22px;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.users-field label {
    display: block;
    color: #536158;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 7px;
}

.users-field input,
.users-field select {
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

.users-field input:focus,
.users-field select:focus {
    border-color: #198754;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.08);
}

.users-buttons {
    display: flex;
    justify-content: flex-end;
    margin-top: 22px;
}

.save-button {
    border: none;
    border-radius: 8px;
    padding: 10px 17px;
    background: #198754;
    color: white;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.save-button:hover {
    background: #146c43;
}

.users-table-container {
    width: 100%;
    overflow-x: auto;
}

.users-table {
    width: 100%;
    min-width: 1000px;
    border-collapse: collapse;
}

.users-table th {
    background: #f6f9f7;
    color: #536158;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    padding: 13px 15px;
    border-bottom: 1px solid #e4ebe6;
    white-space: nowrap;
}

.users-table td {
    padding: 14px 15px;
    color: #3f4b44;
    font-size: 13px;
    border-bottom: 1px solid #edf1ee;
    vertical-align: middle;
}

.users-table tbody tr:hover {
    background: #fafcfb;
}

.role,
.user-status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.admin-role {
    background: #d1e7dd;
    color: #0f5132;
}

.staff-role {
    background: #e8f5e9;
    color: #198754;
}

.user-status.active {
    background: #d1e7dd;
    color: #0f5132;
}

.user-status.inactive {
    background: #e9ecef;
    color: #495057;
}

.linked-borrower {
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

.user-actions {
    display: flex;
    gap: 7px;
    align-items: center;
}

.toggle-button,
.delete-button {
    text-decoration: none;
    padding: 6px 10px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
}

.toggle-button {
    background: #e8f5e9;
    color: #198754;
}

.toggle-button:hover {
    background: #d1e7dd;
}

.delete-button {
    background: #f8d7da;
    color: #842029;
}

.delete-button:hover {
    background: #f1bfc4;
}

.current-user {
    color: #78847d;
    font-size: 11px;
    font-style: italic;
}

.users-empty {
    text-align: center;
    padding: 40px;
}

.users-empty > div {
    font-size: 35px;
    margin-bottom: 10px;
}

.users-empty strong {
    color: #536158;
}

@media (max-width: 900px) {

    .users-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 600px) {

    .users-grid {
        grid-template-columns: 1fr;
    }

    .users-buttons {
        justify-content: stretch;
    }

    .save-button {
        width: 100%;
    }

}

</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>