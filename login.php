
<?php
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password_hash, role
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $user &&
                password_verify($password, $user['password_hash'])
            ) {
                // Secure session after successful login
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id'    => (int) $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role']
                ];

                $_SESSION['last_activity'] = time();

                // Record login if audit helper exists
                $auditFile = __DIR__ . '/includes/audit.php';

                if (is_file($auditFile)) {
                    require_once $auditFile;

                    if (function_exists('audit')) {
                        try {
                            audit(
                                $pdo,
                                (int) $user['id'],
                                'login',
                                'Successful login'
                            );
                        } catch (Throwable $auditError) {
                            error_log(
                                'Audit error: ' . $auditError->getMessage()
                            );
                        }
                    }
                }

                // Update last login time
                $update = $pdo->prepare(
                    'UPDATE users SET last_login = NOW() WHERE id = ?'
                );

                $update->execute([(int) $user['id']]);

                // Redirect according to role
                if ($user['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: dashboard.php');
                }

                exit;
            }

            $errors[] = 'Incorrect email address or password.';

        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $errors[] = 'Unable to log in right now. Please try again.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    .login-page {
        max-width: 1000px;
        margin: 55px auto;
        padding: 0 24px;
        color: var(--text-color, #f8fafc);
    }

    .login-heading {
        margin-bottom: 35px;
    }

    .login-heading .eyebrow {
        color: #a5b4fc;
        font-weight: bold;
        letter-spacing: 2px;
    }

    .login-heading h1 {
        font-size: 36px;
        margin: 25px 0;
    }

    .login-heading p {
        line-height: 1.6;
    }

    .login-alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 22px;
    }

    .login-alert.success {
        background: #143d2a;
        color: #bbf7d0;
    }

    .login-alert.error {
        background: #451a1a;
        color: #fecaca;
    }

    .login-card {
        background: #1c2437;
        padding: 35px;
        border: 1px solid #2b354b;
        border-radius: 20px;
    }

    .login-field {
        margin-bottom: 24px;
    }

    .login-field label {
        display: block;
        margin-bottom: 10px;
    }

    .login-field input {
        display: block;
        width: 100%;
        box-sizing: border-box;
        padding: 15px;
        border: 1px solid #39445d;
        border-radius: 10px;
        background: #111827;
        color: white;
        font-size: 16px;
    }

    .login-field input:focus {
        outline: 2px solid #5145e5;
        outline-offset: 2px;
    }

    .forgot-password {
        text-align: right;
        margin-top: -12px;
        margin-bottom: 24px;
    }

    .forgot-password a,
    .login-links a {
        color: #a5b4fc;
        text-decoration: none;
    }

    .forgot-password a:hover,
    .login-links a:hover {
        text-decoration: underline;
    }

    .login-button {
        display: block;
        width: 100%;
        padding: 15px;
        border: 0;
        border-radius: 10px;
        background: #5145e5;
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
    }

    .login-button:hover {
        background: #4338ca;
    }

    .login-links {
        text-align: center;
        margin-top: 25px;
    }

    @media (max-width: 600px) {
        .login-page {
            margin: 35px auto;
            padding: 0 16px;
        }

        .login-heading h1 {
            font-size: 29px;
        }

        .login-card {
            padding: 22px;
        }
    }
</style>

<main class="login-page">

    <div class="login-heading">
        <p class="eyebrow">WELCOME BACK</p>

        <h1>Log in to StudentHub</h1>

        <p>Use your registered email address.</p>
    </div>

    <?php if (isset($_GET['registered'])): ?>
        <div class="login-alert success" role="status">
            Registration successful! You can now log in.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="login-alert success" role="status">
            Your password has been changed. Please log in with your new password.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="login-alert error" role="alert">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="login-card">

        <div class="login-field">
            <label for="email">Email address *</label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars(
                    $email,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                required
                autocomplete="email"
                placeholder="Enter your email address"
            >
        </div>

        <div class="login-field">
            <label for="password">Password *</label>

            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
            >
        </div>

        <!-- Forgot Password Link -->
        <div class="forgot-password">
            <a href="forgot_password.php">Forgot your password?</a>
        </div>

        <button type="submit" class="login-button">
            Log In
        </button>

        <p class="login-links">
            Don't have an account?
            <a href="register.php">Create an account</a>
        </p>

    </form>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
