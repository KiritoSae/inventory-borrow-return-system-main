<?php

/*
|--------------------------------------------------------------------------
| Database Schema Initializer
|--------------------------------------------------------------------------
|
| Reads database/schema.sql and executes every statement against the
| configured MySQL database. This is used to automatically create all
| required tables (users, borrowers, departments, categories, items,
| transactions, borrow_requests, ...) on deployment, and can also be
| triggered manually by visiting /init-db.php in the browser.
|
*/

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/database.php';

$schemaPath = __DIR__ . '/database/schema.sql';

echo "==========================================\n";
echo " Database Schema Initialization\n";
echo "==========================================\n\n";

if (!file_exists($schemaPath)) {
    http_response_code(500);
    echo "ERROR: Schema file not found at: $schemaPath\n";
    exit(1);
}

$sql = file_get_contents($schemaPath);

if ($sql === false) {
    http_response_code(500);
    echo "ERROR: Unable to read schema file at: $schemaPath\n";
    exit(1);
}

// Remove the CREATE DATABASE / USE statements since the PDO connection
// is already bound to the correct database provided by Railway.
$sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
$sql = preg_replace('/USE\s+[^\s;]+\s*;/is', '', $sql);

// Split the file into individual statements. The schema file does not
// contain semicolons inside string literals, so a simple split is safe.
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function ($statement) {
        return $statement !== '';
    }
);

$successCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($statements as $statement) {
    try {
        $pdo->exec($statement);
        $successCount++;
        echo "[OK] " . substr(str_replace("\n", ' ', $statement), 0, 80) . "...\n";
    } catch (PDOException $e) {
        // Ignore "already exists" / duplicate errors so this script can be
        // safely re-run on every deployment without failing the build.
        $message = $e->getMessage();

        if (
            stripos($message, 'already exists') !== false ||
            stripos($message, 'Duplicate entry') !== false ||
            stripos($message, 'Duplicate column name') !== false
        ) {
            $skippedCount++;
            echo "[SKIP] " . substr(str_replace("\n", ' ', $statement), 0, 80) . "... (" . $message . ")\n";
            continue;
        }

        $errorCount++;
        echo "[ERROR] " . substr(str_replace("\n", ' ', $statement), 0, 80) . "...\n";
        echo "        " . $message . "\n";
    }
}

echo "\n==========================================\n";

if ($errorCount === 0) {
    echo "SUCCESS: Database schema is up to date.\n";
    echo "Executed: $successCount statement(s). Skipped: $skippedCount (already existed).\n";
} else {
    http_response_code(500);
    echo "FAILED: $errorCount statement(s) could not be executed.\n";
    echo "Executed: $successCount statement(s). Skipped: $skippedCount.\n";
}

echo "==========================================\n";
