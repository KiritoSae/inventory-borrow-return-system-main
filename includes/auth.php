<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Admin
|--------------------------------------------------------------------------
*/

function requireAdmin()
{
    requireLogin();

    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'Admin'
    ) {
        http_response_code(403);

        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Access Denied</title>
            <link rel='stylesheet' href='../assets/css/style.css'>
        </head>

        <body>

            <div style='
                min-height:100vh;
                display:flex;
                align-items:center;
                justify-content:center;
                background:#f4f7f5;
                padding:20px;
            '>

                <div class='login-card' style='
                    max-width:500px;
                    text-align:center;
                '>

                    <div style='font-size:50px;'>
                        🔒
                    </div>

                    <h1 style='color:#0f5132;'>
                        Access Denied
                    </h1>

                    <p style='
                        color:#718078;
                        margin:15px 0 25px;
                    '>
                        You do not have permission to access
                        this administrator page.
                    </p>

                    <a
                        href='../borrower/dashboard.php'
                        class='btn btn-primary'
                        style='
                            display:inline-block;
                            width:auto;
                            text-decoration:none;
                        '
                    >
                        Go to Dashboard
                    </a>

                </div>

            </div>

        </body>
        </html>
        ";

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Staff
|--------------------------------------------------------------------------
*/

function requireStaff()
{
    requireLogin();

    if (
        !isset($_SESSION['role']) ||
        $_SESSION['role'] !== 'Staff'
    ) {
        header("Location: ../admin/dashboard.php");
        exit;
    }
}