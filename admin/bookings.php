<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$error = '';
$bookings = [];

$search = trim($_GET['search'] ?? '');
$bookingStatus = trim($_GET['booking_status'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');

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

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">

        <div class="section-head admin-packages-head" style="margin-bottom:16px;">
            <span class="badge">Admin</span>
            <h2 style="margin:10px 0 8px;">Manage Bookings</h2>
            <p style="margin:0;">Search, filter, and review customer bookings in one place.</p>
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
                                <th>Receipt</th>
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
                                        <a
                                            class="btn btn-soft btn-small"
                                            href="<?= BASE_URL ?>/receipt.php?booking_id=<?= (int)$row['id'] ?>"
                                        >
                                            View Receipt
                                        </a>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>