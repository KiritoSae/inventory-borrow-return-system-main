<?php

$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$dbname = getenv('MYSQLDATABASE') ?: getenv('DB_DATABASE') ?: 'inventory_system';
$username = getenv('MYSQLUSER') ?: getenv('DB_USERNAME') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';

try {
	$pdo = new PDO(
		"mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
		$username,
		$password,
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		]
	);

	echo "<!-- Database connected successfully -->";
} catch (PDOException $e) {
	die('Database connection failed.');
}
