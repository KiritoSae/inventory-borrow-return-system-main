<?php
/**
 * One-time setup script.
 *
 * Creates the `users` table (if it does not already exist) and inserts
 * a test admin account for initial testing purposes.
 *
 * Access via browser at /setup.php
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>Inventory System &mdash; Setup</h2>";

try {
    // 1. Create the users table if it doesn't already exist.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'staff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    echo "<p>Users table checked/created successfully.</p>";

    // 2. Test admin credentials.
    $username = 'admin';
    $email = 'admin@test.com';
    $plainPassword = 'password123';
    $role = 'admin';

    // 3. Only insert if the admin user doesn't already exist.
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->execute([$username, $email]);

    if ($checkStmt->fetch()) {
        echo "<p>Admin user already exists. No changes made.</p>";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role, created_at)
            VALUES (?, ?, SHA2(?, 256), ?, NOW())
        ");
        $stmt->execute([$username, $email, $plainPassword, $role]);

        echo "<p style='color:green;'><strong>Success!</strong> Test admin user created.</p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> " . htmlspecialchars($username) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>";
        echo "<li><strong>Password:</strong> " . htmlspecialchars($plainPassword) . "</li>";
        echo "<li><strong>Role:</strong> " . htmlspecialchars($role) . "</li>";
        echo "</ul>";
        echo "<p>You can now log in using the credentials above.</p>";
    }
} catch (PDOException $e) {
    error_log('Setup script failed: ' . $e->getMessage());
    echo "<p style='color:red;'>Setup failed. Check the deployment logs for details.</p>";
}
