<?php

/*
|--------------------------------------------------------------------------
| Schema Auto-Migration
|--------------------------------------------------------------------------
|
| Lightweight, idempotent schema check that runs on every request after
| the database connection is established. It ensures columns that the
| application code depends on (like users.full_name) actually exist,
| even if the database was provisioned from an older schema.sql.
|
| This uses the $pdo connection created in config/database.php and is
| safe to run repeatedly - it will not error out if the table or column
| already exists.
|
*/

if (!isset($pdo) || !($pdo instanceof PDO)) {
	return;
}

try {
	// Check if the users table exists before attempting to alter it.
	$tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
	$usersTableExists = $tableCheck && $tableCheck->rowCount() > 0;

	if ($usersTableExists) {
		// Check if the full_name column already exists on the users table.
		$columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'full_name'");
		$fullNameColumnExists = $columnCheck && $columnCheck->rowCount() > 0;

		if (!$fullNameColumnExists) {
			try {
				$pdo->exec(
					"ALTER TABLE users
						ADD COLUMN full_name VARCHAR(255) NOT NULL DEFAULT '' AFTER id"
				);
			} catch (PDOException $e) {
				// Ignore errors caused by a race condition where the column
				// was added by a concurrent request between the check above
				// and this ALTER TABLE statement.
				$message = $e->getMessage();

				if (
					stripos($message, 'Duplicate column name') === false
				) {
					error_log('Schema migration failed (users.full_name): ' . $message);
				}
			}
		}
	}
} catch (PDOException $e) {
	// Never let schema migration failures take down the whole app -
	// just log the issue so it can be investigated separately.
	error_log('Schema migration check failed: ' . $e->getMessage());
}
