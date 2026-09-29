<?php
/**
 * One-time setup script.
 *
 * Inserts a test admin account into the existing `users` table for
 * initial testing purposes. The `users` table itself is created by
 * database/schema.sql (see init-db.php) and must already exist before
 * running this script.
 *
 * Access via browser at /setup.php
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>Inventory System &mdash; Setup</h2>";

try {
    // 1. Test admin credentials.
    $fullName = 'Admin User';
    $username = 'admin';
    $plainPassword = 'password123';
    $role = 'Admin';
    $status = 'Active';

    // 2. Only insert if the admin user doesn't already exist.
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);

    if ($checkStmt->fetch()) {
        echo "<p>Admin user already exists. No changes made.</p>";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, username, password, role, status)
            VALUES (?, ?, SHA2(?, 256), ?, ?)
        ");
        $stmt->execute([$fullName, $username, $plainPassword, $role, $status]);

        echo "<p style='color:green;'><strong>Success!</strong> Test admin user created.</p>";
        echo "<ul>";
        echo "<li><strong>Full Name:</strong> " . htmlspecialchars($fullName) . "</li>";
        echo "<li><strong>Username:</strong> " . htmlspecialchars($username) . "</li>";
        echo "<li><strong>Password:</strong> " . htmlspecialchars($plainPassword) . "</li>";
        echo "<li><strong>Role:</strong> " . htmlspecialchars($role) . "</li>";
        echo "<li><strong>Status:</strong> " . htmlspecialchars($status) . "</li>";
        echo "</ul>";
        echo "<p>You can now log in using the credentials above.</p>";
    }
} catch (PDOException $e) {
    error_log('Setup script failed: ' . $e->getMessage());
    echo "<p style='color:red;'>Setup failed. Check the deployment logs for details.</p>";
}
