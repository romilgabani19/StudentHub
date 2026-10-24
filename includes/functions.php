<?php
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: login.php?next=dashboard.php');
        exit;
    }
    $timeout = 20 * 60;
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}
function require_admin(): void
{
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('403 Forbidden: administrator access required.');
    }
}
function audit(PDO $pdo, ?int $userId, string $action, string $details = ''): void
{
    try {
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$userId, $action, mb_substr($details, 0, 240)]);
    } catch (Throwable $e) {
        // Logging must not expose database details to the visitor.
    }
}
