<?php

require_once __DIR__ . '/../config/database.php';

$fullName = 'System Administrator';
$username = 'admin';
$password = 'ChangeThisPassword123!';
$role = 'Admin';

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare("
    INSERT INTO users
    (full_name, username, password, role)
    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    $fullName,
    $username,
    $hashedPassword,
    $role
]);

echo "Administrator account created.";
