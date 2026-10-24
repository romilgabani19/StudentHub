<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_login();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE student_id=?');
$stmt->execute([$_SESSION['user']['id']]);
$registrationCount = (int)$stmt->fetchColumn();
?>
<header class="page-heading">
    <p class="eyebrow">YOUR SPACE</p>
    <h1>Welcome, <?= e($_SESSION['user']['name']) ?>!</h1>
    <p>Your StudentHub overview.</p>
</header>
<div class="card-grid">
    <article class="feature-card">
        <p class="eyebrow">ACCOUNT</p>
        <h2>Student profile</h2>
        <p>Review your registered details.</p><a href="profile.php">Open profile →</a>
    </article>
    <article class="feature-card">
        <p class="eyebrow">CAMPUS</p>
        <h2>Explore events</h2>
        <p>Find activities that interest you.</p><a href="events.html">View events →</a>
    </article>
    <article class="feature-card">
        <p class="eyebrow">REGISTRATIONS</p>
        <h2><?= $registrationCount ?></h2>
        <p>Event registrations in the database.</p>
    </article>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>