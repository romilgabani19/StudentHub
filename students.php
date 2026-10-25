
<?php
$pageTitle = 'Student Management';

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

require_admin();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['students_csrf'])) {
    $_SESSION['students_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['students_csrf'];
$allowedCourses = ['AIML', 'CSE', 'IT', 'CE'];
$allowedSemesters = range(1, 8);
?>

<style>
.students-page {
    max-width: 1200px;
    margin: 45px auto;
    padding: 0 24px 40px;
    color: var(--text-color, inherit);
}

.students-page .page-heading {
    margin-bottom: 28px;
}

.students-page h1 {
    font-size: clamp(2rem, 3vw, 2.5rem);
    margin: 8px 0 10px;
}

.students-page .eyebrow {
    color: #a5b4fc;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 2px;
    margin: 0;
}

.students-page .panel {
    padding: 24px;
    margin-bottom: 28px;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 16px;
    background: rgba(15, 23, 42, .72);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
}

.students-page .panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}

.students-page .student-alert {
    display: none;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-weight: 600;
}

.students-page .student-alert.visible {
    display: block;
}

.students-page .student-alert.success {
    background: rgba(20, 83, 45, 0.9);
    color: #dcfce7;
}

.students-page .student-alert.error {
    background: rgba(127, 29, 29, 0.88);
    color: #fee2e2;
}

.students-page .secondary-button {
    background: transparent;
    border: 1px solid rgba(148, 163, 184, .35);
    color: #e2e8f0;
}

.students-page .secondary-button.hidden {
    display: none;
}

.students-page input,
.students-page select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid rgba(148, 163, 184, .4);
    border-radius: 8px;
    background: #0f172a;
    color: #f8fafc;
    box-sizing: border-box;
}

.students-page .field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.students-page .field label {
    font-weight: 600;
}

.students-page .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px 20px;
}

.students-page .form-actions {
    display: flex;
    align-items: end;
}

.students-page button,
.students-page .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 16px;
    border: 0;
    border-radius: 8px;
    background: #5145e5;
    color: #fff;
    cursor: pointer;
    text-decoration: none;
    font-weight: 700;
}

.students-page button:hover,
.students-page .btn:hover {
    background: #4338ca;
}

.students-page .btn-danger {
    background: #b91c1c;
}

.students-page .btn-danger:hover {
    background: #991b1b;
}

.students-page .toolbar {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 18px;
}

.students-page .toolbar label {
    font-weight: 600;
}

.students-page .toolbar input {
    flex: 1;
}

.students-page .table-wrap {
    overflow-x: auto;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 12px;
    background: rgba(15, 23, 42, .62);
}

.students-page table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.students-page th,
.students-page td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(148, 163, 184, .16);
    vertical-align: top;
}

.students-page th {
    background: rgba(81, 69, 229, .14);
}

.students-page tr:last-child td {
    border-bottom: none;
}

.students-page .table-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.students-page .table-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(16, 185, 129, .16);
    color: #a7f3d0;
    font-size: .8rem;
    font-weight: 700;
    text-transform: capitalize;
}

.students-page .empty-row td {
    padding: 18px;
    color: #cbd5e1;
}

@media (max-width: 760px) {
    .students-page {
        margin: 25px auto;
        padding: 0 14px 28px;
    }

    .students-page .form-grid {
        grid-template-columns: 1fr;
    }

    .students-page .toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .students-page .panel-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<main class="students-page">
    <header class="page-heading">
        <p class="eyebrow">ADMIN TOOLS</p>
        <h1>Student management</h1>
        <p>Fetch-based CRUD using PHP JSON API and MySQL.</p>
    </header>

    <div id="student-alert" class="student-alert" role="status" aria-live="polite"></div>

    <section class="panel">
        <div class="panel-header">
            <h2 id="form-title">Add a student</h2>
            <button type="button" id="cancel-edit" class="secondary-button hidden">Cancel</button>
        </div>

        <form id="student-form" class="form-grid" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="student-id" name="id" value="">

            <div class="field">
                <label for="full_name">Full name</label>
                <input id="full_name" name="full_name" maxlength="120" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="190" required>
            </div>

            <div class="field">
                <label for="course">Course</label>
                <select id="course" name="course" required>
                    <option value="">Choose course</option>
                    <?php foreach ($allowedCourses as $item): ?>
                        <option value="<?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="semester">Semester</label>
                <select id="semester" name="semester" required>
                    <option value="">Choose semester</option>
                    <?php foreach ($allowedSemesters as $item): ?>
                        <option value="<?= (int) $item ?>">Semester <?= (int) $item ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field form-actions">
                <button id="submit-student" type="submit">Add student</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="toolbar">
            <label for="student-search">Search</label>
            <input
                id="student-search"
                type="search"
                placeholder="Search by name, email, or course"
                aria-label="Search students"
            >
        </div>
    </section>

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
            <tbody id="students-table-body">
                <tr>
                    <td colspan="6" class="empty-row">Loading students...</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
