<?php
require_once __DIR__ . '/config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Keep the schema consistent with users.id (INT UNSIGNED).
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

$errors = [];
$success = false;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }
    if ($token === '') {
        $errors[] = 'Reset token is missing. Please generate a new reset link.';
    }

    if (!$errors) {
        try {
            $tokenHash = hash('sha256', $token);
            $stmt = $pdo->prepare(
                'SELECT id, user_id FROM password_resets
                 WHERE token_hash = ? AND expires_at > NOW() LIMIT 1'
            );
            $stmt->execute([$tokenHash]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $errors[] = 'This reset link is invalid or expired. Generate a new one.';
            } else {
                $pdo->beginTransaction();
                try {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    // This project stores password hashes in users.password_hash.
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([$passwordHash, $reset['user_id']]);
                    if ($stmt->rowCount() < 1) {
                        // A matching hash can produce rowCount 0, so verify the user still exists.
                        $check = $pdo->prepare('SELECT id FROM users WHERE id = ?');
                        $check->execute([$reset['user_id']]);
                        if (!$check->fetchColumn()) {
                            throw new RuntimeException('User no longer exists.');
                        }
                    }
                    // Delete this user's reset tokens so the link cannot be reused.
                    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
                    $stmt->execute([$reset['user_id']]);
                    $pdo->commit();
                    $success = true;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }
            }
        } catch (Throwable $e) {
            error_log('StudentHub reset-password error: ' . $e->getMessage());
            if (!$errors) {
                $errors[] = 'Unable to reset the password. Check the database setup and try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - StudentHub</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 24px 15px; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; background: #101421; color: #f8fafc; }
        .card { width: 100%; max-width: 520px; padding: 30px; background: #1c2539; border: 1px solid #2b354d; border-radius: 16px; }
        h1 { margin-top: 0; }
        label { display: block; margin: 20px 0 8px; }
        input { width: 100%; padding: 14px; border: 1px solid #39445d; border-radius: 8px; background: #e8efff; color: #111827; }
        button { width: 100%; margin-top: 22px; padding: 14px; border: 0; border-radius: 8px; background: #5145e5; color: white; font-weight: bold; cursor: pointer; }
        .error, .success { padding: 12px; margin: 12px 0; border-radius: 8px; line-height: 1.5; }
        .error { background: #4b1e22; }
        .success { background: #164e35; }
        a { color: #a5b4fc; }
    </style>
</head>
<body>
<main class="card">
    <h1>Reset password</h1>
    <?php if ($success): ?>
        <div class="success" role="status">Your password has been changed successfully.</div>
        <p><a href="login.php">Go to login</a></p>
    <?php else: ?>
        <?php foreach ($errors as $error): ?>
            <div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
        <p>Enter your new password below. Use at least 8 characters.</p>
        <form method="post" action="">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            <label for="confirm_password">Confirm new password</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
            <button type="submit">Update password</button>
        </form>
    <?php endif; ?>
    <p><a href="login.php">Back to login</a></p>
</main>
</body>
</html>
