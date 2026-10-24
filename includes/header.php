<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$pageTitle = $pageTitle ?? 'StudentHub';
$currentUser = $_SESSION['user'] ?? null;
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="StudentHub campus portal demo">

  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | StudentHub</title>

  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/app.js" defer></script>
</head>

<body>
  <a class="skip-link" href="#main-content">Skip to main content</a>

  <header class="site-header">

    <a class="brand" href="index.php" aria-label="StudentHub home">
      <span class="brand-mark">S</span>
      <span>Student<span class="brand-accent">Hub</span></span>
    </a>

    <button
      class="menu-toggle"
      type="button"
      aria-expanded="false"
      aria-controls="primary-nav">
      <span class="sr-only">Toggle navigation</span>
      ☰
    </button>

    <nav id="primary-nav" class="primary-nav" aria-label="Main navigation">

      <a href="index.php">Home</a>
      <a href="about.html">About</a>
      <a href="events.html">Events</a>
      <a href="faq.html">FAQ</a>

      <?php if ($currentUser): ?>

        <a href="dashboard.php">Dashboard</a>

        <?php if ($isAdmin): ?>
          <a href="admin.php">Admin</a>
          <a href="events.php">Event Management</a>
          <a href="students.php">Students</a>
        <?php endif; ?>

        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>

      <?php else: ?>

        <a href="login.php">Login</a>
        <a class="nav-cta" href="register.php">Register</a>

      <?php endif; ?>

      <button
        class="theme-toggle"
        id="theme-toggle"
        type="button"
        aria-label="Toggle light and dark theme">
        ◐
      </button>

    </nav>
  </header>

  <main id="main-content" class="container">
