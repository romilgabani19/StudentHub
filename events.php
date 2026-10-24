
<?php
// StudentHub - Practical 12: Event Management CRUD

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_admin();

$uploadDir = __DIR__ . '/uploads/event-posters/';
$uploadUrl = 'uploads/event-posters/';
$maxSize = 2 * 1024 * 1024; // 2 MB

$categories = [
    'Workshop',
    'Seminar',
    'Cultural',
    'Sports',
    'Technical',
    'Other'
];

$message = '';
$errors = [];

// CSRF protection
if (empty($_SESSION['events_csrf'])) {
    $_SESSION['events_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['events_csrf'];

// Create upload directory if missing
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $errors[] = 'Cannot create the poster upload folder.';
    }
}

// Escape output safely
if (!function_exists('event_escape')) {
    function event_escape($value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

// Save and validate an uploaded poster.
// Returns null when no file was uploaded.
function event_save_poster(
    string $field,
    string $uploadDir,
    int $maxSize
): ?string {
    if (!isset($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Upload failed. Check the file and PHP upload limits.'
        );
    }

    if ($file['size'] > $maxSize) {
        throw new RuntimeException(
            'Poster must not be larger than 2 MB.'
        );
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    // Validate real image contents and MIME type
    $image = @getimagesize($file['tmp_name']);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (
        $image === false ||
        !isset($allowed[$mime]) ||
        ($image['mime'] ?? '') !== $mime
    ) {
        throw new RuntimeException(
            'Only valid JPG, PNG, and WebP images are allowed.'
        );
    }

    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        throw new RuntimeException(
            'The poster folder is not writable.'
        );
    }

    // Generate a random filename
    $filename = bin2hex(random_bytes(16))
        . '.' . $allowed[$mime];

    if (!move_uploaded_file(
        $file['tmp_name'],
        $uploadDir . $filename
    )) {
        throw new RuntimeException(
            'Unable to save the poster file.'
        );
    }

    return $filename;
}

// Remove only generated poster filenames
function event_remove_poster(
    ?string $filename,
    string $uploadDir
): void {
    if (
        $filename !== null &&
        preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)
    ) {
        $path = $uploadDir . $filename;

        if (is_file($path)) {
            @unlink($path);
        }
    }
}

// Record audit information when the helper is available
function event_audit(
    PDO $pdo,
    string $action,
    string $details
): void {
    if (function_exists('audit')) {
        try {
            audit(
                $pdo,
                (int)($_SESSION['user']['id'] ?? 0),
                $action,
                $details
            );
        } catch (Throwable $e) {
            error_log('Event audit error: ' . $e->getMessage());
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($postedCsrf) ||
        !hash_equals($csrf, $postedCsrf)
    ) {
        $errors[] = 'Invalid form session. Refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $newPoster = null;

        try {
            if (in_array($action, ['add', 'update'], true)) {
                $id = filter_input(
                    INPUT_POST,
                    'id',
                    FILTER_VALIDATE_INT
                );

                $title = trim($_POST['title'] ?? '');
                $date = trim($_POST['event_date'] ?? '');
                $category = trim($_POST['category'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $status = trim($_POST['status'] ?? '');

                $dateObject = DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $date
                );

                $validDate = $dateObject !== false &&
                    $dateObject->format('Y-m-d') === $date;

                if ($title === '' || mb_strlen($title) > 180) {
                    throw new RuntimeException(
                        'Enter a title of up to 180 characters.'
                    );
                }

                if (!$validDate) {
                    throw new RuntimeException(
                        'Please enter a valid event date.'
                    );
                }

                if (!in_array($category, $categories, true)) {
                    throw new RuntimeException(
                        'Select a valid event category.'
                    );
                }

                if ($description === '') {
                    throw new RuntimeException(
                        'Enter the event description.'
                    );
                }

                if (!in_array($status, ['open', 'closed'], true)) {
                    throw new RuntimeException(
                        'Select a valid event status.'
                    );
                }

                $oldPoster = null;

                if ($action === 'update') {
                    if (!$id) {
                        throw new RuntimeException('Invalid event ID.');
                    }

                    $find = $pdo->prepare(
                        'SELECT poster FROM events WHERE id = ?'
                    );
                    $find->execute([$id]);
                    $existing = $find->fetch(PDO::FETCH_ASSOC);

                    if (!$existing) {
                        throw new RuntimeException('Event not found.');
                    }

                    $oldPoster = $existing['poster'];
                }

                // Optional poster upload
                $newPoster = event_save_poster(
                    'poster',
                    $uploadDir,
                    $maxSize
                );

                if ($action === 'add') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO events
                        (title, event_date, category, description,
                         poster, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())'
                    );

                    $stmt->execute([
                        $title,
                        $date,
                        $category,
                        $description,
                        $newPoster,
                        $status
                    ]);

                    event_audit($pdo, 'event.add', $title);
                    $message = 'Event added successfully.';
                } else {
                    $posterToSave = $newPoster ?? $oldPoster;

                    $stmt = $pdo->prepare(
                        'UPDATE events
                         SET title = ?, event_date = ?, category = ?,
                             description = ?, poster = ?, status = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $title,
                        $date,
                        $category,
                        $description,
                        $posterToSave,
                        $status,
                        $id
                    ]);

                    if (
                        $newPoster !== null &&
                        $oldPoster !== null &&
                        $oldPoster !== $newPoster
                    ) {
                        event_remove_poster($oldPoster, $uploadDir);
                    }

                    event_audit($pdo, 'event.update', $title);
                    $message = 'Event updated successfully.';
                }
            } elseif ($action === 'delete') {
                $id = filter_input(
                    INPUT_POST,
                    'id',
                    FILTER_VALIDATE_INT
                );

                if (!$id) {
                    throw new RuntimeException('Invalid event ID.');
                }

                $stmt = $pdo->prepare(
                    'SELECT title, poster FROM events WHERE id = ?'
                );
                $stmt->execute([$id]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$event) {
                    throw new RuntimeException('Event not found.');
                }

                $stmt = $pdo->prepare(
                    'DELETE FROM events WHERE id = ?'
                );
                $stmt->execute([$id]);

                event_remove_poster($event['poster'], $uploadDir);
                event_audit($pdo, 'event.delete', $event['title']);

                $message = 'Event deleted successfully.';
            } else {
                throw new RuntimeException('Invalid form action.');
            }
        } catch (RuntimeException $e) {
            // Clean up a newly uploaded file if the DB operation failed
            if ($newPoster !== null) {
                event_remove_poster($newPoster, $uploadDir);
            }

            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($newPoster !== null) {
                event_remove_poster($newPoster, $uploadDir);
            }

            error_log('Event database error: ' . $e->getMessage());
            $errors[] = 'Database operation failed. Check your events table.';
        } catch (Throwable $e) {
            if ($newPoster !== null) {
                event_remove_poster($newPoster, $uploadDir);
            }

            error_log('Event error: ' . $e->getMessage());
            $errors[] = 'An unexpected error occurred.';
        }
    }
}

// Search and filters
$search = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = 'SELECT id, title, event_date, category, description,
               poster, status, created_at
        FROM events WHERE 1=1';

$params = [];

if ($search !== '') {
    $sql .= ' AND (title LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (in_array($filterCategory, $categories, true)) {
    $sql .= ' AND category = ?';
    $params[] = $filterCategory;
}

if (in_array($filterStatus, ['open', 'closed'], true)) {
    $sql .= ' AND status = ?';
    $params[] = $filterStatus;
}

$sql .= ' ORDER BY event_date DESC, id DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header after POST processing so redirects from helpers remain possible
$pageTitle = 'Event Management';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.events-page {
    max-width: 1250px;
    margin: 40px auto;
    padding: 0 24px;
    color: var(--text-color, #f8fafc);
}
.events-page .eyebrow {
    color: #a5b4fc;
    font-size: 13px;
    font-weight: bold;
    letter-spacing: 2px;
}
.events-page h1 { margin-bottom: 8px; }
.events-page .panel {
    background: rgba(30, 41, 59, .55);
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 16px;
    padding: 24px;
    margin: 24px 0;
}
.events-page input,
.events-page select,
.events-page textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid #39445d;
    border-radius: 8px;
    background: #111827;
    color: #fff;
}
.events-page textarea { min-height: 100px; resize: vertical; }
.events-page label { display: block; margin-bottom: 8px; }
.events-page .field { margin-bottom: 16px; }
.events-page .form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}
.events-page button,
.events-page .button {
    display: inline-block;
    padding: 11px 15px;
    border: 0;
    border-radius: 8px;
    background: #5145e5;
    color: white;
    cursor: pointer;
    text-decoration: none;
}
.events-page .danger { background: #b91c1c; }
.events-page .table-wrap {
    overflow-x: auto;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 12px;
}
.events-page table { width: 100%; border-collapse: collapse; }
.events-page th,
.events-page td {
    text-align: left;
    vertical-align: top;
    padding: 13px;
    border-bottom: 1px solid rgba(148, 163, 184, .18);
}
.events-page th { background: rgba(81, 69, 229, .14); }
.events-page .filter-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 12px;
    align-items: end;
}
.events-page .notice {
    padding: 14px 18px;
    margin: 18px 0;
    border-radius: 8px;
}
.events-page .success { background: #14532d; color: #dcfce7; }
.events-page .error { background: #7f1d1d; color: #fee2e2; }
.events-page .poster {
    width: 90px;
    height: 68px;
    object-fit: cover;
    border-radius: 8px;
}
.events-page .actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.events-page details summary {
    cursor: pointer;
    color: #a5b4fc;
    margin-bottom: 10px;
}
.events-page .edit-form { min-width: 250px; max-width: 350px; padding: 10px; }
.events-page .open { color: #86efac; }
.events-page .closed { color: #fca5a5; }
@media (max-width: 760px) {
    .events-page { padding: 0 14px; }
    .events-page .form-grid,
    .events-page .filter-grid { grid-template-columns: 1fr; }
    .events-page .panel { padding: 16px; }
}
</style>

<main class="events-page">

    <header>
        <p class="eyebrow">ADMIN TOOLS</p>
        <h1>Event Management</h1>
        <p>Add, view, edit, and delete events with poster uploads.</p>
    </header>

    <?php if ($message !== ''): ?>
        <div class="notice success" role="status">
            <?= event_escape($message) ?>
        </div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="notice error" role="alert">
            <?= event_escape($error) ?>
        </div>
    <?php endforeach; ?>

    <section class="panel">
        <h2>Add New Event</h2>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"
                   value="<?= event_escape($csrf) ?>">
            <input type="hidden" name="action" value="add">

            <div class="form-grid">
                <div class="field">
                    <label for="title">Event title *</label>
                    <input id="title" name="title" maxlength="180" required>
                </div>

                <div class="field">
                    <label for="event_date">Event date *</label>
                    <input id="event_date" name="event_date"
                           type="date" required>
                </div>

                <div class="field">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= event_escape($cat) ?>">
                                <?= event_escape($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="description">Description *</label>
                <textarea id="description" name="description"
                          required></textarea>
            </div>

            <div class="field">
                <label for="poster">Poster (JPG, PNG, WebP; max 2 MB)</label>
                <input id="poster" name="poster" type="file"
                       accept="image/jpeg,image/png,image/webp">
            </div>

            <button type="submit">Add Event</button>
        </form>
    </section>

    <section class="panel">
        <h2>Search and Filter</h2>

        <form method="get" class="filter-grid">
            <div class="field">
                <label for="q">Search</label>
                <input id="q" name="q"
                       value="<?= event_escape($search) ?>"
                       placeholder="Search title or description">
            </div>

            <div class="field">
                <label for="filter_category">Category</label>
                <select id="filter_category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= event_escape($cat) ?>"
                            <?= $filterCategory === $cat ? 'selected' : '' ?>>
                            <?= event_escape($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="filter_status">Status</label>
                <select id="filter_status" name="status">
                    <option value="">All statuses</option>
                    <option value="open"
                        <?= $filterStatus === 'open' ? 'selected' : '' ?>>
                        Open
                    </option>
                    <option value="closed"
                        <?= $filterStatus === 'closed' ? 'selected' : '' ?>>
                        Closed
                    </option>
                </select>
            </div>

            <div class="field">
                <button type="submit">Filter</button>
                <a class="button" href="events.php">Reset</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>All Events</h2>
        <p>Showing up to 200 matching records.</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Event details</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$events): ?>
                    <tr>
                        <td colspan="6">No events found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <?php
                                $poster = $event['poster'] ?? '';
                                $validPoster = is_string($poster) &&
                                    preg_match(
                                        '/^[a-f0-9]{32}\.(jpg|png|webp)$/',
                                        $poster
                                    );
                                ?>

                                <?php if (
                                    $validPoster &&
                                    is_file($uploadDir . $poster)
                                ): ?>
                                    <img
                                        class="poster"
                                        src="<?= event_escape($uploadUrl . $poster) ?>"
                                        alt="Event poster">
                                <?php else: ?>
                                    No poster
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong>
                                    <?= event_escape($event['title']) ?>
                                </strong>
                                <p><?= nl2br(event_escape($event['description'])) ?></p>
                            </td>

                            <td><?= event_escape($event['event_date']) ?></td>
                            <td><?= event_escape($event['category']) ?></td>

                            <td>
                                <span class="<?= $event['status'] === 'open'
                                    ? 'open' : 'closed' ?>">
                                    <?= event_escape(ucfirst($event['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions">
                                    <details>
                                        <summary>Edit</summary>

                                        <form method="post"
                                              enctype="multipart/form-data"
                                              class="edit-form">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= event_escape($csrf) ?>">
                                            <input type="hidden" name="action"
                                                   value="update">
                                            <input type="hidden" name="id"
                                                   value="<?= (int)$event['id'] ?>">

                                            <div class="field">
                                                <label>Title</label>
                                                <input name="title" maxlength="180"
                                                       value="<?= event_escape($event['title']) ?>"
                                                       required>
                                            </div>

                                            <div class="field">
                                                <label>Date</label>
                                                <input type="date" name="event_date"
                                                       value="<?= event_escape($event['event_date']) ?>"
                                                       required>
                                            </div>

                                            <div class="field">
                                                <label>Category</label>
                                                <select name="category" required>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?= event_escape($cat) ?>"
                                                            <?= $event['category'] === $cat
                                                                ? 'selected' : '' ?>>
                                                            <?= event_escape($cat) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="field">
                                                <label>Description</label>
                                                <textarea name="description"
                                                          required><?= event_escape($event['description']) ?></textarea>
                                            </div>

                                            <div class="field">
                                                <label>Status</label>
                                                <select name="status" required>
                                                    <option value="open"
                                                        <?= $event['status'] === 'open'
                                                            ? 'selected' : '' ?>>Open</option>
                                                    <option value="closed"
                                                        <?= $event['status'] === 'closed'
                                                            ? 'selected' : '' ?>>Closed</option>
                                                </select>
                                            </div>

                                            <div class="field">
                                                <label>Replace poster (optional)</label>
                                                <input type="file" name="poster"
                                                       accept="image/jpeg,image/png,image/webp">
                                            </div>

                                            <button type="submit">Save Changes</button>
                                        </form>
                                    </details>

                                    <form method="post"
                                          onsubmit="return confirm('Permanently delete this event?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= event_escape($csrf) ?>">
                                        <input type="hidden" name="action"
                                               value="delete">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$event['id'] ?>">

                                        <button class="danger" type="submit">
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
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
