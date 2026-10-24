
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$timeoutSeconds = 900; // 15 minutes

if (isset($_SESSION['user'])) {
    $lastActivity = $_SESSION['last_activity'] ?? time();

    if (time() - $lastActivity >= $timeoutSeconds) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();

        header('Location: login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function requireLogin(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('403 Forbidden: You are not authorized to access this page.');
    }
}
