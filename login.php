<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirectTo(BASE_URL . '/admin/dashboard.php');
    }
    redirectTo(BASE_URL . '/profile.php');
}

$error = '';
$success = '';
$email = '';
$otpStep = false;
$showPasswordLogin = false;
$forgotPasswordStep = false;
$forgotPasswordVerifyStep = false;

if (!function_exists('sendOtpLoginEmail')) {
    function sendOtpLoginEmail(string $toEmail, string $toName, string $otpCode, ?string &$errorMessage = null): bool
    {
        $errorMessage = null;
        return sendForgotPasswordOtpEmail($toEmail, $toName, $otpCode, $errorMessage);
    }
}

if (!function_exists('sendForgotPasswordOtpEmail')) {
    function sendForgotPasswordOtpEmail(string $toEmail, string $toName, string $otpCode, ?string &$errorMessage = null): bool
    {
        $errorMessage = null;

        try {
            $exceptionFile = __DIR__ . '/phpmailer/src/Exception.php';
            $phpMailerFile = __DIR__ . '/phpmailer/src/PHPMailer.php';
            $smtpFile = __DIR__ . '/phpmailer/src/SMTP.php';

            if (!file_exists($exceptionFile) || !file_exists($phpMailerFile) || !file_exists($smtpFile)) {
                $errorMessage = 'PHPMailer files not found.';
                return false;
            }

            require_once $exceptionFile;
            require_once $phpMailerFile;
            require_once $smtpFile;

            $mailClass = 'PHPMailer\\PHPMailer\\PHPMailer';
            $mail = new $mailClass(true);

            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = trim((string)MAIL_USERNAME);
            $mail->Password = trim((string)MAIL_PASSWORD);
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->Timeout = 30;

            $mail->setFrom(trim((string)MAIL_FROM_ADDRESS), (string)MAIL_FROM_NAME);
            $mail->addAddress(trim($toEmail), $toName);

            $mail->isHTML(true);
            $mail->Subject = 'Prime Holiday Password Reset OTP';
            $mail->Body = '
                <div style="font-family:Arial,sans-serif;background:#f6f8fc;padding:24px;">
                    <div style="max-width:620px;margin:0 auto;background:#fff;border-radius:18px;overflow:hidden;border:1px solid #e5eaf2;">
                        <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;padding:24px;">
                            <h2 style="margin:0;">Prime Holiday</h2>
                            <p style="margin:8px 0 0;">Password reset OTP</p>
                        </div>
                        <div style="padding:24px;color:#172033;">
                            <p>Hi <strong>' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
                            <p>Use this OTP to reset your password:</p>
                            <div style="margin:22px 0;padding:18px;text-align:center;background:#f8fbff;border:1px solid #dbe4f0;border-radius:14px;">
                                <div style="font-size:32px;font-weight:800;letter-spacing:8px;color:#2563eb;">' . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8') . '</div>
                            </div>
                            <p>This OTP is valid for <strong>10 minutes</strong>.</p>
                        </div>
                    </div>
                </div>
            ';
            $mail->AltBody = "Prime Holiday Password Reset OTP\nOTP: " . $otpCode . "\nValid for 10 minutes.";
            $mail->send();

            return true;
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            return false;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'password_login') {
        $showPasswordLogin = true;
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Please enter email and password.';
        } else {
            $stmt = $pdo->prepare("
                SELECT id, full_name, email, password_hash, role, is_active
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Invalid email or password.';
            } elseif ((int)$user['is_active'] !== 1) {
                $error = 'Your account is inactive.';
            } elseif (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
                $error = 'Invalid email or password.';
            } else {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                $updateStmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                $updateStmt->execute([(int)$user['id']]);

                if ($user['role'] === 'admin') {
                    redirectTo(BASE_URL . '/admin/dashboard.php');
                }
                redirectTo(BASE_URL . '/profile.php');
            }
        }
    }

    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $pdo->prepare("
                SELECT id, full_name, email, role, is_active
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'No account found with this email.';
            } elseif ((int)$user['is_active'] !== 1) {
                $error = 'Your account is inactive.';
            } else {
                $otpCode = (string)random_int(100000, 999999);

                try {
                    $pdo->beginTransaction();

                    $clearStmt = $pdo->prepare("
                        UPDATE email_otps
                        SET is_used = 1
                        WHERE email = ? AND purpose = 'login' AND is_used = 0
                    ");
                    $clearStmt->execute([$email]);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO email_otps (user_id, email, otp_code, purpose, expires_at, verified_at, is_used, created_at)
                        VALUES (?, ?, ?, 'login', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, NOW())
                    ");
                    $insertStmt->execute([
                        (int)$user['id'],
                        $user['email'],
                        $otpCode
                    ]);

                    $mailError = null;
                    $mailSent = sendOtpLoginEmail($user['email'], $user['full_name'], $otpCode, $mailError);

                    if (!$mailSent) {
                        throw new Exception($mailError ?: 'Unable to send OTP email.');
                    }

                    $pdo->commit();

                    $success = 'OTP sent to your email address.';
                    $otpStep = true;
                    $showPasswordLogin = false;
                    $forgotPasswordStep = false;
                    $forgotPasswordVerifyStep = false;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Could not send OTP right now. ' . $e->getMessage();
                    $otpStep = true;
                }
            }
        }
    }

    if ($action === 'verify_otp') {
        $email = trim($_POST['email'] ?? '');
        $otpCode = trim($_POST['otp_code'] ?? '');
        $otpStep = true;

        if ($email === '' || $otpCode === '') {
            $error = 'Please enter email and OTP.';
        } else {
            $stmt = $pdo->prepare("
                SELECT eo.id AS otp_id, eo.user_id, u.full_name, u.email, u.role, u.is_active
                FROM email_otps eo
                INNER JOIN users u ON u.id = eo.user_id
                WHERE eo.email = ?
                  AND eo.otp_code = ?
                  AND eo.purpose = 'login'
                  AND eo.is_used = 0
                  AND eo.verified_at IS NULL
                  AND eo.expires_at >= NOW()
                ORDER BY eo.id DESC
                LIMIT 1
            ");
            $stmt->execute([$email, $otpCode]);
            $otpRow = $stmt->fetch();

            if (!$otpRow) {
                $error = 'Invalid or expired OTP.';
            } elseif ((int)$otpRow['is_active'] !== 1) {
                $error = 'Your account is inactive.';
            } else {
                $updateOtp = $pdo->prepare("
                    UPDATE email_otps
                    SET is_used = 1, verified_at = NOW()
                    WHERE id = ?
                ");
                $updateOtp->execute([(int)$otpRow['otp_id']]);

                $_SESSION['user_id'] = (int)$otpRow['user_id'];
                $_SESSION['user_name'] = $otpRow['full_name'];
                $_SESSION['user_email'] = $otpRow['email'];
                $_SESSION['user_role'] = $otpRow['role'];

                $updateUser = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                $updateUser->execute([(int)$otpRow['user_id']]);

                if ($otpRow['role'] === 'admin') {
                    redirectTo(BASE_URL . '/admin/dashboard.php');
                }
                redirectTo(BASE_URL . '/profile.php');
            }
        }
    }

    if ($action === 'forgot_password_send_otp') {
        $email = trim($_POST['email'] ?? '');
        $showPasswordLogin = true;
        $forgotPasswordStep = true;

        if ($email === '') {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $pdo->prepare("
                SELECT id, full_name, email, is_active
                FROM users
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'No account found with this email.';
            } elseif ((int)$user['is_active'] !== 1) {
                $error = 'Your account is inactive.';
            } else {
                $otpCode = (string)random_int(100000, 999999);

                try {
                    $pdo->beginTransaction();

                    $clearStmt = $pdo->prepare("
                        UPDATE email_otps
                        SET is_used = 1
                        WHERE email = ? AND purpose = 'forgot_password' AND is_used = 0
                    ");
                    $clearStmt->execute([$email]);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO email_otps (user_id, email, otp_code, purpose, expires_at, verified_at, is_used, created_at)
                        VALUES (?, ?, ?, 'forgot_password', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, NOW())
                    ");
                    $insertStmt->execute([
                        (int)$user['id'],
                        $user['email'],
                        $otpCode
                    ]);

                    $mailError = null;
                    $mailSent = sendForgotPasswordOtpEmail($user['email'], $user['full_name'], $otpCode, $mailError);

                    if (!$mailSent) {
                        throw new Exception($mailError ?: 'Unable to send forgot password OTP.');
                    }

                    $pdo->commit();

                    $success = 'Password reset OTP sent to your email.';
                    $forgotPasswordVerifyStep = true;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Could not send reset OTP right now. ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'forgot_password_reset') {
        $email = trim($_POST['email'] ?? '');
        $otpCode = trim($_POST['otp_code'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        $showPasswordLogin = true;
        $forgotPasswordStep = true;
        $forgotPasswordVerifyStep = true;

        if ($email === '' || $otpCode === '' || $newPassword === '' || $confirmPassword === '') {
            $error = 'Please fill all reset password fields.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirm password do not match.';
        } else {
            $stmt = $pdo->prepare("
                SELECT eo.id AS otp_id, eo.user_id, u.email
                FROM email_otps eo
                INNER JOIN users u ON u.id = eo.user_id
                WHERE eo.email = ?
                  AND eo.otp_code = ?
                  AND eo.purpose = 'forgot_password'
                  AND eo.is_used = 0
                  AND eo.verified_at IS NULL
                  AND eo.expires_at >= NOW()
                ORDER BY eo.id DESC
                LIMIT 1
            ");
            $stmt->execute([$email, $otpCode]);
            $otpRow = $stmt->fetch();

            if (!$otpRow) {
                $error = 'Invalid or expired reset OTP.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $updateOtp = $pdo->prepare("
                        UPDATE email_otps
                        SET is_used = 1, verified_at = NOW()
                        WHERE id = ?
                    ");
                    $updateOtp->execute([(int)$otpRow['otp_id']]);

                    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

                    $updateUser = $pdo->prepare("
                        UPDATE users
                        SET password_hash = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateUser->execute([$newHash, (int)$otpRow['user_id']]);

                    $pdo->commit();

                    $success = 'Password reset successfully. You can login now.';
                    $forgotPasswordStep = false;
                    $forgotPasswordVerifyStep = false;
                    $showPasswordLogin = true;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = 'Unable to reset password right now.';
                }
            }
        }
    }

    if ($action === 'show_password_login') {
        $showPasswordLogin = true;
        $email = trim($_POST['email'] ?? '');
        $otpStep = false;
        $forgotPasswordStep = false;
        $forgotPasswordVerifyStep = false;
    }

    if ($action === 'show_otp_login') {
        $showPasswordLogin = false;
        $email = trim($_POST['email'] ?? '');
        $forgotPasswordStep = false;
        $forgotPasswordVerifyStep = false;
    }

    if ($action === 'show_forgot_password') {
        $showPasswordLogin = true;
        $forgotPasswordStep = true;
        $email = trim($_POST['email'] ?? '');
        $otpStep = false;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width: 620px;">
        <div class="info-card">
            <div class="section-head" style="margin-bottom:20px;">
                <span class="badge">Login</span>
                <h2>Welcome back</h2>
                <p>Login with your email and one-time OTP.</p>
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

            <?php if (!$showPasswordLogin): ?>
                <div style="border:1px solid #dbe4f0;border-radius:18px;padding:20px;background:#f8fbff;">
                    <h3 style="margin-top:0;margin-bottom:14px;">Login with OTP</h3>

                    <?php if (!$otpStep): ?>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="send_otp">
                            <div class="contact-form">
                                <input type="email" name="email" placeholder="Enter your email" value="<?= e($email) ?>" required>
                                <button type="submit" class="btn btn-primary">Send OTP</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="verify_otp">
                            <div class="contact-form">
                                <input type="email" name="email" placeholder="Enter your email" value="<?= e($email) ?>" required>
                                <input type="text" name="otp_code" placeholder="Enter 6-digit OTP" maxlength="6" required>
                                <button type="submit" class="btn btn-primary">Verify OTP</button>
                            </div>
                        </form>

                        <form method="post" action="" style="margin-top:12px;">
                            <input type="hidden" name="action" value="send_otp">
                            <input type="hidden" name="email" value="<?= e($email) ?>">
                            <button type="submit" class="btn btn-soft" style="width:100%;">Resend OTP</button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="" style="margin-top:14px;">
                        <input type="hidden" name="action" value="show_password_login">
                        <input type="hidden" name="email" value="<?= e($email) ?>">
                        <button type="submit" class="btn btn-soft" style="width:100%;">Use Password Instead</button>
                    </form>
                </div>
            <?php else: ?>
                <div style="border:1px solid #dbe4f0;border-radius:18px;padding:20px;">
                    <h3 style="margin-top:0;margin-bottom:14px;">Login with Password</h3>

                    <form method="post" action="">
                        <input type="hidden" name="action" value="password_login">
                        <div class="contact-form">
                            <input type="email" name="email" placeholder="Enter your email" value="<?= e($email) ?>" required>
                            <input type="password" name="password" placeholder="Enter your password" required>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>

                    <form method="post" action="" style="margin-top:12px;">
                        <input type="hidden" name="action" value="show_forgot_password">
                        <input type="hidden" name="email" value="<?= e($email) ?>">
                        <button type="submit" class="btn btn-soft" style="width:100%;">Forgot Password?</button>
                    </form>

                    <?php if ($forgotPasswordStep): ?>
                        <div style="margin-top:16px;padding:16px;border:1px solid #dbe4f0;border-radius:14px;background:#f8fbff;">
                            <h4 style="margin-top:0;">Reset Password</h4>

                            <?php if (!$forgotPasswordVerifyStep): ?>
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="forgot_password_send_otp">
                                    <div class="contact-form">
                                        <input type="email" name="email" placeholder="Enter your account email" value="<?= e($email) ?>" required>
                                        <button type="submit" class="btn btn-primary">Send Reset OTP</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <form method="post" action="">
                                    <input type="hidden" name="action" value="forgot_password_reset">
                                    <div class="contact-form">
                                        <input type="email" name="email" placeholder="Enter your email" value="<?= e($email) ?>" required>
                                        <input type="text" name="otp_code" placeholder="Enter reset OTP" maxlength="6" required>
                                        <input type="password" name="new_password" placeholder="New password (min 8 chars)" required>
                                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                                        <button type="submit" class="btn btn-primary">Reset Password</button>
                                    </div>
                                </form>

                                <form method="post" action="" style="margin-top:12px;">
                                    <input type="hidden" name="action" value="forgot_password_send_otp">
                                    <input type="hidden" name="email" value="<?= e($email) ?>">
                                    <button type="submit" class="btn btn-soft" style="width:100%;">Resend Reset OTP</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="" style="margin-top:14px;">
                        <input type="hidden" name="action" value="show_otp_login">
                        <input type="hidden" name="email" value="<?= e($email) ?>">
                        <button type="submit" class="btn btn-soft" style="width:100%;">Use Email OTP Instead</button>
                    </form>
                </div>
            <?php endif; ?>

            <div style="margin-top:16px;color:#64748b;">
                Don’t have an account?
                <a href="<?= BASE_URL ?>/signup.php" style="color:#2563eb;font-weight:700;">Create one</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>