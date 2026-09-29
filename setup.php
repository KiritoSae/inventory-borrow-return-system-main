<?php
/**
 * One-time setup script.
 *
 * The `users` table is already created (with the correct schema) by
 * init-db.php from database/schema.sql on deployment. This script simply
 * inserts a test admin account into the existing table for initial
 * testing purposes.
 *
 * Access via browser at /setup.php
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>Inventory System &mdash; Setup</h2>";

try {
    // Test admin credentials.
    $fullName = 'Admin User';
    $username = 'admin';
    $plainPassword = 'password123';
    $role = 'Admin';
    $status = 'Active';

    // Only insert if the admin user doesn't already exist.
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$username]);

    if ($checkStmt->fetch()) {
        echo "<p>Admin user already exists. No changes made.</p>";
    } else {
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, username, password, role, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$fullName, $username, $hashedPassword, $role, $status]);

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
