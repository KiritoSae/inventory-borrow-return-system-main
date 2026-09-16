<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ../admin/departments.php");
    exit;

}

$departmentName = trim(
    $_POST['department_name'] ?? ''
);

$description = trim(
    $_POST['description'] ?? ''
);


if ($departmentName === '') {

    die("Department name is required.");

}


try {

    $stmt = $pdo->prepare("
        INSERT INTO departments
        (
            department_name,
            description
        )
        VALUES
        (
            ?,
            ?
        )
    ");

    $stmt->execute([
        $departmentName,
        $description ?: null
    ]);

    header("Location: ../admin/departments.php");
    exit;

} catch (PDOException $e) {

    if ($e->getCode() === '23000') {

        die(
            "This department already exists."
        );

    }

    die(
        "Unable to create department."
    );

}
