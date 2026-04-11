<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$success = '';
$error = '';
$editId = (int)($_GET['edit_id'] ?? 0);

$form = [
    'name' => '',
    'slug' => '',
    'short_description' => '',
    'full_description' => '',
    'state_name' => '',
    'country_name' => 'India',
    'is_trending' => 0,
    'is_active' => 1,
    'hero_image' => '',
];

function makeSlug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim((string)$text, '-');
    return $text !== '' ? $text : 'destination';
}

function uploadDestinationHeroImage(?array $file, ?string &$errorMessage = null): ?string
{
    $errorMessage = null;

    if (
        !$file ||
        !isset($file['tmp_name']) ||
        $file['tmp_name'] === '' ||
        (int)$file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Image upload failed.';
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        $errorMessage = 'Only JPG, PNG, and WEBP images are allowed.';
        return null;
    }

    if ((int)$file['size'] > 5 * 1024 * 1024) {
        $errorMessage = 'Image size must be less than 5MB.';
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/destinations';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            $errorMessage = 'Could not create destination upload folder.';
            return null;
        }
    }

    $extension = $allowed[$mime];
    $fileName = 'destination_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
    $absolutePath = $uploadDir . '/' . $fileName;
    $relativePath = 'uploads/destinations/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        $errorMessage = 'Could not move uploaded image.';
        return null;
    }

    return $relativePath;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $destinationId = (int)($_POST['destination_id'] ?? 0);

    if ($action === 'save_destination') {
        $form['name'] = trim($_POST['name'] ?? '');
        $form['slug'] = trim($_POST['slug'] ?? '');
        $form['short_description'] = trim($_POST['short_description'] ?? '');
        $form['full_description'] = trim($_POST['full_description'] ?? '');
        $form['state_name'] = trim($_POST['state_name'] ?? '');
        $form['country_name'] = trim($_POST['country_name'] ?? 'India');
        $form['is_trending'] = isset($_POST['is_trending']) ? 1 : 0;
        $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $form['hero_image'] = trim($_POST['existing_hero_image'] ?? '');

        if ($form['name'] === '') {
            $error = 'Destination name is required.';
        } else {
            $form['slug'] = $form['slug'] === ''
                ? makeSlug($form['name'])
                : makeSlug($form['slug']);

            $uploadError = null;
            $newHeroImage = uploadDestinationHeroImage($_FILES['hero_image'] ?? null, $uploadError);

            if ($uploadError !== null) {
                $error = $uploadError;
            } else {
                if ($newHeroImage !== null) {
                    $form['hero_image'] = $newHeroImage;
                }

                try {
                    if ($destinationId > 0) {
                        $slugCheck = $pdo->prepare("SELECT id FROM destinations WHERE slug = ? AND id != ? LIMIT 1");
                        $slugCheck->execute([$form['slug'], $destinationId]);

                        if ($slugCheck->fetch()) {
                            $error = 'Slug already exists. Please use a different slug.';
                        } else {
                            $stmt = $pdo->prepare("
                                UPDATE destinations
                                SET
                                    name = ?,
                                    slug = ?,
                                    short_description = ?,
                                    full_description = ?,
                                    state_name = ?,
                                    country_name = ?,
                                    hero_image = ?,
                                    is_trending = ?,
                                    is_active = ?,
                                    updated_at = NOW()
                                WHERE id = ?
                            ");
                            $stmt->execute([
                                $form['name'],
                                $form['slug'],
                                $form['short_description'] !== '' ? $form['short_description'] : null,
                                $form['full_description'] !== '' ? $form['full_description'] : null,
                                $form['state_name'] !== '' ? $form['state_name'] : null,
                                $form['country_name'] !== '' ? $form['country_name'] : 'India',
                                $form['hero_image'] !== '' ? $form['hero_image'] : null,
                                $form['is_trending'],
                                $form['is_active'],
                                $destinationId
                            ]);

                            $success = 'Destination updated successfully.';
                            $editId = 0;
                            $form = [
                                'name' => '',
                                'slug' => '',
                                'short_description' => '',
                                'full_description' => '',
                                'state_name' => '',
                                'country_name' => 'India',
                                'is_trending' => 0,
                                'is_active' => 1,
                                'hero_image' => '',
                            ];
                        }
                    } else {
                        $slugCheck = $pdo->prepare("SELECT id FROM destinations WHERE slug = ? LIMIT 1");
                        $slugCheck->execute([$form['slug']]);

                        if ($slugCheck->fetch()) {
                            $error = 'Slug already exists. Please use a different slug.';
                        } else {
                            $stmt = $pdo->prepare("
                                INSERT INTO destinations (
                                    name, slug, short_description, full_description, state_name, country_name,
                                    hero_image, is_trending, is_active, created_at, updated_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                            ");
                            $stmt->execute([
                                $form['name'],
                                $form['slug'],
                                $form['short_description'] !== '' ? $form['short_description'] : null,
                                $form['full_description'] !== '' ? $form['full_description'] : null,
                                $form['state_name'] !== '' ? $form['state_name'] : null,
                                $form['country_name'] !== '' ? $form['country_name'] : 'India',
                                $form['hero_image'] !== '' ? $form['hero_image'] : null,
                                $form['is_trending'],
                                $form['is_active']
                            ]);

                            $success = 'Destination added successfully.';
                            $form = [
                                'name' => '',
                                'slug' => '',
                                'short_description' => '',
                                'full_description' => '',
                                'state_name' => '',
                                'country_name' => 'India',
                                'is_trending' => 0,
                                'is_active' => 1,
                                'hero_image' => '',
                            ];
                        }
                    }
                } catch (Throwable $e) {
                    $error = 'Something went wrong while saving the destination.';
                }
            }
        }
    }

    if ($action === 'delete_destination') {
        if ($destinationId <= 0) {
            $error = 'Invalid destination selected.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
                $stmt->execute([$destinationId]);
                $success = 'Destination deleted successfully.';
                if ($editId === $destinationId) {
                    $editId = 0;
                }
            } catch (Throwable $e) {
                $error = 'Could not delete destination. It may be linked to packages.';
            }
        }
    }

    if ($action === 'toggle_trending') {
        if ($destinationId <= 0) {
            $error = 'Invalid destination selected.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE destinations
                    SET is_trending = CASE WHEN is_trending = 1 THEN 0 ELSE 1 END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$destinationId]);
                $success = 'Trending status updated.';
            } catch (Throwable $e) {
                $error = 'Could not update trending status.';
            }
        }
    }

    if ($action === 'toggle_active') {
        if ($destinationId <= 0) {
            $error = 'Invalid destination selected.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE destinations
                    SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$destinationId]);
                $success = 'Active status updated.';
            } catch (Throwable $e) {
                $error = 'Could not update active status.';
            }
        }
    }
}

if ($editId > 0) {
    try {
        $editStmt = $pdo->prepare("
            SELECT id, name, slug, short_description, full_description, state_name, country_name, hero_image, is_trending, is_active
            FROM destinations
            WHERE id = ?
            LIMIT 1
        ");
        $editStmt->execute([$editId]);
        $editRow = $editStmt->fetch();

        if ($editRow) {
            $form['name'] = $editRow['name'] ?? '';
            $form['slug'] = $editRow['slug'] ?? '';
            $form['short_description'] = $editRow['short_description'] ?? '';
            $form['full_description'] = $editRow['full_description'] ?? '';
            $form['state_name'] = $editRow['state_name'] ?? '';
            $form['country_name'] = $editRow['country_name'] ?? 'India';
            $form['hero_image'] = $editRow['hero_image'] ?? '';
            $form['is_trending'] = (int)($editRow['is_trending'] ?? 0);
            $form['is_active'] = (int)($editRow['is_active'] ?? 1);
        } else {
            $editId = 0;
        }
    } catch (Throwable $e) {
        $editId = 0;
    }
}

$destinations = [];
try {
    $stmt = $pdo->query("
        SELECT id, name, slug, state_name, country_name, hero_image, is_trending, is_active, created_at
        FROM destinations
        ORDER BY id DESC
    ");
    $destinations = $stmt->fetchAll();
} catch (Throwable $e) {
    $destinations = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">
        <div class="section-head admin-packages-head" style="margin-bottom: 12px;">
            <span class="badge">Admin</span>
            <h2>Manage Destinations</h2>
            <p>Add, edit, organize, and publish your travel destinations.</p>
        </div>

        <div class="admin-topbar admin-packages-topbar" style="margin-bottom: 12px;">
            <div class="admin-back-links admin-packages-back-links">
                <a class="btn-back" href="<?= BASE_URL ?>/admin/dashboard.php">← Dashboard</a>
                <a class="btn-back" href="<?= BASE_URL ?>/index.php">← Website</a>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="notice-error" style="margin-bottom:12px;"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="notice-success" style="margin-bottom:12px;"><?= e($success) ?></div>
        <?php endif; ?>

        <div style="display:flex; justify-content:flex-end; margin-bottom:14px;">
    <div class="info-card admin-destination-notes" style="width:100%; max-width:1240px;">
                <h3>Destination Notes</h3>
                <div class="admin-destination-note-list">
                    <div>Use a unique slug for each destination.* Packages can later be mapped under these destinations.* Trending destinations appear first on the homepage.* Active destinations are visible publicly.</div>
                </div>
            </div>
        </div>

        <div class="info-card admin-destination-form-card admin-packages-card" style="margin-bottom:14px;">
            <div class="admin-destination-form-head">
                <h3><?= $editId > 0 ? 'Edit Destination' : 'Add Destination' ?></h3>
            </div>

            <form method="post" action="" enctype="multipart/form-data" class="contact-form" style="gap:12px;">
                <input type="hidden" name="action" value="save_destination">
                <input type="hidden" name="destination_id" value="<?= $editId ?>">
                <input type="hidden" name="existing_hero_image" value="<?= e($form['hero_image']) ?>">

                <div class="form-grid-2">
                    <div class="field-wrap">
                        <label class="field-label">Destination Name</label>
                        <input type="text" name="name" value="<?= e($form['name']) ?>" required>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Slug</label>
                        <input type="text" name="slug" value="<?= e($form['slug']) ?>" placeholder="auto-generated-if-empty">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">State</label>
                        <input type="text" name="state_name" value="<?= e($form['state_name']) ?>">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Country</label>
                        <input type="text" name="country_name" value="<?= e($form['country_name']) ?>">
                    </div>

                    <div class="field-wrap" style="grid-column: span 2;">
                        <label class="field-label">Short Description</label>
                        <textarea name="short_description" rows="3"><?= e($form['short_description']) ?></textarea>
                    </div>

                    <div class="field-wrap" style="grid-column: span 2;">
                        <label class="field-label">Full Description</label>
                        <textarea name="full_description" rows="5"><?= e($form['full_description']) ?></textarea>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Hero Image</label>
                        <input type="file" name="hero_image" accept=".jpg,.jpeg,.png,.webp">
                        <div class="field-hint">Upload JPG, PNG, or WEBP up to 5MB.</div>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Options</label>
                        <div class="admin-destination-options">
                            <label>
                                <input type="checkbox" name="is_trending" value="1" <?= (int)$form['is_trending'] === 1 ? 'checked' : '' ?>>
                                Trending
                            </label>
                            <label>
                                <input type="checkbox" name="is_active" value="1" <?= (int)$form['is_active'] === 1 ? 'checked' : '' ?>>
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <?php if ($form['hero_image'] !== ''): ?>
                    <div class="admin-destination-current-image">
                        <p>Current Image</p>
                        <img src="<?= e(getImageUrl($form['hero_image'])) ?>" alt="Destination image">
                    </div>
                <?php endif; ?>

                <div class="form-actions admin-packages-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editId > 0 ? 'Update Destination' : 'Add Destination' ?>
                    </button>

                    <?php if ($editId > 0): ?>
                        <a href="<?= BASE_URL ?>/admin/destinations.php" class="btn btn-soft">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-card admin-packages-card">
            <h3 style="margin-bottom:10px;">All Destinations</h3>

            <?php if (!empty($destinations)): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Location</th>
                                <th>Trending</th>
                                <th>Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($destinations as $row): ?>
                                <tr>
                                    <td>
                                        <img
                                            src="<?= e(getImageUrl($row['hero_image'])) ?>"
                                            alt="<?= e($row['name']) ?>"
                                            style="width:72px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #dbe4f0;"
                                        >
                                    </td>
                                    <td><?= e($row['name']) ?></td>
                                    <td><?= e($row['slug']) ?></td>
                                    <td><?= e(($row['state_name'] ?: '-') . ', ' . ($row['country_name'] ?: '-')) ?></td>
                                    <td><?= (int)$row['is_trending'] === 1 ? 'Yes' : 'No' ?></td>
                                    <td><?= (int)$row['is_active'] === 1 ? 'Yes' : 'No' ?></td>
                                    <td>
                                        <div class="form-actions" style="gap:8px;">
                                            <a class="btn btn-soft btn-small" href="<?= BASE_URL ?>/admin/destinations.php?edit_id=<?= (int)$row['id'] ?>">Edit</a>

                                            <form method="post" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_trending">
                                                <input type="hidden" name="destination_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" class="btn btn-soft btn-small">
                                                    <?= (int)$row['is_trending'] === 1 ? 'Untrend' : 'Trend' ?>
                                                </button>
                                            </form>

                                            <form method="post" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="destination_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" class="btn btn-soft btn-small">
                                                    <?= (int)$row['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>

                                            <form method="post" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this destination?');">
                                                <input type="hidden" name="action" value="delete_destination">
                                                <input type="hidden" name="destination_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="muted" style="margin:0;">No destinations found yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>