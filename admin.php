<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_admin();
$studentCount = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$eventCount = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$latest = $pdo->query('SELECT name,email,created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();
$logs = $pdo->query('SELECT action,details,created_at FROM audit_logs ORDER BY id DESC LIMIT 8')->fetchAll();
?>
<header class="page-heading">
    <p class="eyebrow">ADMINISTRATION</p>
    <h1>Admin dashboard</h1>
    <p>Visible only to administrator accounts.</p>
</header>
<div class="card-grid">
    <article class="feature-card">
        <p class="muted">Users</p>
        <h2><?= $userCount ?></h2>
    </article>
    <article class="feature-card">
        <p class="muted">Students</p>
        <h2><?= $studentCount ?></h2>
    </article>
    <article class="feature-card">
        <p class="muted">Events</p>
        <h2><?= $eventCount ?></h2>
    </article>
</div>
<section class="panel">
    <h2>Management</h2>
    <div class="button-row"><a class="button" href="students.php">Manage students</a><a class="button" href="admin_events.php">Manage events</a><a class="button button-secondary" href="export_students.php">Export students CSV</a></div>
</section>
<section class="panel">
    <h2>Latest registrations</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody><?php foreach ($latest as $row): ?><tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['created_at']) ?></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Recent audit log</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody><?php foreach ($logs as $row): ?><tr>
                        <td><?= e($row['action']) ?></td>
                        <td><?= e($row['details']) ?></td>
                        <td><?= e($row['created_at']) ?></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section><?php require_once __DIR__ . '/includes/footer.php'; ?>