<?php
$pageTitle = 'Feedback';
require_once __DIR__ . '/includes/header.php';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = trim($_POST['rating'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    if (in_array($rating, ['Excellent', 'Good', 'Average', 'Needs improvement'], true) && $comment !== '') {
        $dir = __DIR__ . '/storage';
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        $fp = fopen($dir . '/feedback.csv', 'ab');
        if ($fp) {
            fputcsv($fp, [date('c'), $rating, $comment]);
            fclose($fp);
            $sent = true;
        }
    }
}
?>
<header class="page-heading">
    <p class="eyebrow">HELP US IMPROVE</p>
    <h1>Share feedback</h1>
</header>
<section class="form-card"><?php if ($sent): ?><p class="success-message" role="status">Thanks for your feedback.</p><?php endif; ?><form method="post">
        <div class="field"><label for="rating">How was your experience?</label><select id="rating" name="rating" required>
                <option value="">Choose rating</option>
                <option>Excellent</option>
                <option>Good</option>
                <option>Average</option>
                <option>Needs improvement</option>
            </select></div>
        <div class="field"><label for="comment">Your comments</label><textarea id="comment" name="comment" required></textarea></div><button class="button">Submit feedback</button>
    </form>
</section><?php require_once __DIR__ . '/includes/footer.php'; ?>