<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = $_SESSION['user'];
?>
<header class="page-heading">
    <p class="eyebrow">YOUR ACCOUNT</p>
    <h1>Profile</h1>
</header>
<section class="panel">
    <dl>
        <dt><strong>Name</strong></dt>
        <dd><?= e($user['name']) ?></dd>
        <dt><strong>Email</strong></dt>
        <dd><?= e($user['email']) ?></dd>
        <dt><strong>Role</strong></dt>
        <dd><?= e($user['role']) ?></dd>
        <dt><strong>Course</strong></dt>
        <dd><?= e($user['course'] ?? 'Not specified') ?></dd>
        <dt><strong>Year</strong></dt>
        <dd><?= e((string)($user['year'] ?? 'Not specified')) ?></dd>
    </dl><a class="button" href="dashboard.php">Back to dashboard</a>
</section><?php require_once __DIR__ . '/includes/footer.php'; ?>