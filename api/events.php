
<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

// Allow only logged-in administrators
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin login required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query(
            "SELECT id, title, event_date, category, description
             FROM events ORDER BY id DESC"
        );
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    if ($method === 'POST') {
        $title = trim($data['title'] ?? '');
        $date = $data['event_date'] ?? '';
        $category = trim($data['category'] ?? '');
        $description = trim($data['description'] ?? '');

        if (!$title || !$date || !$category || !$description) {
            http_response_code(422);
            echo json_encode(['error' => 'Fill in all fields']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO events
            (title, event_date, category, description)
            VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$title, $date, $category, $description]);

        echo json_encode(['message' => 'Event added successfully']);
        exit;
    }

    if ($method === 'PUT') {
        $stmt = $pdo->prepare(
            "UPDATE events SET title=?, event_date=?,
             category=?, description=? WHERE id=?"
        );
        $stmt->execute([
            $data['title'] ?? '',
            $data['event_date'] ?? '',
            $data['category'] ?? '',
            $data['description'] ?? '',
            $data['id'] ?? 0
        ]);

        echo json_encode(['message' => 'Update request completed']);
        exit;
    }

    if ($method === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
        $stmt->execute([$data['id'] ?? 0]);

        echo json_encode(['message' => 'Delete request completed']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server or database error']);
}
