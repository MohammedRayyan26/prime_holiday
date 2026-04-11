<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$totalUsers = 0;
$totalPackages = 0;
$totalBookings = 0;
$totalFeedbacks = 0;
$totalContactMessages = 0;
$totalDestinations = 0;
$recentBookings = [];

try {
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $totalPackages = (int)$pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    $totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $totalFeedbacks = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    $totalContactMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
    $totalDestinations = (int)$pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn();

    $recentStmt = $pdo->query("
        SELECT
            b.booking_reference,
            b.customer_name,
            b.travel_date,
            b.total_amount,
            b.booking_status,
            b.payment_status,
            p.package_name
        FROM bookings b
        INNER JOIN packages p ON p.id = b.package_id
        ORDER BY b.id DESC
        LIMIT 8
    ");
    $recentBookings = $recentStmt->fetchAll();
} catch (Throwable $e) {
    $recentBookings = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-dashboard-section">
    <div class="container">
        <div class="section-head admin-dashboard-head">
            <span class="badge">Admin Dashboard</span>
            <h2>Welcome, <?= e(currentUserName()) ?></h2>
            <p>Manage packages, bookings, users, feedback, and contact enquiries from one place.</p>
        </div>

        <div class="info-card admin-summary-card">
            <div class="admin-summary-row">
                <div class="admin-summary-item">
                    <span class="admin-summary-label">Total Users</span>
                    <strong class="admin-summary-value"><?= $totalUsers ?></strong>
                </div>

                <div class="admin-summary-item">
                    <span class="admin-summary-label">Total Packages</span>
                    <strong class="admin-summary-value"><?= $totalPackages ?></strong>
                </div>

                <div class="admin-summary-item">
                    <span class="admin-summary-label">Total Bookings</span>
                    <strong class="admin-summary-value"><?= $totalBookings ?></strong>
                </div>

                <div class="admin-summary-item">
                    <span class="admin-summary-label">Total Feedbacks</span>
                    <strong class="admin-summary-value"><?= $totalFeedbacks ?></strong>
                </div>

                <div class="admin-summary-item">
                    <span class="admin-summary-label">Contact Messages</span>
                    <strong class="admin-summary-value"><?= $totalContactMessages ?></strong>
                </div>

                <div class="admin-summary-item">
                    <span class="admin-summary-label">Destinations</span>
                    <strong class="admin-summary-value"><?= $totalDestinations ?></strong>
                </div>
            </div>
        </div>

        <div class="info-card admin-actions-card">
            <div class="admin-card-head">
                <h3>Quick Actions</h3>
            </div>

            <div class="admin-actions-row">
                <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/admin/packages.php">Manage Packages</a>
                <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/admin/bookings.php">Manage Bookings</a>
                <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/admin/users.php">Manage Users</a>
                <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/admin/feedbacks.php">View Feedbacks</a>
                <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/admin/contact_messages.php">Contact Messages</a>
                <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/admin/destinations.php">Manage Destinations</a>
            </div>
        </div>

        <div class="info-card admin-bookings-card">
            <div class="admin-card-head">
                <h3>Recent Bookings</h3>
            </div>

            <?php if (!empty($recentBookings)): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Travel Date</th>
                                <th>Amount</th>
                                <th>Booking</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $row): ?>
                                <tr>
                                    <td><?= e($row['booking_reference']) ?></td>
                                    <td><?= e($row['customer_name']) ?></td>
                                    <td><?= e($row['package_name']) ?></td>
                                    <td><?= e($row['travel_date']) ?></td>
                                    <td><?= e(formatPrice($row['total_amount'])) ?></td>
                                    <td><?= e(ucfirst($row['booking_status'])) ?></td>
                                    <td><?= e(ucfirst($row['payment_status'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="muted" style="margin:0;">No bookings found yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>