<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirectTo(BASE_URL . '/profile.php');
}

$error = '';
$success = '';
$fullName = '';
$email = '';
$phone = '';
$gender = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '' || $phone === '' || $gender === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    } elseif (!in_array($gender, ['male', 'female', 'other'], true)) {
        $error = 'Please select a valid gender.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password and confirm password do not match.';
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $checkStmt->execute([$email, $phone]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            $error = 'Email or phone already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $pdo->beginTransaction();

            try {
                $userStmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, gender, password_hash, role, is_active, email_verified_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, 'user', 1, NULL, NOW(), NOW())
                ");
                $userStmt->execute([$fullName, $email, $phone, $gender, $passwordHash]);

                $userId = (int)$pdo->lastInsertId();

                $profileStmt = $pdo->prepare("
                    INSERT INTO user_profiles (user_id, created_at, updated_at)
                    VALUES (?, NOW(), NOW())
                ");
                $profileStmt->execute([$userId]);

                $pdo->commit();

                $success = 'Account created successfully. You can login now.';
                $fullName = '';
                $email = '';
                $phone = '';
                $gender = '';
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Something went wrong while creating your account.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width: 760px;">
        <div class="info-card">
            <div class="section-head" style="margin-bottom:20px;">
                <span class="badge">Signup</span>
                <h2>Create your account</h2>
                <p>Register with your basic details. Extra details like DOB and nationality can be added later in profile.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div style="background:#fee2e2;color:#991b1b;padding:12px 14px;border-radius:12px;margin-bottom:16px;">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div style="background:#dcfce7;color:#166534;padding:12px 14px;border-radius:12px;margin-bottom:16px;">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="contact-form">
                    <input type="text" name="full_name" placeholder="Full name" value="<?= e($fullName) ?>" required>
                    <input type="email" name="email" placeholder="Email address" value="<?= e($email) ?>" required>
                    <input type="text" name="phone" placeholder="10-digit phone number" value="<?= e($phone) ?>" maxlength="10" required>

                    <select name="gender" required>
                        <option value="">Select gender</option>
                        <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>

                    <input type="password" name="password" placeholder="Password (min 8 characters)" required>
                    <input type="password" name="confirm_password" placeholder="Confirm password" required>

                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>

            <div style="margin-top:16px;color:#64748b;">
                Already have an account?
                <a href="<?= BASE_URL ?>/login.php" style="color:#2563eb;font-weight:700;">Login here</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>