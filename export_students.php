<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_admin();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="studenthub-students.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['id', 'full_name', 'email', 'course', 'year', 'status']);
foreach ($pdo->query('SELECT id,full_name,email,course,year,status FROM students ORDER BY id') as $row) {
    fputcsv($out, $row);
}
fclose($out);
