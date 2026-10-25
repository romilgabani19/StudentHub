<?php
require_once __DIR__ . '/config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Create the reset table if it is missing. user_id matches users.id (INT UNSIGNED).
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_user_id (user_id),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$message = '';
$resetLink = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $emailValue = $email;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $message = 'If this account exists, a reset link has been generated for this local demo.';

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                $stmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
                $stmt->execute([$user['id']]);

                $stmt = $pdo->prepare(
                    'INSERT INTO password_resets (user_id, token_hash, expires_at)
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))'
                );
                $stmt->execute([$user['id'], $tokenHash]);

                // Build URL from this script's actual folder (works in /StudentHub or /Other/StudentHub).
                $scriptDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/StudentHub/forgot_password.php')), '/');
                if ($scriptDirectory === '') {
                    $scriptDirectory = '';
                }
                $resetLink = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                    . $scriptDirectory . '/reset_password.php?token=' . urlencode($token);
            }
        } catch (Throwable $e) {
            error_log('StudentHub forgot-password error: ' . $e->getMessage());
            $message = 'Could not generate a reset link. Check that the users table and database connection are correct.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - StudentHub</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 30px 15px; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; background: #101421; color: #f8fafc; }
        .card { width: 100%; max-width: 660px; padding: 34px; background: #1c2539; border: 1px solid #2b354d; border-radius: 18px; }
        h1 { margin-top: 0; font-size: 32px; }
        p { line-height: 1.6; }
        label { display: block; margin: 22px 0 9px; }
        input { width: 100%; padding: 15px; border: 1px solid #39445d; border-radius: 9px; background: #111827; color: #fff; font-size: 16px; }
        button { width: 100%; margin-top: 22px; padding: 15px; border: 0; border-radius: 9px; background: #5145e5; color: #fff; font-size: 16px; font-weight: bold; cursor: pointer; }
        .message { margin-top: 20px; padding: 14px; border-radius: 9px; line-height: 1.7; overflow-wrap: anywhere; background: #26334b; }
        a { color: #a5b4fc; }
        @media (max-width: 480px) { .card { padding: 23px; } h1 { font-size: 27px; } }
    </style>
</head>
<body>
<main class="card">
    <h1>Forgot password?</h1>
    <p>Enter the email address registered with StudentHub.</p>
    <form method="post" action="">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" required autocomplete="email" placeholder="you@example.com" value="<?= htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Generate reset link</button>
    </form>
    <?php if ($message !== ''): ?>
        <div class="message" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($resetLink !== ''): ?>
        <div class="message">
            <strong>Local demo reset link (valid for 15 minutes):</strong>
            <p><a href="<?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?>">Reset your password</a></p>
            <p>This link display is for local development only. Use email delivery on a live site.</p>
        </div>
    <?php endif; ?>
    <p><a href="login.php">Back to login</a></p>
</main>
</body>
</html>
