
<?php

function audit(PDO $pdo, int $userId, string $action, string $details = '')
{
    try {
        $sql = "INSERT INTO audit_logs (user_id, action, details)
                VALUES (:user_id, :action, :details)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'user_id' => (int) $userId,
            'action' => (string) $action,
            'details' => (string) $details
        ]);

        return true;
    } catch (Throwable $e) {
        error_log('Audit logging failed: ' . $e->getMessage());
        return false;
    }
}
