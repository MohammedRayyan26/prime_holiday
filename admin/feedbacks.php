<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$error = '';
$success = '';
$feedbacks = [];

$search = trim($_GET['search'] ?? '');
$ratingFilter = trim($_GET['rating'] ?? '');
$approvalFilter = trim($_GET['approval'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $reviewId = (int)($_POST['review_id'] ?? 0);

    if ($reviewId <= 0) {
        $error = 'Invalid feedback selected.';
    } else {
        try {
            if ($action === 'approve_feedback') {
                $stmt = $pdo->prepare("
                    UPDATE reviews
                    SET is_approved = 1, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$reviewId]);
                $success = 'Feedback approved successfully.';
            } elseif ($action === 'unapprove_feedback') {
                $stmt = $pdo->prepare("
                    UPDATE reviews
                    SET is_approved = 0, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$reviewId]);
                $success = 'Feedback moved to unapproved.';
            } elseif ($action === 'delete_feedback') {
                $stmt = $pdo->prepare("
                    DELETE FROM reviews
                    WHERE id = ?
                ");
                $stmt->execute([$reviewId]);
                $success = 'Feedback deleted successfully.';
            } else {
                $error = 'Invalid action.';
            }
        } catch (Throwable $e) {
            $error = 'Unable to update feedback.';
        }
    }
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        u.full_name LIKE ?
        OR u.email LIKE ?
        OR p.package_name LIKE ?
        OR r.review_text LIKE ?
    )";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($ratingFilter !== '' && in_array($ratingFilter, ['1', '2', '3', '4', '5'], true)) {
    $where[] = "r.rating = ?";
    $params[] = (int)$ratingFilter;
}

if ($approvalFilter === 'approved') {
    $where[] = "r.is_approved = 1";
} elseif ($approvalFilter === 'unapproved') {
    $where[] = "r.is_approved = 0";
}

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

$totalFeedbacks = 0;
$totalApproved = 0;
$totalUnapproved = 0;

try {
    $totalFeedbacks = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    $totalApproved = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 1")->fetchColumn();
    $totalUnapproved = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            r.id,
            r.rating,
            r.review_text,
            r.is_approved,
            r.created_at,
            r.updated_at,
            r.booking_id,
            u.full_name,
            u.email,
            p.package_name,
            b.booking_reference
        FROM reviews r
        INNER JOIN users u ON u.id = r.user_id
        INNER JOIN packages p ON p.id = r.package_id
        LEFT JOIN bookings b ON b.id = r.booking_id
        $sqlWhere
        ORDER BY r.id DESC
    ");
    $stmt->execute($params);
    $feedbacks = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Unable to load feedback records.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">

        <div class="section-head admin-packages-head" style="margin-bottom:16px;">
            <span class="badge">Admin</span>
            <h2 style="margin:10px 0 8px;">Manage Feedbacks</h2>
            <p style="margin:0;">Review customer feedback, moderate visibility, and clean up low-quality entries.</p>
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

        <div class="info-grid admin-packages-grid-gap" style="margin-bottom:14px;">
            <div class="info-card" style="padding:20px 22px;">
                <h3 style="margin-bottom:8px;">Total Feedbacks</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;"><?= $totalFeedbacks ?></p>
            </div>
            <div class="info-card" style="padding:20px 22px;">
                <h3 style="margin-bottom:8px;">Approved</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;color:#166534;"><?= $totalApproved ?></p>
            </div>
            <div class="info-card" style="padding:20px 22px;">
                <h3 style="margin-bottom:8px;">Rejected</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;color:#991b1b;"><?= $totalUnapproved ?></p>
            </div>
        </div>

        <div class="info-card admin-packages-card" style="margin-bottom:14px;">
            <form method="get" action="">
                <div class="form-grid-2" style="gap:14px;">
                    <div class="field-wrap">
                        <label class="field-label">Search</label>
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="User, email, package, review text">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Rating</label>
                        <select name="rating">
                            <option value="">All Ratings</option>
                            <option value="5" <?= $ratingFilter === '5' ? 'selected' : '' ?>>5 Stars</option>
                            <option value="4" <?= $ratingFilter === '4' ? 'selected' : '' ?>>4 Stars</option>
                            <option value="3" <?= $ratingFilter === '3' ? 'selected' : '' ?>>3 Stars</option>
                            <option value="2" <?= $ratingFilter === '2' ? 'selected' : '' ?>>2 Stars</option>
                            <option value="1" <?= $ratingFilter === '1' ? 'selected' : '' ?>>1 Star</option>
                        </select>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Approval</label>
                        <select name="approval">
                            <option value="">All</option>
                            <option value="approved" <?= $approvalFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="unapproved" <?= $approvalFilter === 'unapproved' ? 'selected' : '' ?>>Reject</option>
                        </select>
                    </div>

                    <div class="field-wrap" style="align-self:end;">
                        <div class="card-actions" style="gap:10px;">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="<?= BASE_URL ?>/admin/feedbacks.php" class="btn btn-soft">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-card" style="padding:20px 22px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                <h3 style="margin:0;">Feedback List</h3>
                <span class="muted" style="font-size:13px;font-weight:700;">Total: <?= count($feedbacks) ?></span>
            </div>

            <?php if (!empty($feedbacks)): ?>
                <div style="display:grid;gap:12px;">
                    <?php foreach ($feedbacks as $row): ?>
                        <div style="border:1px solid #e5eaf2;border-radius:16px;padding:14px 16px;background:#fff;">
                            <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start;">
                                <div style="flex:1;min-width:260px;">
                                    <h3 style="margin:0 0 8px;font-size:20px;"><?= e($row['full_name']) ?></h3>

                                    <div style="display:grid;gap:4px;color:#5f6d83;font-size:14px;line-height:1.5;">
                                        <div><strong style="color:#162033;">Email:</strong> <?= e($row['email']) ?></div>
                                        <div><strong style="color:#162033;">Package:</strong> <?= e($row['package_name']) ?></div>
                                        <div><strong style="color:#162033;">Booking Ref:</strong> <?= e($row['booking_reference'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">Submitted:</strong> <?= e($row['created_at']) ?></div>
                                    </div>
                                </div>

                                <div style="min-width:220px;display:flex;flex-direction:column;align-items:flex-start;gap:8px;">
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <span class="stars"><?= e(renderStars((float)$row['rating'])) ?></span>
                                        <strong style="font-size:15px;"><?= (int)$row['rating'] ?>/5</strong>
                                    </div>

                                    <?php if ((int)$row['is_approved'] === 1): ?>
                                        <span class="btn btn-soft btn-small" style="pointer-events:none;background:#dcfce7;color:#166534;border-color:#bbf7d0;min-height:34px;">Approved</span>
                                    <?php else: ?>
                                        <span class="btn btn-soft btn-small" style="pointer-events:none;background:#fee2e2;color:#991b1b;border-color:#fecaca;min-height:34px;">Unapproved</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="margin-top:12px;padding:12px 14px;border:1px solid #dbe4f0;border-radius:14px;background:#f8fbff;color:#4b5b73;line-height:1.6;">
                                <?= nl2br(e($row['review_text'] ?: 'No written feedback provided.')) ?>
                            </div>

                            <div class="card-actions" style="margin-top:12px;gap:8px;">
                                <?php if ((int)$row['is_approved'] === 1): ?>
                                    <form method="post" action="" style="margin:0;">
                                        <input type="hidden" name="action" value="unapprove_feedback">
                                        <input type="hidden" name="review_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-soft btn-small">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="" style="margin:0;">
                                        <input type="hidden" name="action" value="approve_feedback">
                                        <input type="hidden" name="review_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-small">Approve</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!empty($row['booking_id'])): ?>
                                    <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/receipt.php?booking_id=<?= (int)$row['booking_id'] ?>">View Receipt</a>
                                <?php endif; ?>

                                <form method="post" action="" onsubmit="return confirm('Delete this feedback?');" style="margin:0;">
                                    <input type="hidden" name="action" value="delete_feedback">
                                    <input type="hidden" name="review_id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted" style="margin:0;">No feedback records found for the selected filters.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>