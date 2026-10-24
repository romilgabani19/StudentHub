<?php
$pageTitle = 'Event Management';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_admin();
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM events WHERE id=?');
            $stmt->execute([$id]);
            $message = 'Event deleted.';
            audit($pdo, (int)$_SESSION['user']['id'], 'event.delete', 'Event ID ' . $id);
        }
    } elseif (in_array($action, ['add', 'update'], true)) {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $title = trim($_POST['title'] ?? '');
        $date = trim($_POST['event_date'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $category === '' || $description === '') $error = 'Fill in all event fields with a valid date.';
        else {
            $poster = null;
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['poster'];
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $mime = '';
                if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) $error = 'Poster must be an image under 2 MB.';
                else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    if (!isset($allowed[$mime])) $error = 'Only JPG, PNG, and WebP posters are accepted.';
                    else {
                        $dir = __DIR__ . '/uploads';
                        if (!is_dir($dir)) mkdir($dir, 0750, true);
                        $poster = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
                        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $poster)) $error = 'Could not save poster.';
                    }
                }
            }
            if (!$error) {
                if ($action === 'add') {
                    $stmt = $pdo->prepare('INSERT INTO events(title,event_date,category,description,poster,created_at) VALUES(?,?,?,?,?,NOW())');
                    $stmt->execute([$title, $date, $category, $description, $poster]);
                    $message = 'Event added.';
                    audit($pdo, (int)$_SESSION['user']['id'], 'event.add', $title);
                } else {
                    $stmt = $pdo->prepare('UPDATE events SET title=?,event_date=?,category=?,description=?,poster=COALESCE(?,poster) WHERE id=?');
                    $stmt->execute([$title, $date, $category, $description, $poster, $id]);
                    $message = 'Event updated.';
                    audit($pdo, (int)$_SESSION['user']['id'], 'event.update', $title);
                }
            }
        }
    }
}
$events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC LIMIT 100')->fetchAll();
?>
<header class="page-heading">
    <p class="eyebrow">ADMIN TOOLS</p>
    <h1>Event management</h1>
    <p>Add and manage campus events. Optional poster uploads accept JPG, PNG, or WebP up to 2 MB.</p>
</header><?php if ($message): ?><p class="success-message" role="status"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="error-message" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel">
    <h2>Add event</h2>
    <form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="add">
        <div class="form-grid">
            <div class="field"><label for="title">Title</label><input id="title" name="title" required></div>
            <div class="field"><label for="event_date">Date</label><input id="event_date" name="event_date" type="date" required></div>
            <div class="field"><label for="category">Category</label><select id="category" name="category" required>
                    <option>Workshop</option>
                    <option>Competition</option>
                    <option>Culture</option>
                    <option>Technology</option>
                    <option>Sports</option>
                </select></div>
            <div class="field"><label for="poster">Poster (optional)</label><input id="poster" name="poster" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
        </div>
        <div class="field"><label for="description">Description</label><textarea id="description" name="description" required></textarea></div><button class="button">Add event</button>
    </form>
</section>
<section class="panel">
    <h2>Existing events</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody><?php foreach ($events as $event): ?><tr>
                        <td><?= e($event['title']) ?></td>
                        <td><?= e($event['event_date']) ?></td>
                        <td><?= e($event['category']) ?></td>
                        <td>
                            <details>
                                <summary>Edit</summary>
                                <form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $event['id'] ?>">
                                    <div class="field"><label>Title<input name="title" value="<?= e($event['title']) ?>" required></label></div>
                                    <div class="field"><label>Date<input name="event_date" type="date" value="<?= e($event['event_date']) ?>" required></label></div>
                                    <div class="field"><label>Category<input name="category" value="<?= e($event['category']) ?>" required></label></div>
                                    <div class="field"><label>Description<textarea name="description" required><?= e($event['description']) ?></textarea></label></div>
                                    <div class="field"><label>Replace poster<input name="poster" type="file" accept=".jpg,.jpeg,.png,.webp"></label></div><button class="small-button">Save changes</button>
                                </form>
                            </details>
                            <form method="post" onsubmit="return confirm('Delete this event?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $event['id'] ?>"><button class="small-button">Delete</button></form>
                        </td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</section><?php require_once __DIR__ . '/includes/footer.php'; ?>