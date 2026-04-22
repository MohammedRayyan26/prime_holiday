<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

$error = '';
$success = '';
$fullName = '';
$email = '';
$phone = '';
$gender = '';
$otpStep = false;

$redirectTarget = normalizeRedirectTarget(
    $_GET['redirect'] ?? $_POST['redirect'] ?? '',
    BASE_URL . '/profile.php'
);

if (isLoggedIn()) {
    redirectTo($redirectTarget);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($action === 'send_signup_otp') {
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
                $otpCode = (string) random_int(100000, 999999);

                try {
                    $pdo->beginTransaction();

                    $clearStmt = $pdo->prepare("
                        UPDATE email_otps
                        SET is_used = 1
                        WHERE email = ? AND purpose = 'signup' AND is_used = 0
                    ");
                    $clearStmt->execute([$email]);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO email_otps (
                            user_id, email, otp_code, purpose, expires_at, verified_at, is_used, created_at
                        ) VALUES (
                            NULL, ?, ?, 'signup', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, NOW()
                        )
                    ");
                    $insertStmt->execute([$email, $otpCode]);

                    $mailError = null;
                    $mailSent = sendSignupOtpEmail($email, $fullName, $otpCode, $mailError);

                    if (!$mailSent) {
                        throw new Exception($mailError ?: 'Unable to send signup OTP.');
                    }

                    $_SESSION['signup_pending'] = [
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'gender' => $gender,
                        'password' => $password,
                        'confirm_password' => $confirmPassword,
                        'redirect' => $redirectTarget,
                    ];

                    $pdo->commit();

                    $success = 'OTP sent to your email address. Please verify to complete signup.';
                    $otpStep = true;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Could not send signup OTP right now. ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'verify_signup_otp') {
        $otpStep = true;
        $otpCode = trim($_POST['otp_code'] ?? '');

        $pending = $_SESSION['signup_pending'] ?? null;

        if (!$pending || !is_array($pending)) {
            $error = 'Signup session expired. Please fill the form again.';
            $otpStep = false;
        } else {
            $fullName = trim((string)($pending['full_name'] ?? ''));
            $email = trim((string)($pending['email'] ?? ''));
            $phone = trim((string)($pending['phone'] ?? ''));
            $gender = trim((string)($pending['gender'] ?? ''));
            $password = (string)($pending['password'] ?? '');
            $confirmPassword = (string)($pending['confirm_password'] ?? '');
            $redirectTarget = normalizeRedirectTarget(
                $pending['redirect'] ?? $redirectTarget,
                BASE_URL . '/profile.php'
            );

            if ($otpCode === '') {
                $error = 'Please enter the OTP.';
            } else {
                $otpStmt = $pdo->prepare("
                    SELECT id
                    FROM email_otps
                    WHERE email = ?
                      AND otp_code = ?
                      AND purpose = 'signup'
                      AND is_used = 0
                      AND verified_at IS NULL
                      AND expires_at >= NOW()
                    ORDER BY id DESC
                    LIMIT 1
                ");
                $otpStmt->execute([$email, $otpCode]);
                $otpRow = $otpStmt->fetch();

                if (!$otpRow) {
                    $error = 'Invalid or expired OTP.';
                } else {
                    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
                    $checkStmt->execute([$email, $phone]);
                    $exists = $checkStmt->fetch();

                    if ($exists) {
                        $error = 'Email or phone already exists.';
                    } else {
                        try {
                            $pdo->beginTransaction();

                            $updateOtpStmt = $pdo->prepare("
                                UPDATE email_otps
                                SET is_used = 1, verified_at = NOW()
                                WHERE id = ?
                            ");
                            $updateOtpStmt->execute([(int)$otpRow['id']]);

                            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                            $userStmt = $pdo->prepare("
                                INSERT INTO users (
                                    full_name, email, phone, gender, password_hash, role, is_active, email_verified_at, created_at, updated_at
                                ) VALUES (
                                    ?, ?, ?, ?, ?, 'user', 1, NOW(), NOW(), NOW()
                                )
                            ");
                            $userStmt->execute([$fullName, $email, $phone, $gender, $passwordHash]);

                            $userId = (int)$pdo->lastInsertId();

                            $profileStmt = $pdo->prepare("
                                INSERT INTO user_profiles (user_id, created_at, updated_at)
                                VALUES (?, NOW(), NOW())
                            ");
                            $profileStmt->execute([$userId]);

                            $pdo->commit();

                            unset($_SESSION['signup_pending']);

                            $_SESSION['user_id'] = $userId;
                            $_SESSION['user_name'] = $fullName;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_role'] = 'user';

                            redirectTo($redirectTarget);
                        } catch (Throwable $e) {
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            $error = 'Something went wrong while creating your account.';
                        }
                    }
                }
            }
        }
    }

    if ($action === 'resend_signup_otp') {
        $pending = $_SESSION['signup_pending'] ?? null;

        if (!$pending || !is_array($pending)) {
            $error = 'Signup session expired. Please fill the form again.';
            $otpStep = false;
        } else {
            $fullName = trim((string)($pending['full_name'] ?? ''));
            $email = trim((string)($pending['email'] ?? ''));
            $phone = trim((string)($pending['phone'] ?? ''));
            $gender = trim((string)($pending['gender'] ?? ''));
            $redirectTarget = normalizeRedirectTarget(
                $pending['redirect'] ?? $redirectTarget,
                BASE_URL . '/profile.php'
            );
            $otpStep = true;

            $otpCode = (string) random_int(100000, 999999);

            try {
                $pdo->beginTransaction();

                $clearStmt = $pdo->prepare("
                    UPDATE email_otps
                    SET is_used = 1
                    WHERE email = ? AND purpose = 'signup' AND is_used = 0
                ");
                $clearStmt->execute([$email]);

                $insertStmt = $pdo->prepare("
                    INSERT INTO email_otps (
                        user_id, email, otp_code, purpose, expires_at, verified_at, is_used, created_at
                    ) VALUES (
                        NULL, ?, ?, 'signup', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, NOW()
                    )
                ");
                $insertStmt->execute([$email, $otpCode]);

                $mailError = null;
                $mailSent = sendSignupOtpEmail($email, $fullName, $otpCode, $mailError);

                if (!$mailSent) {
                    throw new Exception($mailError ?: 'Unable to resend signup OTP.');
                }

                $pdo->commit();

                $success = 'OTP resent to your email address.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Could not resend signup OTP right now. ' . $e->getMessage();
            }
        }
    }
}

$hasPendingSignup = isset($_SESSION['signup_pending']) && is_array($_SESSION['signup_pending']);
if ($hasPendingSignup && $otpStep === false && $error === '' && $success === '') {
    $pending = $_SESSION['signup_pending'];
    $fullName = trim((string)($pending['full_name'] ?? ''));
    $email = trim((string)($pending['email'] ?? ''));
    $phone = trim((string)($pending['phone'] ?? ''));
    $gender = trim((string)($pending['gender'] ?? ''));
    $otpStep = true;
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

            <?php if (!$otpStep): ?>
                <form method="post" action="">
                    <input type="hidden" name="action" value="send_signup_otp">
                    <input type="hidden" name="redirect" value="<?= e($redirectTarget) ?>">

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
            <?php else: ?>
                <div style="border:1px solid #dbe4f0;border-radius:18px;padding:20px;background:#f8fbff;">
                    <h3 style="margin-top:0;margin-bottom:14px;">Verify Email OTP</h3>
                    <p style="margin:0 0 14px;color:#475569;">
                        We have sent a 6-digit OTP to <strong><?= e($email) ?></strong>.
                    </p>

                    <form method="post" action="">
                        <input type="hidden" name="action" value="verify_signup_otp">
                        <input type="hidden" name="redirect" value="<?= e($redirectTarget) ?>">
                        <div class="contact-form">
                            <input type="text" name="otp_code" placeholder="Enter 6-digit OTP" maxlength="6" required>
                            <button type="submit" class="btn btn-primary">Verify OTP & Create Account</button>
                        </div>
                    </form>

                    <form method="post" action="" style="margin-top:12px;">
                        <input type="hidden" name="action" value="resend_signup_otp">
                        <input type="hidden" name="redirect" value="<?= e($redirectTarget) ?>">
                        <button type="submit" class="btn btn-soft" style="width:100%;">Resend OTP</button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="margin-top:16px;color:#64748b;">
                Already have an account?
                <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode($redirectTarget) ?>" style="color:#2563eb;font-weight:700;">Login here</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>