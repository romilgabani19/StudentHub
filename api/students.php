
<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

function api_response(int $statusCode, bool $success, array $data = []): void
{
    http_response_code($statusCode);

    echo json_encode(
        array_merge(['success' => $success], $data),
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

function read_json_input(): array
{
    $rawInput = file_get_contents('php://input');

    if ($rawInput === false || trim($rawInput) === '') {
        throw new InvalidArgumentException('Request body is empty.', 400);
    }

    $decoded = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new InvalidArgumentException('Invalid JSON input.', 400);
    }

    return $decoded;
}

function require_admin_api(): void
{
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        api_response(403, false, [
            'error' => 'Please log in as an admin to access this API.'
        ]);
    }
}

function validate_csrf_token(array $data): void
{
    $bodyToken = (string) ($data['csrf_token'] ?? '');
    $headerToken = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $token = $bodyToken !== '' ? $bodyToken : $headerToken;
    $expected = (string) ($_SESSION['students_csrf'] ?? '');

    if (
        $expected === '' ||
        $token === '' ||
        !hash_equals($expected, $token)
    ) {
        api_response(403, false, [
            'error' => 'Invalid session token. Refresh the page and try again.'
        ]);
    }
}

function valid_course(string $course): bool
{
    return in_array($course, ['AIML', 'CSE', 'IT', 'CE'], true);
}

function valid_semester(int $semester): bool
{
    return $semester >= 1 && $semester <= 8;
}

function validate_student_data(array $data): array
{
    $fullName = trim((string) ($data['full_name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $course = trim((string) ($data['course'] ?? ''));
    $semester = filter_var(
        $data['semester'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $fullName === '' ||
        mb_strlen($fullName) > 120
    ) {
        api_response(422, false, [
            'error' => 'Enter a name containing 1 to 120 characters.'
        ]);
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        mb_strlen($email) > 190
    ) {
        api_response(422, false, [
            'error' => 'Enter a valid email address.'
        ]);
    }

    if (!valid_course($course)) {
        api_response(422, false, [
            'error' => 'Select a valid course: AIML, CSE, IT, or CE.'
        ]);
    }

    if ($semester === false || !valid_semester((int) $semester)) {
        api_response(422, false, [
            'error' => 'Select a semester from 1 to 8.'
        ]);
    }

    return [
        'full_name' => $fullName,
        'email' => $email,
        'course' => $course,
        'semester' => (int) $semester
    ];
}

function write_audit(string $action, string $details): void
{
    global $pdo;

    if (function_exists('audit')) {
        audit(
            $pdo,
            (int) ($_SESSION['user']['id'] ?? 0),
            $action,
            $details
        );
    }
}

/* Require administrator access. */
require_admin_api();

/* Generate a CSRF token for this admin session. */
if (empty($_SESSION['students_csrf'])) {
    $_SESSION['students_csrf'] = bin2hex(random_bytes(32));
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');

    api_response(405, false, [
        'error' => 'Method not allowed.'
    ]);
}

try {
    /*
     * GET: List and search students.
     * The token is returned so the frontend can use it for mutations.
     */
    if ($method === 'GET') {
        $search = trim((string) ($_GET['q'] ?? ''));

        $sql = 'SELECT id, full_name, email, course, semester, status, created_at
                FROM students
                WHERE status != :deleted';

        $params = [':deleted' => 'deleted'];

        if ($search !== '') {
            $sql .= ' AND (
                full_name LIKE :search_name
                OR email LIKE :search_email
                OR course LIKE :search_course
            )';

            $term = '%' . $search . '%';

            $params[':search_name'] = $term;
            $params[':search_email'] = $term;
            $params[':search_course'] = $term;
        }

        $sql .= ' ORDER BY id DESC LIMIT 100';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        api_response(200, true, [
            'students' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'csrf_token' => $_SESSION['students_csrf']
        ]);
    }

    /* Read and validate JSON for write operations. */
    $data = read_json_input();
    validate_csrf_token($data);

    /*
     * POST: Add a student.
     */
    if ($method === 'POST') {
        $student = validate_student_data($data);

        $check = $pdo->prepare(
            'SELECT id FROM students
             WHERE LOWER(email) = LOWER(:email)
             AND status != :deleted
             LIMIT 1'
        );

        $check->execute([
            ':email' => $student['email'],
            ':deleted' => 'deleted'
        ]);

        if ($check->fetchColumn()) {
            api_response(409, false, [
                'error' => 'This email is already registered.'
            ]);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO students
                (full_name, email, course, semester, status, created_at)
             VALUES
                (:full_name, :email, :course, :semester, :status, NOW())'
        );

        $stmt->execute([
            ':full_name' => $student['full_name'],
            ':email' => $student['email'],
            ':course' => $student['course'],
            ':semester' => $student['semester'],
            ':status' => 'active'
        ]);

        write_audit('student.add', $student['email']);

        api_response(201, true, [
            'message' => 'Student added successfully.',
            'id' => (int) $pdo->lastInsertId()
        ]);
    }

    /*
     * PUT: Update an existing student.
     */
    if ($method === 'PUT') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            api_response(400, false, [
                'error' => 'Missing or invalid student ID.'
            ]);
        }

        $student = validate_student_data($data);

        $existing = $pdo->prepare(
            'SELECT id FROM students
             WHERE id = :id AND status != :deleted
             LIMIT 1'
        );

        $existing->execute([
            ':id' => $id,
            ':deleted' => 'deleted'
        ]);

        if (!$existing->fetchColumn()) {
            api_response(404, false, [
                'error' => 'Student not found.'
            ]);
        }

        $duplicate = $pdo->prepare(
            'SELECT id FROM students
             WHERE LOWER(email) = LOWER(:email)
             AND id != :id
             AND status != :deleted
             LIMIT 1'
        );

        $duplicate->execute([
            ':email' => $student['email'],
            ':id' => $id,
            ':deleted' => 'deleted'
        ]);

        if ($duplicate->fetchColumn()) {
            api_response(409, false, [
                'error' => 'Another student already uses this email.'
            ]);
        }

        $stmt = $pdo->prepare(
            'UPDATE students
             SET full_name = :full_name,
                 email = :email,
                 course = :course,
                 semester = :semester
             WHERE id = :id AND status != :deleted'
        );

        $stmt->execute([
            ':full_name' => $student['full_name'],
            ':email' => $student['email'],
            ':course' => $student['course'],
            ':semester' => $student['semester'],
            ':id' => $id,
            ':deleted' => 'deleted'
        ]);

        write_audit('student.update', $student['email']);

        api_response(200, true, [
            'message' => 'Student updated successfully.'
        ]);
    }

    /*
     * DELETE: Soft-delete a student.
     * The record remains in the database with status = deleted.
     */
    if ($method === 'DELETE') {
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            api_response(400, false, [
                'error' => 'Missing or invalid student ID.'
            ]);
        }

        $stmt = $pdo->prepare(
            "UPDATE students
             SET status = 'deleted'
             WHERE id = :id AND status != 'deleted'"
        );

        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            api_response(404, false, [
                'error' => 'Student not found or already deleted.'
            ]);
        }

        write_audit('student.delete', 'Student ID ' . $id);

        api_response(200, true, [
            'message' => 'Student deleted successfully.'
        ]);
    }
} catch (InvalidArgumentException $e) {
    $status = (int) $e->getCode();

    if (!in_array($status, [400, 422], true)) {
        $status = 400;
    }

    api_response($status, false, [
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log('Student API database error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        api_response(409, false, [
            'error' => 'A database constraint was violated. Check for duplicate values.'
        ]);
    }

    api_response(500, false, [
        'error' => 'A database error occurred. Check the PHP error log.'
    ]);
} catch (Throwable $e) {
    error_log('Student API error: ' . $e->getMessage());

    api_response(500, false, [
        'error' => 'Unable to process the request right now.'
    ]);
}
