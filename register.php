
<?php
$pageTitle = 'Register';

require_once __DIR__ . '/config/db.php';

$errors = [];

$name = '';
$email = '';
$mobile = '';
$course = '';
$semester = '';
$gender = '';

$allowedCourses = ['AIML', 'CSE', 'IT', 'CE'];
$allowedSemesters = range(1, 8);
$allowedGenders = ['Male', 'Female', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form values
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // Validate name
    if ($name === '' || strlen($name) < 2 || strlen($name) > 120) {
        $errors[] = 'Name must contain between 2 and 120 characters.';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 190) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate mobile (optional)
    if ($mobile !== '' &&
        !preg_match('/^[0-9+\-\s()]{7,24}$/', $mobile)) {
        $errors[] = 'Please enter a valid mobile number.';
    }

    // Validate course
    if (!in_array($course, $allowedCourses, true)) {
        $errors[] = 'Please select a valid course.';
    }

    // Validate semester
    if (!ctype_digit($semester) ||
        !in_array((int)$semester, $allowedSemesters, true)) {
        $errors[] = 'Please select a semester from 1 to 8.';
    }

    // Validate gender
    if (!in_array($gender, $allowedGenders, true)) {
        $errors[] = 'Please select your gender.';
    }

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$terms) {
        $errors[] = 'You must accept the terms and conditions.';
    }

    // Save account if validation succeeds
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Check for an existing email address
            $check = $pdo->prepare(
                'SELECT id FROM users WHERE email = ? LIMIT 1'
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $pdo->rollBack();
                $errors[] = 'This email address is already registered.';
            } else {
                // Hash the password securely
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // Insert user account
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (name, email, mobile, password_hash, course,
                     semester, gender, role, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'student', NOW())"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $mobile !== '' ? $mobile : null,
                    $passwordHash,
                    $course,
                    (int)$semester,
                    $gender
                ]);

                $userId = (int)$pdo->lastInsertId();

                // Insert matching student record
                $studentStmt = $pdo->prepare(
                    "INSERT INTO students
                    (user_id, full_name, email, course, semester,
                     status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'active', NOW())"
                );

                $studentStmt->execute([
                    $userId,
                    $name,
                    $email,
                    $course,
                    (int)$semester
                ]);

                $pdo->commit();

                // Redirect after successful registration
                header('Location: login.php?registered=1');
                exit;
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Log details without showing database errors to visitors
            error_log(
                'StudentHub registration error: ' . $e->getMessage()
            );

            $errors[] =
                'Registration failed. Check your database columns and PHP error log.';
        }
    }
}

// Include header after POST processing so redirects work
require_once __DIR__ . '/includes/header.php';
?>

<style>
.register-page {
    max-width: 720px;
    margin: 35px auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
}

.register-page h1 {
    text-align: center;
    margin-bottom: 8px;
    color: #1f2937;
}

.register-subtitle {
    text-align: center;
    color: #6b7280;
    margin-bottom: 25px;
}

.register-form .form-group {
    margin-bottom: 17px;
}

.register-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
    color: #374151;
}

.register-form input,
.register-form select {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 12px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    font-size: 15px;
    background: #fff;
}

.register-form input:focus,
.register-form select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
}

.register-form .terms {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    font-weight: normal;
}

.register-form .terms input {
    width: auto;
    margin-top: 4px;
}

.register-button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 7px;
    background: #2563eb;
    color: white;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

.register-button:hover {
    background: #1d4ed8;
}

.register-errors {
    padding: 12px 18px;
    margin-bottom: 20px;
    border-radius: 7px;
    background: #fee2e2;
    color: #991b1b;
}

.register-errors li {
    margin: 5px 0;
}

.register-login {
    text-align: center;
    margin-top: 20px;
}

.register-login a {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
}

@media (max-width: 600px) {
    .register-page {
        margin: 20px 12px;
        padding: 20px 16px;
    }
}
</style>

<main class="register-page">

    <h1>Create an Account</h1>
    <p class="register-subtitle">
        Register for your StudentHub account
    </p>

    <?php if (!empty($errors)): ?>
        <div class="register-errors">
            <strong>Please correct the following:</strong>
            <ul>
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

    <form method="POST"
          action="register.php"
          class="register-form">

        <div class="form-group">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                maxlength="120"
                value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="190"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="mobile">Mobile Number (Optional)</label>
            <input
                type="tel"
                id="mobile"
                name="mobile"
                maxlength="24"
                value="<?= htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="form-group">
            <label for="course">Course</label>
            <select id="course" name="course" required>
                <option value="">Select Course</option>

                <?php foreach ($allowedCourses as $item): ?>
                    <option
                        value="<?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $course === $item ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="semester">Semester</label>
            <select id="semester" name="semester" required>
                <option value="">Select Semester</option>

                <?php foreach ($allowedSemesters as $item): ?>
                    <option
                        value="<?= $item ?>"
                        <?= (string)$semester === (string)$item ? 'selected' : '' ?>
                    >
                        Semester <?= $item ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>

                <?php foreach ($allowedGenders as $item): ?>
                    <option
                        value="<?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>"
                        <?= $gender === $item ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="form-group">
            <label class="terms">
                <input type="checkbox" name="terms" value="1" required>
                <span>I accept the terms and conditions.</span>
            </label>
        </div>

        <button type="submit" class="register-button">
            Create Account
        </button>

    </form>

    <p class="register-login">
        Already have an account?
        <a href="login.php">Login here</a>
    </p>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
