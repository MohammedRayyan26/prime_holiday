<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$success = '';
$error = '';
$filter = trim($_GET['filter'] ?? 'all');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $messageId = (int)($_POST['message_id'] ?? 0);

    if ($messageId <= 0) {
        $error = 'Invalid message selected.';
    } else {
        try {
            if ($action === 'mark_replied') {
                $stmt = $pdo->prepare("
                    UPDATE contact_messages
                    SET is_replied = 1
                    WHERE id = ?
                ");
                $stmt->execute([$messageId]);
                $success = 'Message marked as replied.';
            } elseif ($action === 'mark_unreplied') {
                $stmt = $pdo->prepare("
                    UPDATE contact_messages
                    SET is_replied = 0
                    WHERE id = ?
                ");
                $stmt->execute([$messageId]);
                $success = 'Message marked as unreplied.';
            } elseif ($action === 'delete_message') {
                $stmt = $pdo->prepare("
                    DELETE FROM contact_messages
                    WHERE id = ?
                ");
                $stmt->execute([$messageId]);
                $success = 'Message deleted successfully.';
            } else {
                $error = 'Invalid action.';
            }
        } catch (Throwable $e) {
            $error = 'Something went wrong while updating the message.';
        }
    }
}

$whereSql = '';
$params = [];

if ($filter === 'replied') {
    $whereSql = 'WHERE is_replied = 1';
} elseif ($filter === 'unreplied') {
    $whereSql = 'WHERE is_replied = 0';
}

$messages = [];
$totalCount = 0;
$repliedCount = 0;
$unrepliedCount = 0;

try {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM contact_messages");
    $totalCount = (int)$countStmt->fetchColumn();

    $repliedStmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_replied = 1");
    $repliedCount = (int)$repliedStmt->fetchColumn();

    $unrepliedStmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_replied = 0");
    $unrepliedCount = (int)$unrepliedStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, subject, message, is_replied, created_at
        FROM contact_messages
        $whereSql
        ORDER BY id DESC
    ");
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Unable to load contact messages.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">

        <div class="section-head admin-packages-head" style="margin-bottom:16px;">
            <span class="badge">Admin</span>
            <h2 style="margin:10px 0 8px;">Contact Messages</h2>
            <p style="margin:0;">View customer enquiries, mark replies, and manage incoming messages.</p>
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
                <h3 style="margin-bottom:8px;">Total Messages</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;"><?= $totalCount ?></p>
            </div>
            <div class="info-card" style="padding:20px 22px;">
                <h3 style="margin-bottom:8px;">Replied</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;color:#166534;"><?= $repliedCount ?></p>
            </div>
            <div class="info-card" style="padding:20px 22px;">
                <h3 style="margin-bottom:8px;">Unreplied</h3>
                <p style="font-size:32px;font-weight:800;margin:0;line-height:1;color:#991b1b;"><?= $unrepliedCount ?></p>
            </div>
        </div>

        <div class="info-card admin-packages-card" style="margin-bottom:14px;">
            <div class="card-actions" style="gap:10px;">
                <a href="<?= BASE_URL ?>/admin/contact_messages.php?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-soft' ?>">All</a>
                <a href="<?= BASE_URL ?>/admin/contact_messages.php?filter=unreplied" class="btn <?= $filter === 'unreplied' ? 'btn-primary' : 'btn-soft' ?>">Unreplied</a>
                <a href="<?= BASE_URL ?>/admin/contact_messages.php?filter=replied" class="btn <?= $filter === 'replied' ? 'btn-primary' : 'btn-soft' ?>">Replied</a>
            </div>
        </div>

        <div class="table-card" style="padding:20px 22px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
                <h3 style="margin:0;">Message List</h3>
            </div>

            <?php if (!empty($messages)): ?>
                <div style="display:grid;gap:12px;">
                    <?php foreach ($messages as $row): ?>
                        <div style="border:1px solid #e5eaf2;border-radius:16px;padding:14px 16px;background:#fff;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;">
                                <div style="flex:1;min-width:260px;">
                                    <h3 style="margin:0 0 8px;font-size:20px;"><?= e($row['full_name']) ?></h3>

                                    <div style="display:grid;gap:4px;color:#5f6d83;font-size:14px;line-height:1.5;">
                                        <div><strong style="color:#162033;">Email:</strong> <?= e($row['email']) ?></div>
                                        <div><strong style="color:#162033;">Phone:</strong> <?= e($row['phone'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">Subject:</strong> <?= e($row['subject'] ?: '-') ?></div>
                                        <div><strong style="color:#162033;">Date:</strong> <?= e($row['created_at']) ?></div>
                                    </div>
                                </div>

                                <div style="min-width:140px;">
                                    <?php if ((int)$row['is_replied'] === 1): ?>
                                        <span class="btn btn-soft btn-small" style="pointer-events:none;background:#dcfce7;color:#166534;border-color:#bbf7d0;min-height:34px;">Replied</span>
                                    <?php else: ?>
                                        <span class="btn btn-soft btn-small" style="pointer-events:none;background:#fee2e2;color:#991b1b;border-color:#fecaca;min-height:34px;">Unreplied</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="margin-top:12px;padding:12px 14px;border:1px solid #dbe4f0;border-radius:14px;background:#f8fbff;color:#4b5b73;line-height:1.6;">
                                <?= nl2br(e($row['message'])) ?>
                            </div>

                            <div class="card-actions" style="margin-top:12px;gap:8px;">
                                <?php if ((int)$row['is_replied'] === 0): ?>
                                    <form method="post" action="" style="margin:0;">
                                        <input type="hidden" name="action" value="mark_replied">
                                        <input type="hidden" name="message_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-small">Mark Replied</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="" style="margin:0;">
                                        <input type="hidden" name="action" value="mark_unreplied">
                                        <input type="hidden" name="message_id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-soft btn-small">Mark Unreplied</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this message?');" style="margin:0;">
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="padding:6px 0 0;">
                    <h3 style="margin:0 0 6px;">No contact messages found</h3>
                    <p class="muted" style="margin:0;">There are no messages for this filter right now.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>