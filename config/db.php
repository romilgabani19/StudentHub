<?php
// XAMPP defaults are for local development only. Change credentials for other environments.
$host = '127.0.0.1';
$dbname = 'studenthub';
$username = 'root';
$password = '';
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Check config/db.php and import database/studenthub.sql.');
}
