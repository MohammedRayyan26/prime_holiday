<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$error = '';
$success = '';
$editData = null;

$uploadDirFs = __DIR__ . '/../uploads/packages/';
$uploadDirDb = 'uploads/packages/';

if (!is_dir($uploadDirFs)) {
    @mkdir($uploadDirFs, 0777, true);
}

function makeSlugSafeFileName(string $name): string
{
    $name = preg_replace('/[^a-zA-Z0-9\._-]/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    return trim($name, '-');
}

function uploadSingleImage(string $inputName, string $uploadDirFs, string $uploadDirDb, ?string &$error): ?string
{
    if (
        !isset($_FILES[$inputName]) ||
        !is_array($_FILES[$inputName]) ||
        ($_FILES[$inputName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    $file = $_FILES[$inputName];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Failed to upload image for ' . $inputName . '.';
        return null;
    }

    $tmpPath = $file['tmp_name'];
    $originalName = $file['name'] ?? 'image';
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        $error = 'Only JPG, JPEG, PNG, and WEBP images are allowed.';
        return null;
    }

    $newName = time() . '_' . rand(1000, 9999) . '_' . makeSlugSafeFileName(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $ext;
    $destination = $uploadDirFs . $newName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        $error = 'Could not save uploaded image.';
        return null;
    }

    return $uploadDirDb . $newName;
}

function uploadMultipleImages(string $inputName, string $uploadDirFs, string $uploadDirDb, ?string &$error): array
{
    $saved = [];

    if (
        !isset($_FILES[$inputName]) ||
        !is_array($_FILES[$inputName]['name'] ?? null)
    ) {
        return $saved;
    }

    $names = $_FILES[$inputName]['name'];
    $tmpNames = $_FILES[$inputName]['tmp_name'];
    $errors = $_FILES[$inputName]['error'];

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    foreach ($names as $i => $originalName) {
        if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = 'One of the gallery images failed to upload.';
            continue;
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $error = 'Only JPG, JPEG, PNG, and WEBP gallery images are allowed.';
            continue;
        }

        $newName = time() . '_' . rand(1000, 9999) . '_' . makeSlugSafeFileName(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $ext;
        $destination = $uploadDirFs . $newName;

        if (move_uploaded_file($tmpNames[$i], $destination)) {
            $saved[] = $uploadDirDb . $newName;
        } else {
            $error = 'One gallery image could not be saved.';
        }
    }

    return $saved;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $success = 'Package added successfully.';
    if ($_GET['msg'] === 'updated') $success = 'Package updated successfully.';
    if ($_GET['msg'] === 'deleted') $success = 'Package deleted successfully.';
    if ($_GET['msg'] === 'gallery_deleted') $success = 'Gallery image deleted successfully.';
    if ($_GET['msg'] === 'itinerary_added') $success = 'Itinerary day added successfully.';
    if ($_GET['msg'] === 'itinerary_updated') $success = 'Itinerary day updated successfully.';
    if ($_GET['msg'] === 'itinerary_deleted') $success = 'Itinerary day deleted successfully.';
    if ($_GET['msg'] === 'include_added') $success = 'Include item added successfully.';
    if ($_GET['msg'] === 'include_updated') $success = 'Include item updated successfully.';
    if ($_GET['msg'] === 'include_deleted') $success = 'Include item deleted successfully.';
    if ($_GET['msg'] === 'exclude_added') $success = 'Exclude item added successfully.';
    if ($_GET['msg'] === 'exclude_updated') $success = 'Exclude item updated successfully.';
    if ($_GET['msg'] === 'exclude_deleted') $success = 'Exclude item deleted successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_package'])) {
    $packageId = (int)($_POST['package_id'] ?? 0);
    $destinationId = (int)($_POST['destination_id'] ?? 0);
    $packageName = trim($_POST['package_name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $offerPrice = trim($_POST['offer_price'] ?? '');
    $durationDays = (int)($_POST['duration_days'] ?? 1);
    $durationNights = (int)($_POST['duration_nights'] ?? 0);
    $busNumber = trim($_POST['bus_number'] ?? '');
    $driverName = trim($_POST['driver_name'] ?? '');
    $departureFrom = trim($_POST['departure_from'] ?? '');
    $seatsAvailable = (int)($_POST['seats_available'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if ($destinationId <= 0 || $packageName === '' || $slug === '' || $price === '') {
        $error = 'Please fill all required package fields and select a valid destination.';
    } else {
        try {
            $pdo->beginTransaction();

            $existingFeatured = null;
            $existingCover = null;

            if ($packageId > 0) {
                $existingStmt = $pdo->prepare("SELECT featured_image, cover_image FROM packages WHERE id = ? LIMIT 1");
                $existingStmt->execute([$packageId]);
                $existingRow = $existingStmt->fetch();

                if (!$existingRow) {
                    throw new RuntimeException('Package not found.');
                }

                $existingFeatured = $existingRow['featured_image'];
                $existingCover = $existingRow['cover_image'];
            }

            $featuredImagePath = uploadSingleImage('featured_image', $uploadDirFs, $uploadDirDb, $error);
            $coverImagePath = uploadSingleImage('cover_image', $uploadDirFs, $uploadDirDb, $error);

            if ($packageId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE packages
                    SET destination_id = ?, package_name = ?, slug = ?, short_description = ?, description = ?, recommendations = ?,
                        price = ?, offer_price = ?, duration_days = ?, duration_nights = ?, bus_number = ?, driver_name = ?,
                        departure_from = ?, seats_available = ?, featured_image = ?, cover_image = ?, is_featured = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $destinationId,
                    $packageName,
                    $slug,
                    $shortDescription !== '' ? $shortDescription : null,
                    $description !== '' ? $description : null,
                    $recommendations !== '' ? $recommendations : null,
                    $price,
                    $offerPrice !== '' ? $offerPrice : null,
                    $durationDays,
                    $durationNights,
                    $busNumber !== '' ? $busNumber : null,
                    $driverName !== '' ? $driverName : null,
                    $departureFrom !== '' ? $departureFrom : null,
                    $seatsAvailable,
                    $featuredImagePath ?: $existingFeatured,
                    $coverImagePath ?: $existingCover,
                    $isFeatured,
                    $packageId
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO packages (
                        destination_id, package_name, slug, short_description, description, recommendations,
                        price, offer_price, duration_days, duration_nights, bus_number, driver_name,
                        departure_from, seats_available, featured_image, cover_image,
                        is_featured, is_active, created_by, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $destinationId,
                    $packageName,
                    $slug,
                    $shortDescription !== '' ? $shortDescription : null,
                    $description !== '' ? $description : null,
                    $recommendations !== '' ? $recommendations : null,
                    $price,
                    $offerPrice !== '' ? $offerPrice : null,
                    $durationDays,
                    $durationNights,
                    $busNumber !== '' ? $busNumber : null,
                    $driverName !== '' ? $driverName : null,
                    $departureFrom !== '' ? $departureFrom : null,
                    $seatsAvailable,
                    $featuredImagePath,
                    $coverImagePath,
                    $isFeatured,
                    $_SESSION['user_id']
                ]);

                $packageId = (int)$pdo->lastInsertId();
            }

            $galleryPaths = uploadMultipleImages('gallery_images', $uploadDirFs, $uploadDirDb, $error);

            if (!empty($galleryPaths)) {
                $sortStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM package_images WHERE package_id = ?");
                $sortStmt->execute([$packageId]);
                $sortStart = (int)$sortStmt->fetchColumn();

                $imgInsert = $pdo->prepare("
                    INSERT INTO package_images (package_id, image_path, alt_text, sort_order, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");

                foreach ($galleryPaths as $index => $path) {
                    $imgInsert->execute([
                        $packageId,
                        $path,
                        $packageName . ' image',
                        $sortStart + $index + 1
                    ]);
                }
            }

            $pdo->commit();

            if ((int)($_POST['package_id'] ?? 0) > 0) {
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=updated');
            } else {
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=added');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($error === '') {
                $error = 'Failed to save package. Slug may already exist or upload failed.';
            }
        }
    }
}

/* ---------- Itinerary ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_itinerary'])) {
    $packageId = (int)($_POST['package_id'] ?? 0);
    $itineraryId = (int)($_POST['itinerary_id'] ?? 0);
    $dayNumber = (int)($_POST['day_number'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['itinerary_description'] ?? '');
    $overnightStay = trim($_POST['overnight_stay'] ?? '');

    if ($packageId <= 0 || $dayNumber <= 0 || $title === '') {
        $error = 'Please fill itinerary day number and title.';
    } else {
        try {
            if ($itineraryId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE package_itinerary_days
                    SET day_number = ?, title = ?, description = ?, overnight_stay = ?, updated_at = NOW()
                    WHERE id = ? AND package_id = ?
                ");
                $stmt->execute([
                    $dayNumber,
                    $title,
                    $description !== '' ? $description : null,
                    $overnightStay !== '' ? $overnightStay : null,
                    $itineraryId,
                    $packageId
                ]);
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=itinerary_updated');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO package_itinerary_days (package_id, day_number, title, description, overnight_stay, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $packageId,
                    $dayNumber,
                    $title,
                    $description !== '' ? $description : null,
                    $overnightStay !== '' ? $overnightStay : null
                ]);
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=itinerary_added');
            }
        } catch (Throwable $e) {
            $error = 'Failed to save itinerary. Day number may already exist for this package.';
        }
    }
}

if (isset($_GET['delete_itinerary']) && (int)$_GET['delete_itinerary'] > 0 && (int)($_GET['edit'] ?? 0) > 0) {
    $itineraryId = (int)$_GET['delete_itinerary'];
    $packageId = (int)$_GET['edit'];

    try {
        $stmt = $pdo->prepare("DELETE FROM package_itinerary_days WHERE id = ? AND package_id = ?");
        $stmt->execute([$itineraryId, $packageId]);
        redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=itinerary_deleted');
    } catch (Throwable $e) {
        $error = 'Failed to delete itinerary day.';
    }
}

/* ---------- Includes ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_include'])) {
    $packageId = (int)($_POST['package_id'] ?? 0);
    $includeId = (int)($_POST['include_id'] ?? 0);
    $itemText = trim($_POST['item_text'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if ($packageId <= 0 || $itemText === '') {
        $error = 'Please enter include item text.';
    } else {
        try {
            if ($includeId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE package_includes
                    SET item_text = ?, sort_order = ?
                    WHERE id = ? AND package_id = ?
                ");
                $stmt->execute([$itemText, $sortOrder, $includeId, $packageId]);
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=include_updated');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO package_includes (package_id, item_text, sort_order, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$packageId, $itemText, $sortOrder]);
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=include_added');
            }
        } catch (Throwable $e) {
            $error = 'Failed to save include item.';
        }
    }
}

if (isset($_GET['delete_include']) && (int)$_GET['delete_include'] > 0 && (int)($_GET['edit'] ?? 0) > 0) {
    $includeId = (int)$_GET['delete_include'];
    $packageId = (int)$_GET['edit'];

    try {
        $stmt = $pdo->prepare("DELETE FROM package_includes WHERE id = ? AND package_id = ?");
        $stmt->execute([$includeId, $packageId]);
        redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=include_deleted');
    } catch (Throwable $e) {
        $error = 'Failed to delete include item.';
    }
}

/* ---------- Excludes ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_exclude'])) {
    $packageId = (int)($_POST['package_id'] ?? 0);
    $excludeId = (int)($_POST['exclude_id'] ?? 0);
    $itemText = trim($_POST['item_text'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if ($packageId <= 0 || $itemText === '') {
        $error = 'Please enter exclude item text.';
    } else {
        try {
            if ($excludeId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE package_excludes
                    SET item_text = ?, sort_order = ?
                    WHERE id = ? AND package_id = ?
                ");
                $stmt->execute([$itemText, $sortOrder, $excludeId, $packageId]);
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=exclude_updated');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO package_excludes (package_id, item_text, sort_order, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$packageId, $itemText, $sortOrder]);
                redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=exclude_added');
            }
        } catch (Throwable $e) {
            $error = 'Failed to save exclude item.';
        }
    }
}

if (isset($_GET['delete_exclude']) && (int)$_GET['delete_exclude'] > 0 && (int)($_GET['edit'] ?? 0) > 0) {
    $excludeId = (int)$_GET['delete_exclude'];
    $packageId = (int)$_GET['edit'];

    try {
        $stmt = $pdo->prepare("DELETE FROM package_excludes WHERE id = ? AND package_id = ?");
        $stmt->execute([$excludeId, $packageId]);
        redirectTo(BASE_URL . '/admin/packages.php?edit=' . $packageId . '&msg=exclude_deleted');
    } catch (Throwable $e) {
        $error = 'Failed to delete exclude item.';
    }
}

/* ---------- Package delete / gallery delete ---------- */
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->execute([(int)$_GET['delete']]);
        redirectTo(BASE_URL . '/admin/packages.php?msg=deleted');
    } catch (Throwable $e) {
        $error = 'Failed to delete package.';
    }
}

if (isset($_GET['delete_gallery']) && (int)$_GET['delete_gallery'] > 0) {
    $galleryId = (int)$_GET['delete_gallery'];

    try {
        $stmt = $pdo->prepare("DELETE FROM package_images WHERE id = ?");
        $stmt->execute([$galleryId]);
        $backPackage = (int)($_GET['edit'] ?? 0);
        if ($backPackage > 0) {
            redirectTo(BASE_URL . '/admin/packages.php?edit=' . $backPackage . '&msg=gallery_deleted');
        } else {
            redirectTo(BASE_URL . '/admin/packages.php?msg=gallery_deleted');
        }
    } catch (Throwable $e) {
        $error = 'Failed to delete gallery image.';
    }
}

/* ---------- Edit package load ---------- */
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}

$editGalleryImages = [];
$itineraryRows = [];
$includeRows = [];
$excludeRows = [];
$editItinerary = null;
$editInclude = null;
$editExclude = null;

if ($editData) {
    $galleryStmt = $pdo->prepare("
        SELECT id, image_path, alt_text, sort_order
        FROM package_images
        WHERE package_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $galleryStmt->execute([(int)$editData['id']]);
    $editGalleryImages = $galleryStmt->fetchAll();

    $itineraryStmt = $pdo->prepare("
        SELECT *
        FROM package_itinerary_days
        WHERE package_id = ?
        ORDER BY day_number ASC, id ASC
    ");
    $itineraryStmt->execute([(int)$editData['id']]);
    $itineraryRows = $itineraryStmt->fetchAll();

    $includeStmt = $pdo->prepare("
        SELECT *
        FROM package_includes
        WHERE package_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $includeStmt->execute([(int)$editData['id']]);
    $includeRows = $includeStmt->fetchAll();

    $excludeStmt = $pdo->prepare("
        SELECT *
        FROM package_excludes
        WHERE package_id = ?
        ORDER BY sort_order ASC, id ASC
    ");
    $excludeStmt->execute([(int)$editData['id']]);
    $excludeRows = $excludeStmt->fetchAll();

    if (isset($_GET['edit_itinerary']) && (int)$_GET['edit_itinerary'] > 0) {
        $stmt = $pdo->prepare("SELECT * FROM package_itinerary_days WHERE id = ? AND package_id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['edit_itinerary'], (int)$editData['id']]);
        $editItinerary = $stmt->fetch();
    }

    if (isset($_GET['edit_include']) && (int)$_GET['edit_include'] > 0) {
        $stmt = $pdo->prepare("SELECT * FROM package_includes WHERE id = ? AND package_id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['edit_include'], (int)$editData['id']]);
        $editInclude = $stmt->fetch();
    }

    if (isset($_GET['edit_exclude']) && (int)$_GET['edit_exclude'] > 0) {
        $stmt = $pdo->prepare("SELECT * FROM package_excludes WHERE id = ? AND package_id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['edit_exclude'], (int)$editData['id']]);
        $editExclude = $stmt->fetch();
    }
}

$destinations = $pdo->query("SELECT id, name FROM destinations WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

$packages = $pdo->query("
    SELECT p.*, d.name AS destination_name
    FROM packages p
    INNER JOIN destinations d ON d.id = p.destination_id
    ORDER BY p.id DESC
")->fetchAll();

$selectedDestinationName = '';
if (!empty($editData['destination_id'])) {
    foreach ($destinations as $destination) {
        if ((int)$destination['id'] === (int)$editData['destination_id']) {
            $selectedDestinationName = $destination['name'];
            break;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">
        <div class="section-head admin-packages-head">
            <span class="badge">Admin Packages</span>
            <h2>Manage Packages</h2>
            <p>Add, edit, delete, upload images, and manage package details from one place.</p>
        </div>

        <div class="admin-topbar admin-packages-topbar">
            <div class="admin-back-links admin-packages-back-links">
                <a class="btn-back" href="<?= BASE_URL ?>/admin/dashboard.php">← Dashboard</a>
                <a class="btn-back" href="<?= BASE_URL ?>/index.php">← Website</a>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="notice-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="notice-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="info-card admin-packages-card">
            <h3><?= $editData ? 'Edit Package' : 'Add Package' ?></h3>

            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="save_package" value="1">
                <input type="hidden" name="package_id" value="<?= (int)($editData['id'] ?? 0) ?>">

                <div class="form-grid-2">
                    <div class="field-wrap">
                        <label class="field-label">Destination</label>

                        <input
                            type="text"
                            id="destination_search"
                            list="destination_list"
                            placeholder="Type city name like Goa, Gokarna, Gulmarg"
                            value="<?= e($selectedDestinationName) ?>"
                            autocomplete="off"
                            required
                        >

                        <datalist id="destination_list">
                            <?php foreach ($destinations as $destination): ?>
                                <option value="<?= e($destination['name']) ?>" data-id="<?= (int)$destination['id'] ?>"></option>
                            <?php endforeach; ?>
                        </datalist>

                        <input
                            type="hidden"
                            name="destination_id"
                            id="destination_id"
                            value="<?= (int)($editData['destination_id'] ?? 0) ?>"
                            required
                        >

                        <div class="field-hint">Start typing and choose a valid destination from the suggestions.</div>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Package Name</label>
                        <input type="text" name="package_name" value="<?= e($editData['package_name'] ?? '') ?>" required>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Slug</label>
                        <input type="text" name="slug" value="<?= e($editData['slug'] ?? '') ?>" required>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Price</label>
                        <input type="number" step="0.01" name="price" value="<?= e($editData['price'] ?? '') ?>" required>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Offer Price</label>
                        <input type="number" step="0.01" name="offer_price" value="<?= e($editData['offer_price'] ?? '') ?>">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Duration Days</label>
                        <input type="number" name="duration_days" value="<?= e($editData['duration_days'] ?? 1) ?>" required>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Duration Nights</label>
                        <input type="number" name="duration_nights" value="<?= e($editData['duration_nights'] ?? 0) ?>" required>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Bus Number</label>
                        <input type="text" name="bus_number" value="<?= e($editData['bus_number'] ?? '') ?>">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Driver Name</label>
                        <input type="text" name="driver_name" value="<?= e($editData['driver_name'] ?? '') ?>">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Departure From</label>
                        <input type="text" name="departure_from" value="<?= e($editData['departure_from'] ?? '') ?>">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Seats Available</label>
                        <input type="number" name="seats_available" value="<?= e($editData['seats_available'] ?? 0) ?>">
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Featured Image</label>
                        <input type="file" name="featured_image" accept=".jpg,.jpeg,.png,.webp">
                        <?php if (!empty($editData['featured_image'])): ?>
                            <div class="field-hint">Current image</div>
                            <img src="<?= BASE_URL . '/' . e($editData['featured_image']) ?>" alt="" style="max-width:140px;border-radius:12px;margin-top:8px;">
                        <?php endif; ?>
                    </div>

                    <div class="field-wrap">
                        <label class="field-label">Cover Image</label>
                        <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                        <?php if (!empty($editData['cover_image'])): ?>
                            <div class="field-hint">Current image</div>
                            <img src="<?= BASE_URL . '/' . e($editData['cover_image']) ?>" alt="" style="max-width:140px;border-radius:12px;margin-top:8px;">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field-wrap admin-packages-field-gap">
                    <label class="field-label">Short Description</label>
                    <input type="text" name="short_description" value="<?= e($editData['short_description'] ?? '') ?>">
                </div>

                <div class="field-wrap admin-packages-field-gap">
                    <label class="field-label">Full Description</label>
                    <textarea name="description"><?= e($editData['description'] ?? '') ?></textarea>
                </div>

                <div class="field-wrap admin-packages-field-gap">
                    <label class="field-label">Recommendations</label>
                    <textarea name="recommendations"><?= e($editData['recommendations'] ?? '') ?></textarea>
                </div>

                <div class="field-wrap admin-packages-field-gap">
                    <label class="field-label">Gallery Images</label>
                    <input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
                    <div class="field-hint">Select one or more package gallery images.</div>
                </div>

                <?php if (!empty($editGalleryImages)): ?>
                    <div class="field-wrap admin-packages-field-gap">
                        <label class="field-label">Existing Gallery</label>
                        <div class="admin-gallery-grid">
                            <?php foreach ($editGalleryImages as $img): ?>
                                <div class="admin-gallery-card">
                                    <img src="<?= BASE_URL . '/' . e($img['image_path']) ?>" alt="" class="admin-gallery-image">
                                    <div class="admin-gallery-actions">
                                        <a class="btn btn-small btn-danger" href="?edit=<?= (int)$editData['id'] ?>&delete_gallery=<?= (int)$img['id'] ?>" onclick="return confirm('Delete this gallery image?')">Delete</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-actions admin-packages-form-actions">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="is_featured" value="1" <?= ((int)($editData['is_featured'] ?? 0) === 1) ? 'checked' : '' ?>>
                        Featured Package
                    </label>

                    <button type="submit" class="btn btn-primary"><?= $editData ? 'Update Package' : 'Add Package' ?></button>

                    <?php if ($editData): ?>
                        <a class="btn btn-soft" href="<?= BASE_URL ?>/admin/packages.php">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($editData): ?>
            <div class="info-grid admin-packages-grid-gap">
                <div class="info-card">
                    <h3><?= $editItinerary ? 'Edit Itinerary Day' : 'Add Itinerary Day' ?></h3>
                    <form method="post" action="">
                        <input type="hidden" name="save_itinerary" value="1">
                        <input type="hidden" name="package_id" value="<?= (int)$editData['id'] ?>">
                        <input type="hidden" name="itinerary_id" value="<?= (int)($editItinerary['id'] ?? 0) ?>">

                        <div class="contact-form">
                            <input type="number" name="day_number" placeholder="Day Number" value="<?= e($editItinerary['day_number'] ?? '') ?>" required>
                            <input type="text" name="title" placeholder="Title" value="<?= e($editItinerary['title'] ?? '') ?>" required>
                            <input type="text" name="overnight_stay" placeholder="Overnight Stay" value="<?= e($editItinerary['overnight_stay'] ?? '') ?>">
                            <textarea name="itinerary_description" placeholder="Description"><?= e($editItinerary['description'] ?? '') ?></textarea>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><?= $editItinerary ? 'Update Day' : 'Add Day' ?></button>
                                <?php if ($editItinerary): ?>
                                    <a class="btn btn-soft" href="<?= BASE_URL ?>/admin/packages.php?edit=<?= (int)$editData['id'] ?>">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="info-card">
                    <h3><?= $editInclude ? 'Edit Include' : 'Add Include' ?></h3>
                    <form method="post" action="">
                        <input type="hidden" name="save_include" value="1">
                        <input type="hidden" name="package_id" value="<?= (int)$editData['id'] ?>">
                        <input type="hidden" name="include_id" value="<?= (int)($editInclude['id'] ?? 0) ?>">

                        <div class="contact-form">
                            <input type="text" name="item_text" placeholder="Include item" value="<?= e($editInclude['item_text'] ?? '') ?>" required>
                            <input type="number" name="sort_order" placeholder="Sort Order" value="<?= e($editInclude['sort_order'] ?? 0) ?>">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><?= $editInclude ? 'Update Include' : 'Add Include' ?></button>
                                <?php if ($editInclude): ?>
                                    <a class="btn btn-soft" href="<?= BASE_URL ?>/admin/packages.php?edit=<?= (int)$editData['id'] ?>">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="info-card">
                    <h3><?= $editExclude ? 'Edit Exclude' : 'Add Exclude' ?></h3>
                    <form method="post" action="">
                        <input type="hidden" name="save_exclude" value="1">
                        <input type="hidden" name="package_id" value="<?= (int)$editData['id'] ?>">
                        <input type="hidden" name="exclude_id" value="<?= (int)($editExclude['id'] ?? 0) ?>">

                        <div class="contact-form">
                            <input type="text" name="item_text" placeholder="Exclude item" value="<?= e($editExclude['item_text'] ?? '') ?>" required>
                            <input type="number" name="sort_order" placeholder="Sort Order" value="<?= e($editExclude['sort_order'] ?? 0) ?>">
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"><?= $editExclude ? 'Update Exclude' : 'Add Exclude' ?></button>
                                <?php if ($editExclude): ?>
                                    <a class="btn btn-soft" href="<?= BASE_URL ?>/admin/packages.php?edit=<?= (int)$editData['id'] ?>">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="info-grid admin-packages-grid-gap">
                <div class="table-card">
                    <h3>Itinerary Days</h3>
                    <?php if (!empty($itineraryRows)): ?>
                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Title</th>
                                        <th>Stay</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itineraryRows as $row): ?>
                                        <tr>
                                            <td><?= (int)$row['day_number'] ?></td>
                                            <td><?= e($row['title']) ?></td>
                                            <td><?= e($row['overnight_stay'] ?? '-') ?></td>
                                            <td>
                                                <div class="form-actions">
                                                    <a class="btn btn-small btn-soft" href="?edit=<?= (int)$editData['id'] ?>&edit_itinerary=<?= (int)$row['id'] ?>">Edit</a>
                                                    <a class="btn btn-small btn-danger" href="?edit=<?= (int)$editData['id'] ?>&delete_itinerary=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this itinerary day?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" style="background:#fafcff;color:#64748b;"><?= nl2br(e($row['description'] ?? '-')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No itinerary days added yet.</p>
                    <?php endif; ?>
                </div>

                <div class="table-card">
                    <h3>Includes</h3>
                    <?php if (!empty($includeRows)): ?>
                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Sort</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($includeRows as $row): ?>
                                        <tr>
                                            <td><?= e($row['item_text']) ?></td>
                                            <td><?= (int)$row['sort_order'] ?></td>
                                            <td>
                                                <div class="form-actions">
                                                    <a class="btn btn-small btn-soft" href="?edit=<?= (int)$editData['id'] ?>&edit_include=<?= (int)$row['id'] ?>">Edit</a>
                                                    <a class="btn btn-small btn-danger" href="?edit=<?= (int)$editData['id'] ?>&delete_include=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this include item?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No includes added yet.</p>
                    <?php endif; ?>
                </div>

                <div class="table-card">
                    <h3>Excludes</h3>
                    <?php if (!empty($excludeRows)): ?>
                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Sort</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($excludeRows as $row): ?>
                                        <tr>
                                            <td><?= e($row['item_text']) ?></td>
                                            <td><?= (int)$row['sort_order'] ?></td>
                                            <td>
                                                <div class="form-actions">
                                                    <a class="btn btn-small btn-soft" href="?edit=<?= (int)$editData['id'] ?>&edit_exclude=<?= (int)$row['id'] ?>">Edit</a>
                                                    <a class="btn btn-small btn-danger" href="?edit=<?= (int)$editData['id'] ?>&delete_exclude=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this exclude item?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No excludes added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <h3>All Packages</h3>
            <?php if (!empty($packages)): ?>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Package</th>
                                <th>Destination</th>
                                <th>Price</th>
                                <th>Featured</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                                <tr>
                                    <td><?= (int)$package['id'] ?></td>
                                    <td><?= e($package['package_name']) ?></td>
                                    <td><?= e($package['destination_name']) ?></td>
                                    <td><?= e(formatPrice($package['price'])) ?></td>
                                    <td><?= (int)$package['is_featured'] === 1 ? 'Yes' : 'No' ?></td>
                                    <td>
                                        <?php if (!empty($package['featured_image'])): ?>
                                            <img src="<?= BASE_URL . '/' . e($package['featured_image']) ?>" alt="" style="width:90px;height:60px;object-fit:cover;border-radius:10px;">
                                        <?php else: ?>
                                            <span class="muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="form-actions">
                                            <a class="btn btn-small btn-soft" href="?edit=<?= (int)$package['id'] ?>">Edit</a>
                                            <a class="btn btn-small btn-danger" href="?delete=<?= (int)$package['id'] ?>" onclick="return confirm('Delete this package?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No packages found.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    const searchInput = document.getElementById('destination_search');
    const hiddenInput = document.getElementById('destination_id');
    const dataList = document.getElementById('destination_list');

    if (!searchInput || !hiddenInput || !dataList) return;

    function syncDestinationId() {
        const typed = searchInput.value.trim().toLowerCase();
        let foundId = '';

        Array.from(dataList.options).forEach(option => {
            if (option.value.trim().toLowerCase() === typed) {
                foundId = option.dataset.id || '';
            }
        });

        hiddenInput.value = foundId;
    }

    searchInput.addEventListener('input', syncDestinationId);
    searchInput.addEventListener('change', syncDestinationId);

    const form = searchInput.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            syncDestinationId();
            if (!hiddenInput.value) {
                e.preventDefault();
                alert('Please select a valid destination from the suggestions.');
                searchInput.focus();
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>