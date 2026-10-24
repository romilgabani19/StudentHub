
<?php
$pageTitle = 'Student Management';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

require_admin();

$message = '';
$error = '';

$allowedCourses = ['AIML', 'CSE', 'IT', 'CE'];
$allowedSemesters = range(1, 8);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete student
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id) {
            try {
                $stmt = $pdo->prepare(
                    'DELETE FROM students WHERE id = ?'
                );
                $stmt->execute([$id]);

                if ($stmt->rowCount() > 0) {
                    $message = 'Student deleted successfully.';

                    audit(
                        $pdo,
                        (int)$_SESSION['user']['id'],
                        'student.delete',
                        'Student ID ' . $id
                    );
                } else {
                    $error = 'Student record not found.';
                }
            } catch (PDOException $e) {
                error_log('Student delete error: ' . $e->getMessage());
                $error = 'Unable to delete the student record.';
            }
        } else {
            $error = 'Invalid student ID.';
        }
    }

    // Add or update student
    elseif (in_array($action, ['add', 'update'], true)) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $semesterInput = trim($_POST['semester'] ?? '');

        $semester = ctype_digit($semesterInput)
            ? (int)$semesterInput
            : 0;

        // Validate form data
        if (
            $name === '' ||
            strlen($name) > 120 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 190 ||
            !in_array($course, $allowedCourses, true) ||
            !in_array($semester, $allowedSemesters, true) ||
            ($action === 'update' && !$id)
        ) {
            $error = 'Please check the name, email, course, and semester.';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare(
                        "INSERT INTO students
                        (full_name, email, course, semester, status, created_at)
                        VALUES (?, ?, ?, ?, 'active', NOW())"
                    );

                    $stmt->execute([
                        $name,
                        $email,
                        $course,
                        $semester
                    ]);

                    $message = 'Student added successfully.';

                    audit(
                        $pdo,
                        (int)$_SESSION['user']['id'],
                        'student.add',
                        $email
                    );
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE students
                         SET full_name = ?, email = ?, course = ?, semester = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $name,
                        $email,
                        $course,
                        $semester,
                        $id
                    ]);

                    $message = 'Student updated successfully.';

                    audit(
                        $pdo,
                        (int)$_SESSION['user']['id'],
                        'student.update',
                        $email
                    );
                }
            } catch (PDOException $e) {
                error_log('Student save error: ' . $e->getMessage());

                if ($e->getCode() === '23000') {
                    $error = 'This email or student record may already exist.';
                } else {
                    $error = 'Unable to save the student. Check the database configuration.';
                }
            }
        }
    }
}

// Search students
$q = trim($_GET['q'] ?? '');

$stmt = $pdo->prepare(
    'SELECT id, full_name, email, course, semester, status
     FROM students
     WHERE status != ?
       AND (full_name LIKE ? OR email LIKE ? OR course LIKE ?)
     ORDER BY id DESC
     LIMIT 100'
);

$like = '%' . $q . '%';

$stmt->execute([
    'deleted',
    $like,
    $like,
    $like
]);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.students-page {
    max-width: 1200px;
    margin: 45px auto;
    padding: 0 24px;
    color: var(--text-color, inherit);
}

.students-page .page-heading {
    margin-bottom: 28px;
}

.students-page h1 {
    font-size: 32px;
    margin-bottom: 8px;
}

.students-page .eyebrow {
    color: #a5b4fc;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
}

.students-page .panel {
    padding: 24px;
    margin-bottom: 28px;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 16px;
    background: rgba(30, 41, 59, .45);
}

.students-page input,
.students-page select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #39445d;
    border-radius: 8px;
    background: #1b2235;
    color: #fff;
    box-sizing: border-box;
}

.students-page button,
.students-page .btn {
    display: inline-block;
    padding: 11px 16px;
    border: 0;
    border-radius: 8px;
    background: #5145e5;
    color: #fff;
    cursor: pointer;
    text-decoration: none;
}

.students-page button:hover {
    background: #4338ca;
}

.students-page .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.students-page .field label {
    display: block;
    margin-bottom: 8px;
}

.students-page .table-wrap {
    overflow-x: auto;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 12px;
}

.students-page table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.students-page th,
.students-page td {
    padding: 14px;
    border-bottom: 1px solid rgba(148, 163, 184, .18);
}

.students-page th {
    background: rgba(81, 69, 229, .14);
}

.students-page tr:last-child td {
    border-bottom: none;
}

.students-page .toolbar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.students-page .toolbar input {
    flex: 1;
}

.students-page .table-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 10px;
}

.students-page details {
    min-width: 70px;
}

.students-page summary {
    cursor: pointer;
    color: #a5b4fc;
    margin-bottom: 10px;
}

.students-page .edit-form {
    min-width: 220px;
    display: grid;
    gap: 12px;
    padding: 12px;
}

.students-page .small-button {
    padding: 8px 12px;
}

.students-page .delete-button {
    background: #b91c1c;
}

.students-page .delete-button:hover {
    background: #991b1b;
}

.students-page .success-message {
    padding: 12px 16px;
    border-radius: 8px;
    background: #14532d;
    color: #dcfce7;
    margin-bottom: 20px;
}

.students-page .error-message {
    padding: 12px 16px;
    border-radius: 8px;
    background: #7f1d1d;
    color: #fee2e2;
    margin-bottom: 20px;
}

@media (max-width: 650px) {
    .students-page {
        margin: 25px auto;
        padding: 0 14px;
    }

    .students-page .form-grid {
        grid-template-columns: 1fr;
    }

    .students-page .toolbar {
        flex-direction: column;
    }
}
</style>

<main class="students-page">

    <header class="page-heading">
        <p class="eyebrow">ADMIN TOOLS</p>
        <h1>Student management</h1>
        <p>Add, search, edit, and delete student records.</p>
    </header>

    <?php if ($message !== ''): ?>
        <p class="success-message" role="status">
            <?= e($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="error-message" role="alert">
            <?= e($error) ?>
        </p>
    <?php endif; ?>

    <section class="panel">
        <h2>Add a student</h2>

        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add">

            <div class="field">
                <label for="full_name">Full name</label>
                <input
                    id="full_name"
                    name="full_name"
                    maxlength="120"
                    required
                >
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    maxlength="190"
                    required
                >
            </div>

            <div class="field">
                <label for="course">Course</label>
                <select id="course" name="course" required>
                    <option value="">Choose course</option>
                    <?php foreach ($allowedCourses as $item): ?>
                        <option value="<?= e($item) ?>">
                            <?= e($item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="semester">Semester</label>
                <select id="semester" name="semester" required>
                    <option value="">Choose semester</option>
                    <?php foreach ($allowedSemesters as $item): ?>
                        <option value="<?= $item ?>">
                            Semester <?= $item ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <button type="submit">Add student</button>
            </div>
        </form>
    </section>

    <form class="toolbar" method="get">
        <label for="q" style="display:none">Search students</label>
        <input
            id="q"
            name="q"
            value="<?= e($q) ?>"
            placeholder="Search name, email, or course"
        >
        <button type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="btn" href="students.php">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6">No students found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= e($s['full_name']) ?></td>
                            <td><?= e($s['email']) ?></td>
                            <td><?= e($s['course']) ?></td>
                            <td><?= e((string)$s['semester']) ?></td>
                            <td><?= e($s['status']) ?></td>

                            <td>
                                <div class="table-actions">
                                    <details>
                                        <summary>Edit</summary>

                                        <form
                                            method="post"
                                            class="edit-form"
                                        >
                                            <input
                                                type="hidden"
                                                name="action"
                                                value="update"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$s['id'] ?>"
                                            >

                                            <div class="field">
                                                <label>Name</label>
                                                <input
                                                    name="full_name"
                                                    maxlength="120"
                                                    value="<?= e($s['full_name']) ?>"
                                                    required
                                                >
                                            </div>

                                            <div class="field">
                                                <label>Email</label>
                                                <input
                                                    name="email"
                                                    type="email"
                                                    maxlength="190"
                                                    value="<?= e($s['email']) ?>"
                                                    required
                                                >
                                            </div>

                                            <div class="field">
                                                <label>Course</label>
                                                <select name="course" required>
                                                    <?php foreach ($allowedCourses as $item): ?>
                                                        <option
                                                            value="<?= e($item) ?>"
                                                            <?= $item === $s['course'] ? 'selected' : '' ?>
                                                        >
                                                            <?= e($item) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="field">
                                                <label>Semester</label>
                                                <select name="semester" required>
                                                    <?php foreach ($allowedSemesters as $item): ?>
                                                        <option
                                                            value="<?= $item ?>"
                                                            <?= $item === (int)$s['semester'] ? 'selected' : '' ?>
                                                        >
                                                            Semester <?= $item ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <button
                                                type="submit"
                                                class="small-button"
                                            >
                                                Save changes
                                            </button>
                                        </form>
                                    </details>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Delete this student record?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$s['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="small-button delete-button"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
