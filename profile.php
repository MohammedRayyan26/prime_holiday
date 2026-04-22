<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';

$user = null;
$profile = null;
$bookings = [];

$activeTab = trim((string)($_GET['tab'] ?? $_POST['active_tab'] ?? 'profile'));
$allowedTabs = ['profile', 'password', 'bookings'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'profile';
}

$passwordOtpStep = false;

function profileTabUrl(string $tab): string {
    return BASE_URL . '/profile.php?tab=' . urlencode($tab);
}

function canCancelBooking(array $booking): bool {
    $status = strtolower(trim((string)($booking['booking_status'] ?? '')));
    return in_array($status, ['pending', 'confirmed'], true);
}

function sendBookingMail(string $toEmail, string $toName, string $subject, string $html, ?string &$error = null): bool
{
    $error = null;

    if ($toEmail === '') {
        $error = 'Recipient email is empty.';
        return false;
    }

    try {
        if (function_exists('sendCustomEmail')) {
            return (bool) sendCustomEmail($toEmail, $toName, $subject, $html, $error);
        }

        if (function_exists('sendGeneralEmail')) {
            return (bool) sendGeneralEmail($toEmail, $toName, $subject, $html, $error);
        }

        if (function_exists('sendOtpLoginEmail')) {
            // fallback not suitable for generic mail body, so skip
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        return false;
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: Prime Holiday <no-reply@primeholiday.com>';

    $sent = @mail($toEmail, $subject, $html, implode("\r\n", $headers));
    if (!$sent) {
        $error = 'Mail send failed.';
    }

    return $sent;
}

try {
    $userStmt = $pdo->prepare("
        SELECT id, full_name, email, phone, gender, role, password_hash, created_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    $profileStmt = $pdo->prepare("
        SELECT dob, nationality, marital_status, address_line1, address_line2, city, state, country, postal_code, profile_photo
        FROM user_profiles
        WHERE user_id = ?
        LIMIT 1
    ");
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch();
} catch (Throwable $e) {
    $error = 'Unable to load profile data.';
}

/*
|--------------------------------------------------------------------------
| Reset OTP mode on normal refresh / fresh GET
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['profile_password_reset_email']);
    $passwordOtpStep = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $activeTab = 'profile';

    $dob = trim((string)($_POST['dob'] ?? ''));
    $nationality = trim((string)($_POST['nationality'] ?? ''));
    $maritalStatus = trim((string)($_POST['marital_status'] ?? ''));
    $address1 = trim((string)($_POST['address_line1'] ?? ''));
    $address2 = trim((string)($_POST['address_line2'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $state = trim((string)($_POST['state'] ?? ''));
    $country = trim((string)($_POST['country'] ?? ''));
    $postalCode = trim((string)($_POST['postal_code'] ?? ''));

    if ($maritalStatus !== '' && !in_array($maritalStatus, ['single', 'married', 'other'], true)) {
        $error = 'Invalid marital status.';
    } else {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ? LIMIT 1");
            $checkStmt->execute([$userId]);
            $profileRow = $checkStmt->fetch();

            if ($profileRow) {
                $saveStmt = $pdo->prepare("
                    UPDATE user_profiles
                    SET dob = ?, nationality = ?, marital_status = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, country = ?, postal_code = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $saveStmt->execute([
                    $dob !== '' ? $dob : null,
                    $nationality !== '' ? $nationality : null,
                    $maritalStatus !== '' ? $maritalStatus : null,
                    $address1 !== '' ? $address1 : null,
                    $address2 !== '' ? $address2 : null,
                    $city !== '' ? $city : null,
                    $state !== '' ? $state : null,
                    $country !== '' ? $country : null,
                    $postalCode !== '' ? $postalCode : null,
                    $userId
                ]);
            } else {
                $saveStmt = $pdo->prepare("
                    INSERT INTO user_profiles (
                        user_id, dob, nationality, marital_status, address_line1, address_line2, city, state, country, postal_code, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $saveStmt->execute([
                    $userId,
                    $dob !== '' ? $dob : null,
                    $nationality !== '' ? $nationality : null,
                    $maritalStatus !== '' ? $maritalStatus : null,
                    $address1 !== '' ? $address1 : null,
                    $address2 !== '' ? $address2 : null,
                    $city !== '' ? $city : null,
                    $state !== '' ? $state : null,
                    $country !== '' ? $country : null,
                    $postalCode !== '' ? $postalCode : null
                ]);
            }

            $success = 'Profile updated successfully.';

            $profileStmt = $pdo->prepare("
                SELECT dob, nationality, marital_status, address_line1, address_line2, city, state, country, postal_code, profile_photo
                FROM user_profiles
                WHERE user_id = ?
                LIMIT 1
            ");
            $profileStmt->execute([$userId]);
            $profile = $profileStmt->fetch();
        } catch (Throwable $e) {
            $error = 'Unable to update profile right now.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $activeTab = 'password';
    $passwordOtpStep = false;

    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmNewPassword = (string)($_POST['confirm_new_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmNewPassword === '') {
        $error = 'Please fill all password fields.';
    } elseif (empty($user['password_hash']) || !password_verify($currentPassword, $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmNewPassword) {
        $error = 'New password and confirm password do not match.';
    } elseif ($currentPassword === $newPassword) {
        $error = 'New password must be different from current password.';
    } else {
        try {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                UPDATE users
                SET password_hash = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$newHash, $userId]);

            $success = 'Password changed successfully.';

            $userStmt = $pdo->prepare("
                SELECT id, full_name, email, phone, gender, role, password_hash, created_at
                FROM users
                WHERE id = ?
                LIMIT 1
            ");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();

            unset($_SESSION['profile_password_reset_email']);
        } catch (Throwable $e) {
            $error = 'Unable to change password right now.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_password_reset_otp'])) {
    $activeTab = 'password';
    $passwordOtpStep = true;

    if (empty($user['email'])) {
        $error = 'No email address found for your account.';
    } else {
        $otpCode = (string) random_int(100000, 999999);

        try {
            $pdo->beginTransaction();

            $clearStmt = $pdo->prepare("
                UPDATE email_otps
                SET is_used = 1
                WHERE email = ? AND purpose = 'forgot_password' AND is_used = 0
            ");
            $clearStmt->execute([$user['email']]);

            $insertStmt = $pdo->prepare("
                INSERT INTO email_otps (
                    user_id, email, otp_code, purpose, expires_at, verified_at, is_used, created_at
                ) VALUES (
                    ?, ?, ?, 'forgot_password', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, 0, NOW()
                )
            ");
            $insertStmt->execute([
                $userId,
                $user['email'],
                $otpCode
            ]);

            $mailError = null;
            $mailSent = sendOtpLoginEmail($user['email'], $user['full_name'] ?? 'User', $otpCode, $mailError);

            if (!$mailSent) {
                throw new Exception($mailError ?: 'Unable to send reset OTP.');
            }

            $pdo->commit();

            $success = 'Password reset OTP sent to your email.';
            $_SESSION['profile_password_reset_email'] = $user['email'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Could not send reset OTP right now. ' . $e->getMessage();
            $passwordOtpStep = false;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_with_otp'])) {
    $activeTab = 'password';
    $passwordOtpStep = true;

    $otpCode = trim((string)($_POST['otp_code'] ?? ''));
    $newPassword = (string)($_POST['otp_new_password'] ?? '');
    $confirmPassword = (string)($_POST['otp_confirm_new_password'] ?? '');

    if ($otpCode === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'Please fill all OTP reset password fields.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirm password do not match.';
    } else {
        $stmt = $pdo->prepare("
            SELECT eo.id AS otp_id, eo.user_id
            FROM email_otps eo
            WHERE eo.user_id = ?
              AND eo.email = ?
              AND eo.otp_code = ?
              AND eo.purpose = 'forgot_password'
              AND eo.is_used = 0
              AND eo.verified_at IS NULL
              AND eo.expires_at >= NOW()
            ORDER BY eo.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            $userId,
            $user['email'],
            $otpCode
        ]);
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
                $updateUser->execute([$newHash, $userId]);

                $pdo->commit();

                unset($_SESSION['profile_password_reset_email']);

                $success = 'Password reset successfully.';
                $passwordOtpStep = false;

                $userStmt = $pdo->prepare("
                    SELECT id, full_name, email, phone, gender, role, password_hash, created_at
                    FROM users
                    WHERE id = ?
                    LIMIT 1
                ");
                $userStmt->execute([$userId]);
                $user = $userStmt->fetch();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Unable to reset password right now.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_password_otp'])) {
    $activeTab = 'password';
    $passwordOtpStep = false;
    unset($_SESSION['profile_password_reset_email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_user'])) {
    $activeTab = 'bookings';

    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($bookingId <= 0) {
        $error = 'Invalid booking selected.';
    } else {
        try {
            $bookingStmt = $pdo->prepare("
                SELECT
                    b.id,
                    b.user_id,
                    b.booking_reference,
                    b.customer_name,
                    b.customer_email,
                    b.customer_phone,
                    b.travel_date,
                    b.total_amount,
                    b.booking_status,
                    b.payment_status,
                    b.created_at,
                    p.package_name
                FROM bookings b
                INNER JOIN packages p ON p.id = b.package_id
                WHERE b.id = ? AND b.user_id = ?
                LIMIT 1
            ");
            $bookingStmt->execute([$bookingId, $userId]);
            $bookingRow = $bookingStmt->fetch();

            if (!$bookingRow) {
                $error = 'Booking not found.';
            } elseif (!canCancelBooking($bookingRow)) {
                $error = 'This booking cannot be cancelled now.';
            } else {
                $pdo->beginTransaction();

                $currentPaymentStatus = strtolower(trim((string)($bookingRow['payment_status'] ?? '')));
                $newPaymentStatus = $currentPaymentStatus === 'paid' ? 'refunded' : ($bookingRow['payment_status'] ?? 'pending');

                $updateStmt = $pdo->prepare("
                    UPDATE bookings
                    SET booking_status = 'cancelled',
                        payment_status = ?,
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                    LIMIT 1
                ");
                $updateStmt->execute([
                    $newPaymentStatus,
                    $bookingId,
                    $userId
                ]);

                $pdo->commit();

                $customerName = trim((string)($bookingRow['customer_name'] ?? '')) !== ''
                    ? (string)$bookingRow['customer_name']
                    : (string)($user['full_name'] ?? 'Customer');

                $customerEmail = trim((string)($bookingRow['customer_email'] ?? ''));
                if ($customerEmail === '' && !empty($user['email'])) {
                    $customerEmail = (string)$user['email'];
                }

                if ($customerEmail !== '') {
                    $subject = 'Booking Cancelled - ' . (string)$bookingRow['booking_reference'];
                    $html = '
                        <div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.7;color:#1e293b;">
                            <p>Dear ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                            <p>Your booking <strong>' . htmlspecialchars((string)$bookingRow['booking_reference'], ENT_QUOTES, 'UTF-8') . '</strong> for <strong>' . htmlspecialchars((string)$bookingRow['package_name'], ENT_QUOTES, 'UTF-8') . '</strong> has been cancelled successfully.</p>
                            <p>If any amount was paid, it will be refunded within <strong>5 to 6 working days</strong>.</p>
                            <p>
                                <strong>Travel Date:</strong> ' . htmlspecialchars((string)$bookingRow['travel_date'], ENT_QUOTES, 'UTF-8') . '<br>
                                <strong>Amount:</strong> ' . htmlspecialchars(formatPrice($bookingRow['total_amount']), ENT_QUOTES, 'UTF-8') . '
                            </p>
                            <p>Regards,<br>Prime Holiday Team</p>
                        </div>
                    ';
                    $mailError = null;
                    sendBookingMail($customerEmail, $customerName, $subject, $html, $mailError);
                }

                try {
                    $adminStmt = $pdo->query("
                        SELECT full_name, email
                        FROM users
                        WHERE role = 'admin' AND is_active = 1 AND email IS NOT NULL AND email <> ''
                        ORDER BY id ASC
                    ");
                    $admins = $adminStmt ? $adminStmt->fetchAll() : [];

                    foreach ($admins as $adminRow) {
                        $adminEmail = trim((string)($adminRow['email'] ?? ''));
                        if ($adminEmail === '') {
                            continue;
                        }

                        $adminName = trim((string)($adminRow['full_name'] ?? '')) ?: 'Admin';
                        $subject = 'User Cancelled Booking - ' . (string)$bookingRow['booking_reference'];
                        $html = '
                            <div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.7;color:#1e293b;">
                                <p>Hello ' . htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') . ',</p>
                                <p>A user cancelled a booking.</p>
                                <p>
                                    <strong>Booking Ref:</strong> ' . htmlspecialchars((string)$bookingRow['booking_reference'], ENT_QUOTES, 'UTF-8') . '<br>
                                    <strong>Customer:</strong> ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . '<br>
                                    <strong>Email:</strong> ' . htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') . '<br>
                                    <strong>Package:</strong> ' . htmlspecialchars((string)$bookingRow['package_name'], ENT_QUOTES, 'UTF-8') . '<br>
                                    <strong>Travel Date:</strong> ' . htmlspecialchars((string)$bookingRow['travel_date'], ENT_QUOTES, 'UTF-8') . '<br>
                                    <strong>Amount:</strong> ' . htmlspecialchars(formatPrice($bookingRow['total_amount']), ENT_QUOTES, 'UTF-8') . '
                                </p>
                                <p>The booking status is now <strong>Cancelled</strong>.</p>
                            </div>
                        ';
                        $mailError = null;
                        sendBookingMail($adminEmail, $adminName, $subject, $html, $mailError);
                    }
                } catch (Throwable $e) {
                    // keep silent, booking already cancelled successfully
                }

                $success = 'Booking cancelled successfully.';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Unable to cancel booking right now.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_feedback'])) {
    $activeTab = 'bookings';

    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $packageId = (int)($_POST['package_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim((string)($_POST['review_text'] ?? ''));

    if ($bookingId <= 0 || $packageId <= 0 || $rating < 1 || $rating > 5) {
        $error = 'Please give a valid rating.';
    } else {
        try {
            $checkStmt = $pdo->prepare("
                SELECT id
                FROM bookings
                WHERE id = ? AND user_id = ?
                LIMIT 1
            ");
            $checkStmt->execute([$bookingId, $userId]);
            $bookingExists = $checkStmt->fetch();

            if (!$bookingExists) {
                $error = 'Invalid booking selected for feedback.';
            } else {
                $existingStmt = $pdo->prepare("
                    SELECT id
                    FROM reviews
                    WHERE user_id = ? AND booking_id = ?
                    LIMIT 1
                ");
                $existingStmt->execute([$userId, $bookingId]);
                $existingReview = $existingStmt->fetch();

                if ($existingReview) {
                    $updateStmt = $pdo->prepare("
                        UPDATE reviews
                        SET rating = ?, review_text = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([
                        $rating,
                        $reviewText !== '' ? $reviewText : null,
                        $existingReview['id']
                    ]);
                    $success = 'Feedback updated successfully.';
                } else {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO reviews (
                            user_id, package_id, booking_id, rating, review_text, is_approved, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                    ");
                    $insertStmt->execute([
                        $userId,
                        $packageId,
                        $bookingId,
                        $rating,
                        $reviewText !== '' ? $reviewText : null
                    ]);
                    $success = 'Feedback submitted successfully.';
                }
            }
        } catch (Throwable $e) {
            $error = 'Unable to save feedback right now.';
        }
    }
}

try {
    $bookingStmt = $pdo->prepare("
        SELECT
            b.id,
            b.booking_reference,
            b.travel_date,
            b.number_of_passengers,
            b.total_amount,
            b.booking_status,
            b.payment_status,
            b.created_at,
            p.id AS package_id,
            p.package_name,
            r.id AS review_id,
            r.rating AS existing_rating,
            r.review_text AS existing_review_text
        FROM bookings b
        INNER JOIN packages p ON p.id = b.package_id
        LEFT JOIN reviews r ON r.booking_id = b.id AND r.user_id = b.user_id
        WHERE b.user_id = ?
        ORDER BY b.id DESC
        LIMIT 10
    ");
    $bookingStmt->execute([$userId]);
    $bookings = $bookingStmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Unable to load booking history.';
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $activeTab === 'password' &&
    isset($_SESSION['profile_password_reset_email']) &&
    $_SESSION['profile_password_reset_email'] === ($user['email'] ?? '')
) {
    $passwordOtpStep = true;
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section profile-page-section">
    <div class="container">
        <div class="section-head profile-page-head">
            <span class="badge">My Profile</span>
            <h2>Hello, <?= e($user['full_name'] ?? currentUserName()) ?></h2>
            <p>Manage your account details, optional profile, password, bookings, receipts, and feedback in one place.</p>
        </div>

        <div class="profile-layout">
            <aside class="info-card profile-sidebar">
                <div class="profile-sidebar-head">
                    <div class="profile-sidebar-avatar">
                        <?php if (!empty($profile['profile_photo'])): ?>
                            <img src="<?= e($profile['profile_photo']) ?>" alt="<?= e($user['full_name'] ?? 'User') ?>">
                        <?php else: ?>
                            <span><?= e(strtoupper(substr((string)($user['full_name'] ?? 'U'), 0, 1))) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="profile-sidebar-user">
                        <h3><?= e($user['full_name'] ?? '') ?></h3>
                        <p><?= e($user['email'] ?? '') ?></p>
                    </div>
                </div>

                <div class="profile-sidebar-info profile-sidebar-info-classic">
                    <p><strong>Name:</strong> <?= e($user['full_name'] ?? '') ?></p>
                    <p><strong>Email:</strong> <?= e($user['email'] ?? '') ?></p>
                    <p><strong>Phone:</strong> <?= e($user['phone'] ?? '') ?></p>
                    <p><strong>Gender:</strong> <?= e(ucfirst($user['gender'] ?? '')) ?></p>
                    <p><strong>Role:</strong> <?= e(ucfirst($user['role'] ?? 'user')) ?></p>
                    <p><strong>Member Since:</strong> <?= e($user['created_at'] ?? '') ?></p>
                </div>

                <div class="profile-sidebar-nav">
                    <?php if (isAdmin()): ?>
                        <a class="profile-nav-btn profile-nav-btn-link" href="<?= BASE_URL ?>/admin/dashboard.php">Admin Panel</a>
                    <?php endif; ?>

                    <a class="profile-nav-btn <?= $activeTab === 'profile' ? 'is-active' : '' ?>" href="<?= e(profileTabUrl('profile')) ?>">Profile Edit</a>
                    <a class="profile-nav-btn <?= $activeTab === 'password' ? 'is-active' : '' ?>" href="<?= e(profileTabUrl('password')) ?>">Change Password</a>
                    <a class="profile-nav-btn <?= $activeTab === 'bookings' ? 'is-active' : '' ?>" href="<?= e(profileTabUrl('bookings')) ?>">Booking History</a>
                    <a class="profile-nav-btn profile-nav-btn-link" href="<?= BASE_URL ?>/logout.php">Logout</a>
                </div>
            </aside>

            <div class="profile-main">
                <?php if ($error !== ''): ?>
                    <div class="notice-error profile-notice"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="notice-success profile-notice"><?= e($success) ?></div>
                <?php endif; ?>

                <?php if ($activeTab === 'profile'): ?>
                    <div class="info-card profile-content-card">
                        <div class="profile-content-head">
                            <div>
                                <h3>Edit Optional Profile</h3>
                                <p>Update your personal details without leaving this page.</p>
                            </div>
                        </div>

                        <form method="post" action="">
                            <input type="hidden" name="save_profile" value="1">
                            <input type="hidden" name="active_tab" value="profile">

                            <div class="contact-form">
                                <div class="form-grid-2">
                                    <div class="field-wrap">
                                        <label class="field-label">Date of Birth</label>
                                        <input type="date" name="dob" value="<?= e($profile['dob'] ?? '') ?>">
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">Nationality</label>
                                        <input type="text" name="nationality" placeholder="Nationality" value="<?= e($profile['nationality'] ?? '') ?>">
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">Marital Status</label>
                                        <select name="marital_status">
                                            <option value="">Select marital status</option>
                                            <option value="single" <?= (($profile['marital_status'] ?? '') === 'single') ? 'selected' : '' ?>>Single</option>
                                            <option value="married" <?= (($profile['marital_status'] ?? '') === 'married') ? 'selected' : '' ?>>Married</option>
                                            <option value="other" <?= (($profile['marital_status'] ?? '') === 'other') ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">Address Line 1</label>
                                        <input type="text" name="address_line1" placeholder="Address line 1" value="<?= e($profile['address_line1'] ?? '') ?>">
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">Address Line 2</label>
                                        <input type="text" name="address_line2" placeholder="Address line 2" value="<?= e($profile['address_line2'] ?? '') ?>">
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">City</label>
                                        <input type="text" name="city" placeholder="City" value="<?= e($profile['city'] ?? '') ?>">
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">State</label>
                                        <input type="text" name="state" placeholder="State" value="<?= e($profile['state'] ?? '') ?>">
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">Country</label>
                                        <input type="text" name="country" placeholder="Country" value="<?= e($profile['country'] ?? '') ?>">
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">Postal Code</label>
                                        <input type="text" name="postal_code" placeholder="Postal code" value="<?= e($profile['postal_code'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-actions profile-form-actions">
                                    <button type="submit" class="btn btn-primary">Save Profile</button>
                                </div>
                            </div>
                        </form>
                    </div>

                <?php elseif ($activeTab === 'password'): ?>
                    <div class="info-card profile-content-card">
                        <div class="profile-content-head">
                            <div>
                                <h3>Change Password</h3>
                                <p>Keep your account secure by updating your password.</p>
                            </div>
                        </div>

                        <?php if (!$passwordOtpStep): ?>
                            <form method="post" action="">
                                <input type="hidden" name="change_password" value="1">
                                <input type="hidden" name="active_tab" value="password">

                                <div class="contact-form">
                                    <div class="field-wrap">
                                        <label class="field-label">Current Password</label>
                                        <input type="password" name="current_password" placeholder="Current password" required>
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">New Password</label>
                                        <input type="password" name="new_password" placeholder="New password (min 8 characters)" required>
                                    </div>

                                    <div class="field-wrap">
                                        <label class="field-label">Confirm New Password</label>
                                        <input type="password" name="confirm_new_password" placeholder="Confirm new password" required>
                                    </div>

                                    <div class="form-actions profile-form-actions">
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </div>
                                </div>
                            </form>

                            <div style="margin-top:18px;padding-top:18px;border-top:1px solid #dbe4f0;">
                                <h4 style="margin:0 0 8px;">Forgot current password?</h4>
                                <p style="margin:0 0 14px;color:#64748b;">
                                    Send an OTP to <strong><?= e($user['email'] ?? '') ?></strong> and reset your password.
                                </p>

                                <form method="post" action="">
                                    <input type="hidden" name="send_password_reset_otp" value="1">
                                    <input type="hidden" name="active_tab" value="password">
                                    <button type="submit" class="btn btn-soft">Send Reset OTP</button>
                                </form>
                            </div>

                        <?php else: ?>
                            <div style="margin-top:4px;">
                                <h4 style="margin:0 0 8px;">Forgot current password?</h4>
                                <p style="margin:0 0 14px;color:#64748b;">
                                    OTP has been sent to <strong><?= e($user['email'] ?? '') ?></strong>. Enter it below to reset your password.
                                </p>

                                <form method="post" action="">
                                    <input type="hidden" name="reset_password_with_otp" value="1">
                                    <input type="hidden" name="active_tab" value="password">

                                    <div class="contact-form">
                                        <div class="field-wrap">
                                            <label class="field-label">OTP Code</label>
                                            <input type="text" name="otp_code" placeholder="Enter 6-digit OTP" maxlength="6" required>
                                        </div>

                                        <div class="field-wrap">
                                            <label class="field-label">New Password</label>
                                            <input type="password" name="otp_new_password" placeholder="New password (min 8 characters)" required>
                                        </div>

                                        <div class="field-wrap">
                                            <label class="field-label">Confirm New Password</label>
                                            <input type="password" name="otp_confirm_new_password" placeholder="Confirm new password" required>
                                        </div>

                                        <div class="form-actions profile-form-actions">
                                            <button type="submit" class="btn btn-primary">Verify OTP & Reset Password</button>
                                        </div>
                                    </div>
                                </form>

                                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
                                    <form method="post" action="" style="margin:0;">
                                        <input type="hidden" name="send_password_reset_otp" value="1">
                                        <input type="hidden" name="active_tab" value="password">
                                        <button type="submit" class="btn btn-soft">Resend Reset OTP</button>
                                    </form>

                                    <form method="post" action="" style="margin:0;">
                                        <input type="hidden" name="cancel_password_otp" value="1">
                                        <input type="hidden" name="active_tab" value="password">
                                        <button type="submit" class="btn btn-soft">Back to Change Password</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="info-card profile-content-card">
                        <div class="profile-content-head">
                            <div>
                                <h3>Recent Booking History</h3>
                                <p>View your bookings, receipts, and manage feedback only when needed.</p>
                            </div>
                        </div>

                        <?php if (!empty($bookings)): ?>
                            <div class="profile-booking-list">
                                <?php foreach ($bookings as $booking): ?>
                                    <?php
                                    $hasReview = !empty($booking['review_id']);
                                    $feedbackBoxId = 'feedback-box-' . (int)$booking['id'];
                                    $bookingStatusLower = strtolower((string)($booking['booking_status'] ?? ''));
                                    ?>
                                    <div class="profile-booking-card">
                                        <div class="profile-booking-top">
                                            <div class="profile-booking-title">
                                                <h4><?= e($booking['package_name']) ?></h4>
                                                <p>Ref: <?= e($booking['booking_reference']) ?></p>
                                            </div>

                                            <div class="profile-booking-price">
                                                <strong><?= e(formatPrice($booking['total_amount'])) ?></strong>
                                                <span><?= e($booking['travel_date']) ?></span>
                                            </div>
                                        </div>

                                        <div class="profile-booking-meta">
                                            <span>Passengers: <?= (int)$booking['number_of_passengers'] ?></span>
                                            <span>Booking: <?= e(ucfirst((string)$booking['booking_status'])) ?></span>
                                            <span>Payment: <?= e(ucfirst((string)$booking['payment_status'])) ?></span>
                                        </div>

                                        <div class="card-actions profile-booking-actions">
                                            <a class="btn btn-small btn-soft" href="<?= BASE_URL ?>/package-details.php?id=<?= (int)$booking['package_id'] ?>">View Package</a>
                                            <a class="btn btn-small btn-soft" href="<?= BASE_URL ?>/receipt.php?booking_id=<?= (int)$booking['id'] ?>">View Receipt</a>

                                            <?php if (canCancelBooking($booking)): ?>
                                                <form method="post" action="" style="margin:0;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                    <input type="hidden" name="cancel_booking_user" value="1">
                                                    <input type="hidden" name="active_tab" value="bookings">
                                                    <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                                                    <button type="submit" class="btn btn-small btn-soft" style="border-color:#fecaca;color:#b91c1c;background:#fff5f5;">
                                                        Cancel Booking
                                                    </button>
                                                </form>
                                            <?php elseif ($bookingStatusLower === 'cancelled'): ?>
                                                <span class="btn btn-small btn-soft" style="pointer-events:none;background:#fee2e2;color:#991b1b;border-color:#fecaca;">
                                                    Cancelled
                                                </span>
                                            <?php endif; ?>

                                            <button
                                                type="button"
                                                class="btn btn-small <?= $hasReview ? 'btn-primary' : 'btn-soft' ?> profile-feedback-toggle"
                                                data-target="<?= e($feedbackBoxId) ?>"
                                            >
                                                <?= $hasReview ? 'Edit Feedback' : 'Rate Us' ?>
                                            </button>
                                        </div>

                                        <?php if ($hasReview): ?>
                                            <div class="profile-feedback-preview">
                                                <div class="profile-feedback-preview-top">
                                                    <span class="profile-feedback-badge">Your Feedback</span>
                                                    <strong><?= (int)$booking['existing_rating'] ?>/5</strong>
                                                </div>
                                                <p><?= nl2br(e($booking['existing_review_text'] ?: 'No written feedback provided.')) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <div id="<?= e($feedbackBoxId) ?>" class="profile-feedback-form-wrap" style="display:none;">
                                            <form method="post" action="">
                                                <input type="hidden" name="save_feedback" value="1">
                                                <input type="hidden" name="active_tab" value="bookings">
                                                <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                                                <input type="hidden" name="package_id" value="<?= (int)$booking['package_id'] ?>">

                                                <div class="contact-form">
                                                    <div class="field-wrap">
                                                        <label class="field-label">Rating</label>
                                                        <select name="rating" required>
                                                            <option value="">Select rating</option>
                                                            <option value="1" <?= ((int)($booking['existing_rating'] ?? 0) === 1) ? 'selected' : '' ?>>1 Star</option>
                                                            <option value="2" <?= ((int)($booking['existing_rating'] ?? 0) === 2) ? 'selected' : '' ?>>2 Stars</option>
                                                            <option value="3" <?= ((int)($booking['existing_rating'] ?? 0) === 3) ? 'selected' : '' ?>>3 Stars</option>
                                                            <option value="4" <?= ((int)($booking['existing_rating'] ?? 0) === 4) ? 'selected' : '' ?>>4 Stars</option>
                                                            <option value="5" <?= ((int)($booking['existing_rating'] ?? 0) === 5) ? 'selected' : '' ?>>5 Stars</option>
                                                        </select>
                                                    </div>

                                                    <div class="field-wrap">
                                                        <label class="field-label">Feedback</label>
                                                        <textarea name="review_text" placeholder="Write your feedback"><?= e($booking['existing_review_text'] ?? '') ?></textarea>
                                                    </div>

                                                    <div class="form-actions profile-form-actions">
                                                        <button type="submit" class="btn btn-primary">
                                                            <?= $hasReview ? 'Update Feedback' : 'Submit Feedback' ?>
                                                        </button>
                                                        <button type="button" class="btn btn-soft profile-feedback-cancel" data-target="<?= e($feedbackBoxId) ?>">Cancel</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="profile-empty-state">
                                <h4>No bookings found yet</h4>
                                <p>When you book a package, your trip details, receipt, and feedback options will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var toggleButtons = document.querySelectorAll('.profile-feedback-toggle');
    var cancelButtons = document.querySelectorAll('.profile-feedback-cancel');

    function closeAllFeedbackForms() {
        document.querySelectorAll('.profile-feedback-form-wrap').forEach(function (el) {
            el.style.display = 'none';
        });
    }

    toggleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            var target = targetId ? document.getElementById(targetId) : null;
            if (!target) return;

            var isOpen = target.style.display === 'block';
            closeAllFeedbackForms();
            target.style.display = isOpen ? 'none' : 'block';
        });
    });

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            var target = targetId ? document.getElementById(targetId) : null;
            if (!target) return;
            target.style.display = 'none';
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>