<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$error = '';
$success = '';
$users = [];

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        $error = 'Invalid user selected.';
    } else {
        try {
            if ($action === 'activate_user') {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET is_active = 1, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                $success = 'User activated successfully.';
            } elseif ($action === 'deactivate_user') {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET is_active = 0, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                $success = 'User deactivated successfully.';
            } else {
                $error = 'Invalid action.';
            }
        } catch (Throwable $e) {
            $error = 'Unable to update user status.';
        }
    }
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        u.full_name LIKE ?
        OR u.email LIKE ?
        OR u.phone LIKE ?
    )";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($roleFilter !== '' && in_array($roleFilter, ['user', 'admin'], true)) {
    $where[] = "u.role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter === 'active') {
    $where[] = "u.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $where[] = "u.is_active = 0";
}

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

$totalUsers = 0;
$totalActive = 0;
$totalInactive = 0;

try {
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalActive = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    $totalInactive = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.gender,
            u.role,
            u.is_active,
            u.email_verified_at,
            u.last_login_at,
            u.created_at,
            up.dob,
            up.nationality,
            up.marital_status,
            up.city,
            up.state,
            up.country,
            up.postal_code
        FROM users u
        LEFT JOIN user_profiles up ON up.user_id = u.id
        $sqlWhere
        ORDER BY u.id DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Unable to load users.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">

        <div class="section-head admin-packages-head" style="margin-bottom:16px;">
            <span class="badge">Admin</span>
            <h2 style="margin:10px 0 8px;">Manage Users</h2>
            <p style="margin:0;">Search users, review profiles, and control account status.</p>
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
                <h3 style="margin-bottom:8px;">Total Users</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;"><?= $totalUsers ?></p>
            </div>
            <div class="info-card" style="padding:20px 22px;">
                <h3 style="margin-bottom:8px;">Active Users</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;color:#166534;"><?= $totalActive ?></p>
            </div>
            <div class="info-card" style="padding:20px 22px;">
                <h3 style="margin-bottom:8px;">Inactive Users</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;color:#991b1b;"><?= $totalInactive ?></p>
            </div>
        </div>

        <div class="info-card admin-packages-card" style="margin-bottom:14px;">
            <form method="get" action="">
                <div class="form-grid-2" style="gap:14px;">
                    <div class="field-wrap">
                        <label class="field-label">Search</label>
                        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Name, email, or phone">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Role</label>
                        <select name="role">
                            <option value="">All Roles</option>
                            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="field-wrap" style="align-self:end;">
                        <div class="card-actions" style="gap:10px;">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-soft">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-card" style="padding:20px 22px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                <h3 style="margin:0;">User List</h3>
                <span class="muted" style="font-size:13px;font-weight:700;">Total: <?= count($users) ?></span>
            </div>

            <?php if (!empty($users)): ?>
                <div style="display:grid;gap:12px;">
                    <?php foreach ($users as $row): ?>
                        <div style="border:1px solid #e5eaf2;border-radius:16px;padding:14px 16px;background:#fff;">
                            <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start;">
                                <div style="flex:1;min-width:260px;">
                                    <h3 style="margin:0 0 8px;font-size:20px;"><?= e($row['full_name']) ?></h3>

                                    <div style="display:grid;gap:4px;color:#5f6d83;font-size:14px;line-height:1.5;">
                                        <div><strong style="color:#162033;">Email:</strong> <?= e($row['email']) ?></div>
                                        <div><strong style="color:#162033;">Phone:</strong> <?= e($row['phone']) ?></div>
                                        <div><strong style="color:#162033;">Gender:</strong> <?= e(ucfirst($row['gender'] ?: '-')) ?></div>
                                        <div><strong style="color:#162033;">Role:</strong> <?= e(ucfirst($row['role'])) ?></div>
                                        <div><strong style="color:#162033;">Member Since:</strong> <?= e($row['created_at']) ?></div>
                                        <div><strong style="color:#162033;">Last Login:</strong> <?= e($row['last_login_at'] ?: '-') ?></div>
                                    </div>
                                </div>

                                <div style="min-width:260px;flex:0 0 320px;max-width:100%;">
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                                        <?php if ((int)$row['is_active'] === 1): ?>
                                            <span class="btn btn-soft btn-small" style="pointer-events:none;background:#dcfce7;color:#166534;border-color:#bbf7d0;min-height:34px;">Active</span>
                                        <?php else: ?>
                                            <span class="btn btn-soft btn-small" style="pointer-events:none;background:#fee2e2;color:#991b1b;border-color:#fecaca;min-height:34px;">Inactive</span>
                                        <?php endif; ?>

                                        <?php if (!empty($row['email_verified_at'])): ?>
                                            <span class="btn btn-soft btn-small" style="pointer-events:none;background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;min-height:34px;">Email Verified</span>
                                        <?php else: ?>
                                            <span class="btn btn-soft btn-small" style="pointer-events:none;background:#fff7ed;color:#c2410c;border-color:#fed7aa;min-height:34px;">Email Not Verified</span>
                                        <?php endif; ?>
                                    </div>

                                    <div style="padding:12px;border:1px solid #dbe4f0;border-radius:14px;background:#f8fbff;display:grid;gap:4px;font-size:14px;line-height:1.5;color:#5f6d83;">
                                        <div><strong style="color:#162033;">DOB:</strong> <?= e($row['dob'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">Nationality:</strong> <?= e($row['nationality'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">Marital Status:</strong> <?= e(ucfirst($row['marital_status'] ?: '-')) ?></div>
                                        <div><strong style="color:#162033;">City:</strong> <?= e($row['city'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">State:</strong> <?= e($row['state'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">Country:</strong> <?= e($row['country'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">Postal Code:</strong> <?= e($row['postal_code'] ?: '-') ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-actions" style="margin-top:12px;gap:8px;">
                                <?php if ((int)$row['is_active'] === 1): ?>
                                    <form method="post" action="" onsubmit="return confirm('Deactivate this user?');" style="margin:0;">
                                        <input type="hidden" name="action" value="deactivate_user">
                                        <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-soft btn-small">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="" style="margin:0;">
                                        <input type="hidden" name="action" value="activate_user">
                                        <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-small">Activate</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted" style="margin:0;">No users found for the selected filters.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>