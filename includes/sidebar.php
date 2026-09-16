```php
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';

$role = strtolower(trim($role));

$isAdmin = ($role === 'admin' || $role === 'administrator');
$isStaff = ($role === 'staff');

?>

<aside class="sidebar">

    <div class="sidebar-header">
        <h2>Inventory</h2>
        <p>Management System</p>
    </div>

    <nav class="sidebar-nav">

        <?php if ($isAdmin): ?>

            <!-- ADMIN NAVIGATION -->

            <div class="nav-section">
                <span class="nav-title">MAIN</span>
            </div>

            <a href="../admin/dashboard.php" class="nav-link">
                <span>📊</span>
                <span>Dashboard</span>
            </a>

            <div class="nav-section">
                <span class="nav-title">MANAGEMENT</span>
            </div>

            <a href="../admin/inventory.php" class="nav-link">
                <span>📦</span>
                <span>Inventory</span>
            </a>

            <a href="../admin/borrowers.php" class="nav-link">
                <span>👥</span>
                <span>Borrowers</span>
            </a>

            <a href="../admin/departments.php" class="nav-link">
                <span>🏢</span>
                <span>Departments</span>
            </a>

            <a href="../admin/users.php" class="nav-link">
                <span>👤</span>
                <span>Users</span>
            </a>

            <div class="nav-section">
                <span class="nav-title">TRANSACTIONS</span>
            </div>

            <a href="../admin/requests.php" class="nav-link">
                <span>📋</span>
                <span>Borrow Requests</span>
            </a>

            <a href="../admin/transactions.php" class="nav-link">
                <span>🔄</span>
                <span>Transactions</span>
            </a>

            <div class="nav-section">
                <span class="nav-title">QR CODE</span>
            </div>

            <a href="../qr/scan.php" class="nav-link">
                <span>📷</span>
                <span>QR Scanner</span>
            </a>

        <?php elseif ($isStaff): ?>

            <!-- STAFF NAVIGATION -->

            <div class="nav-section">
                <span class="nav-title">MAIN</span>
            </div>

            <a href="../borrower/dashboard.php" class="nav-link">
                <span>📊</span>
                <span>Dashboard</span>
            </a>

            <div class="nav-section">
                <span class="nav-title">BORROWING</span>
            </div>

            <a href="../borrower/dashboard.php" class="nav-link">
                <span>📦</span>
                <span>Borrow / Return</span>
            </a>

            <div class="nav-section">
                <span class="nav-title">QR CODE</span>
            </div>

            <a href="../qr/scan.php" class="nav-link">
                <span>📷</span>
                <span>QR Scanner</span>
            </a>

        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">

        <div class="user-info">
            <div class="user-icon">
                👤
            </div>

            <div class="user-details">
                <strong>
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                </strong>

                <span>
                    <?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?>
                </span>
            </div>
        </div>

        <a href="../logout.php" class="logout-link">
            <span>🚪</span>
            <span>Logout</span>
        </a>

    </div>

</aside>
```
