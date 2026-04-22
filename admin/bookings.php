<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

requireAdmin();

$error = '';
$success = '';
$bookings = [];

$search = trim($_GET['search'] ?? '');
$bookingStatus = trim($_GET['booking_status'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');

function adminCanCancelBooking(array $booking): bool
{
    $status = strtolower(trim((string)($booking['booking_status'] ?? '')));
    return in_array($status, ['pending', 'confirmed'], true);
}

function bookingsPageUrl(array $extra = []): string
{
    $params = [];

    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $params['search'] = $_GET['search'];
    }
    if (isset($_GET['booking_status']) && $_GET['booking_status'] !== '') {
        $params['booking_status'] = $_GET['booking_status'];
    }
    if (isset($_GET['payment_status']) && $_GET['payment_status'] !== '') {
        $params['payment_status'] = $_GET['payment_status'];
    }

    foreach ($extra as $key => $value) {
        $params[$key] = $value;
    }

    $query = http_build_query($params);
    return BASE_URL . '/admin/bookings.php' . ($query !== '' ? '?' . $query : '');
}

function adminSendBookingMail(string $toEmail, string $toName, string $subject, string $html, ?string &$error = null): bool
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($action === 'cancel_booking') {
        if ($bookingId <= 0) {
            header('Location: ' . bookingsPageUrl([
                'error' => 'Invalid booking selected.'
            ]));
            exit;
        }

        try {
            $bookingStmt = $pdo->prepare("
                SELECT
                    b.id,
                    b.booking_reference,
                    b.customer_name,
                    b.customer_email,
                    b.customer_phone,
                    b.travel_date,
                    b.number_of_passengers,
                    b.total_amount,
                    b.booking_status,
                    b.payment_status,
                    b.booked_at,
                    b.user_id,
                    p.package_name,
                    d.name AS destination_name,
                    pay.payment_gateway,
                    pay.razorpay_payment_id,
                    pay.razorpay_order_id,
                    pay.paid_at
                FROM bookings b
                INNER JOIN packages p ON p.id = b.package_id
                INNER JOIN destinations d ON d.id = p.destination_id
                LEFT JOIN payments pay ON pay.booking_id = b.id
                WHERE b.id = ?
                LIMIT 1
            ");
            $bookingStmt->execute([$bookingId]);
            $bookingRow = $bookingStmt->fetch();

            if (!$bookingRow) {
                header('Location: ' . bookingsPageUrl([
                    'error' => 'Booking not found.'
                ]));
                exit;
            }

            if (!adminCanCancelBooking($bookingRow)) {
                header('Location: ' . bookingsPageUrl([
                    'error' => 'This booking cannot be cancelled now.'
                ]));
                exit;
            }

            $pdo->beginTransaction();

            $currentPaymentStatus = strtolower(trim((string)($bookingRow['payment_status'] ?? '')));
            $newPaymentStatus = $currentPaymentStatus === 'paid'
                ? 'refunded'
                : ($bookingRow['payment_status'] ?: 'pending');

            $updateBookingStmt = $pdo->prepare("
                UPDATE bookings
                SET booking_status = 'cancelled',
                    payment_status = ?
                WHERE id = ?
                LIMIT 1
            ");
            $updateBookingStmt->execute([
                $newPaymentStatus,
                $bookingId
            ]);

            if ($currentPaymentStatus === 'paid') {
                $updatePaymentStmt = $pdo->prepare("
                    UPDATE payments
                    SET payment_status = 'refunded'
                    WHERE booking_id = ?
                ");
                $updatePaymentStmt->execute([$bookingId]);
            }

            $pdo->commit();

            $customerName = trim((string)($bookingRow['customer_name'] ?? '')) ?: 'Customer';
            $customerEmail = trim((string)($bookingRow['customer_email'] ?? ''));

            if ($customerEmail === '' && !empty($bookingRow['user_id'])) {
                $userEmailStmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
                $userEmailStmt->execute([(int)$bookingRow['user_id']]);
                $userRow = $userEmailStmt->fetch();
                if ($userRow && !empty($userRow['email'])) {
                    $customerEmail = trim((string)$userRow['email']);
                }
            }

            if ($customerEmail !== '') {
                $subject = 'Booking Cancelled - ' . (string)$bookingRow['booking_reference'];
                $html = '
                    <div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.7;color:#1e293b;">
                        <p>Dear ' . htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') . ',</p>
                        <p>Your booking <strong>' . htmlspecialchars((string)$bookingRow['booking_reference'], ENT_QUOTES, 'UTF-8') . '</strong> for <strong>' . htmlspecialchars((string)$bookingRow['package_name'], ENT_QUOTES, 'UTF-8') . '</strong> has been cancelled by our team.</p>
                        <p>Amountwill be refunded within <strong>5 to 6 working days</strong>.</p>
                        <p>
                            <strong>Destination:</strong> ' . htmlspecialchars((string)$bookingRow['destination_name'], ENT_QUOTES, 'UTF-8') . '<br>
                            <strong>Travel Date:</strong> ' . htmlspecialchars((string)$bookingRow['travel_date'], ENT_QUOTES, 'UTF-8') . '<br>
                            <strong>Passengers:</strong> ' . (int)$bookingRow['number_of_passengers'] . '<br>
                            <strong>Amount:</strong> ' . htmlspecialchars(formatPrice($bookingRow['total_amount']), ENT_QUOTES, 'UTF-8') . '
                        </p>
                        <p>Regards,<br>Prime Holiday Team</p>
                    </div>
                ';
                $mailError = null;
                adminSendBookingMail($customerEmail, $customerName, $subject, $html, $mailError);
            }

            header('Location: ' . bookingsPageUrl([
                'success' => 'Booking cancelled successfully.'
            ]));
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            header('Location: ' . bookingsPageUrl([
                'error' => 'Unable to cancel booking right now.'
            ]));
            exit;
        }
    }
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        b.booking_reference LIKE ?
        OR b.customer_name LIKE ?
        OR b.customer_email LIKE ?
        OR p.package_name LIKE ?
    )";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($bookingStatus !== '' && in_array($bookingStatus, ['pending', 'confirmed', 'cancelled', 'completed'], true)) {
    $where[] = "b.booking_status = ?";
    $params[] = $bookingStatus;
}

if ($paymentStatus !== '' && in_array($paymentStatus, ['pending', 'paid', 'failed', 'refunded'], true)) {
    $where[] = "b.payment_status = ?";
    $params[] = $paymentStatus;
}

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

try {
    $stmt = $pdo->prepare("
        SELECT
            b.id,
            b.booking_reference,
            b.customer_name,
            b.customer_email,
            b.customer_phone,
            b.travel_date,
            b.number_of_passengers,
            b.total_amount,
            b.booking_status,
            b.payment_status,
            b.booked_at,
            p.package_name,
            d.name AS destination_name,
            pay.payment_gateway,
            pay.razorpay_payment_id,
            pay.razorpay_order_id,
            pay.paid_at
        FROM bookings b
        INNER JOIN packages p ON p.id = b.package_id
        INNER JOIN destinations d ON d.id = p.destination_id
        LEFT JOIN payments pay ON pay.booking_id = b.id
        $sqlWhere
        ORDER BY b.id DESC
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Unable to load bookings.';
}

if (isset($_GET['success']) && $_GET['success'] !== '') {
    $success = trim((string)$_GET['success']);
}

if (isset($_GET['error']) && $_GET['error'] !== '') {
    $error = trim((string)$_GET['error']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">

        <div class="section-head admin-packages-head" style="margin-bottom:16px;">
            <span class="badge">Admin</span>
            <h2 style="margin:10px 0 8px;">Manage Bookings</h2>
            <p style="margin:0;">Search, filter, review, and manage customer bookings in one place.</p>
        </div>

        <div class="admin-topbar admin-packages-topbar" style="margin-bottom:14px;">
            <div class="admin-back-links admin-packages-back-links">
                <a class="btn-back" href="<?= BASE_URL ?>/admin/dashboard.php">← Dashboard</a>
                <a class="btn-back" href="<?= BASE_URL ?>/index.php">← Website</a>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="notice-error" style="margin-bottom:14px;"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="notice-success" style="margin-bottom:14px;"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="info-card admin-packages-card" style="margin-bottom:14px;">
            <form method="get" action="">
                <div class="form-grid-2" style="gap:14px;">
                    <div class="field-wrap">
                        <label class="field-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Reference, customer, email, package"
                        >
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Booking Status</label>
                        <select name="booking_status">
                            <option value="">All Booking Status</option>
                            <option value="pending" <?= $bookingStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $bookingStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="cancelled" <?= $bookingStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="completed" <?= $bookingStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Payment Status</label>
                        <select name="payment_status">
                            <option value="">All Payment Status</option>
                            <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="failed" <?= $paymentStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="refunded" <?= $paymentStatus === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>

                    <div class="field-wrap" style="align-self:end;">
                        <div class="card-actions" style="gap:10px;">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-soft">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-card" style="padding:20px 22px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
                <h3 style="margin:0;">Booking List</h3>
                <span class="muted" style="font-size:13px;font-weight:700;">
                    Total: <?= count($bookings) ?>
                </span>
            </div>

            <?php if (!empty($bookings)): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Travel</th>
                                <th>Amount</th>
                                <th>Booking</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($row['booking_reference']) ?></strong><br>
                                        <span class="muted" style="font-size:12px;"><?= e($row['booked_at']) ?></span>
                                    </td>

                                    <td>
                                        <strong><?= e($row['customer_name']) ?></strong><br>
                                        <span class="muted" style="font-size:12px;"><?= e($row['customer_email']) ?></span><br>
                                        <span class="muted" style="font-size:12px;"><?= e($row['customer_phone']) ?></span>
                                    </td>

                                    <td>
                                        <strong><?= e($row['package_name']) ?></strong><br>
                                        <span class="muted" style="font-size:12px;"><?= e($row['destination_name']) ?></span>
                                    </td>

                                    <td>
                                        <div><strong><?= e($row['travel_date']) ?></strong></div>
                                        <div class="muted" style="font-size:12px;">
                                            Passengers: <?= (int)$row['number_of_passengers'] ?>
                                        </div>
                                    </td>

                                    <td>
                                        <strong><?= e(formatPrice($row['total_amount'])) ?></strong>
                                    </td>

                                    <td>
                                        <span><?= e(ucfirst($row['booking_status'])) ?></span>
                                    </td>

                                    <td>
                                        <div><strong><?= e(ucfirst($row['payment_status'])) ?></strong></div>
                                        <div class="muted" style="font-size:12px;"><?= e($row['payment_gateway'] ?: '-') ?></div>
                                        <div class="muted" style="font-size:12px;"><?= e($row['razorpay_payment_id'] ?: '-') ?></div>
                                    </td>

                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:8px;min-width:120px;">
                                            <a
                                                class="btn btn-soft btn-small"
                                                href="<?= BASE_URL ?>/receipt.php?booking_id=<?= (int)$row['id'] ?>"
                                            >
                                                View Receipt
                                            </a>

                                            <?php if (adminCanCancelBooking($row)): ?>
                                                <form method="post" action="" style="margin:0;" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                                    <input type="hidden" name="action" value="cancel_booking">
                                                    <input type="hidden" name="booking_id" value="<?= (int)$row['id'] ?>">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-soft btn-small"
                                                        style="border-color:#fecaca;color:#b91c1c;background:#fff5f5;"
                                                    >
                                                        Cancel Booking
                                                    </button>
                                                </form>
                                            <?php elseif (strtolower((string)$row['booking_status']) === 'cancelled'): ?>
                                                <span
                                                    class="btn btn-soft btn-small"
                                                    style="pointer-events:none;background:#fee2e2;color:#991b1b;border-color:#fecaca;"
                                                >
                                                    Cancelled
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="muted" style="margin:0;">No bookings found for the selected filters.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
(function () {
    var hasNotice = document.querySelector('.notice-error, .notice-success');

    if (hasNotice && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.delete('error');
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ''));
    }

    setTimeout(function () {
        document.querySelectorAll('.notice-error, .notice-success').forEach(function (el) {
            el.style.display = 'none';
        });
    }, 4000);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>