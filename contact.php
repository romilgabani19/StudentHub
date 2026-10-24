<?php
$pageTitle = 'Contact';
require_once __DIR__ . '/includes/header.php';
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (mb_strlen($message) < 10) $errors[] = 'Message must contain at least 10 characters.';
    if (!$errors) {
        $dir = __DIR__ . '/storage';
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        $fp = fopen($dir . '/contacts.csv', 'ab');
        if ($fp) {
            flock($fp, LOCK_EX);
            fputcsv($fp, [date('c'), $name, $email, $message]);
            flock($fp, LOCK_UN);
            fclose($fp);
            $success = true;
        } else {
            $errors[] = 'Could not save your message.';
        }
    }
}
?>
<header class="page-heading">
    <p class="eyebrow">GET IN TOUCH</p>
    <h1>Contact us</h1>
    <p>This practical demonstrates PHP POST processing and CSV storage.</p>
</header>
<section class="form-card">
    <?php if ($success): ?><p class="success-message" role="status">Your message was saved. Thank you.</p><?php endif; ?>
    <?php if ($errors): ?><div class="error-message" role="alert">
            <ul><?php foreach ($errors as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
        </div><?php endif; ?>
    <form method="post">
        <div class="field"><label for="name">Name</label><input id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>"></div>
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="field"><label for="message">Message</label><textarea id="message" name="message" minlength="10" required><?= e($_POST['message'] ?? '') ?></textarea></div><button class="button">Send message</button>
    </form>
</section><?php require_once __DIR__ . '/includes/footer.php'; ?>