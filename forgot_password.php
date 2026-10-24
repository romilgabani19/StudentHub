
<?php
require_once __DIR__ . '/config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$message = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Use the same response whether the account exists or not.
        $message = 'If this account exists, a reset link has been generated for this local demo.';

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $stmt = $pdo->prepare(
                'DELETE FROM password_resets WHERE user_id = ?'
            );
            $stmt->execute([$user['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO password_resets
                 (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))'
            );
            $stmt->execute([$user['id'], $tokenHash]);

            // LOCAL DEMO ONLY: display the link instead of emailing it.
            $resetLink = 'http://localhost/StudentHub/reset_password.php?token='
                . urlencode($token);
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
        body {
            margin: 0;
            padding: 30px 15px;
            font-family: Arial, sans-serif;
            background: #101421;
            color: #f8fafc;
        }
        .card {
            max-width: 520px;
            margin: 70px auto;
            padding: 30px;
            background: #1c2539;
            border: 1px solid #2b354d;
            border-radius: 16px;
        }
        h1 { margin-top: 0; }
        p { line-height: 1.6; }
        label { display: block; margin: 20px 0 8px; }
        input {
            box-sizing: border-box;
            width: 100%;
            padding: 14px;
            border: 1px solid #39445d;
            border-radius: 8px;
            background: #111827;
            color: white;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            background: #5145e5;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        .message {
            margin-top: 18px;
            overflow-wrap: anywhere;
            line-height: 1.7;
        }
        a { color: #a5b4fc; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Forgot password?</h1>
        <p>Enter the email address registered with StudentHub.</p>

        <form method="post">
            <label for="email">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                required
                autocomplete="email"
                placeholder="you@example.com"
            >
            <button type="submit">Generate reset link</button>
        </form>

        <?php if ($message !== ''): ?>
            <div class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($resetLink !== ''): ?>
            <div class="message">
                <strong>Local demo reset link (valid for 15 minutes):</strong>
                <p>
                    <a href="<?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?>">
                        Reset your password
                    </a>
                </p>
                <p>Do not use this link-display method on a live website.</p>
            </div>
        <?php endif; ?>

        <p><a href="login.php">Back to login</a></p>
    </main>
</body>
</html>
