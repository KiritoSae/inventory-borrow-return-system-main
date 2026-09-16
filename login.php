<?php

session_start();

require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Please enter your username and password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT
                users.id,
                users.full_name,
                users.username,
                users.password,
                users.role,
                users.status,

                borrowers.id AS borrower_id,
                borrowers.borrower_code

            FROM users

            LEFT JOIN borrowers
                ON borrowers.user_id = users.id

            WHERE users.username = ?

            LIMIT 1
        ");

        $stmt->execute([$username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $user &&
            $user['status'] === 'Active' &&
            password_verify($password, $user['password'])
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];

            $_SESSION['full_name'] = $user['full_name'];

            $_SESSION['username'] = $user['username'];

            $_SESSION['role'] = $user['role'];

            /*
             * Store borrower information if
             * this Staff account is connected
             * to a borrower record.
             */

            if (!empty($user['borrower_id'])) {

                $_SESSION['borrower_id'] = $user['borrower_id'];

                $_SESSION['borrower_code'] = $user['borrower_code'];

            } else {

                unset($_SESSION['borrower_id']);

                unset($_SESSION['borrower_code']);

            }


            /*
             * Redirect based on account role.
             */

            if ($user['role'] === 'Admin') {

                header("Location: admin/dashboard.php");

                exit;

            }


            if ($user['role'] === 'Staff') {

                header("Location: borrower/dashboard.php");

                exit;

            }


            $error = 'Invalid account role.';

        } else {

            $error = 'Invalid username or password.';

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - Inventory Management System</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="login-page">

    <div class="login-container">

        <div class="login-card">

            <div class="login-logo">

                <div class="logo-icon">
                    📦
                </div>

                <h1>
                    Inventory System
                </h1>

                <p>
                    Borrowing & Return Management
                </p>

            </div>


            <?php if ($error): ?>

                <div style="
                    background:#f8d7da;
                    color:#842029;
                    padding:12px;
                    border-radius:8px;
                    margin-bottom:20px;
                    font-size:14px;
                ">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action="login.php"
            >

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Sign In
                </button>

            </form>


            <div class="login-footer">
                Inventory Management System
            </div>

        </div>

    </div>

</div>

</body>

</html>
