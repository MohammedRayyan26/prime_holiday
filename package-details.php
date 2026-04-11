<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$packageId = (int)($_GET['id'] ?? 0);

$package = null;
$images = [];
$includes = [];
$excludes = [];
$itinerary = [];
$relatedPackages = [];

if ($packageId > 0) {
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            d.name AS destination_name,
            d.full_description AS destination_description,
            COALESCE(v.average_rating, 0) AS average_rating,
            COALESCE(v.total_reviews, 0) AS total_reviews
        FROM packages p
        INNER JOIN destinations d ON d.id = p.destination_id
        LEFT JOIN vw_package_rating_summary v ON v.package_id = p.id
        WHERE p.id = ? AND p.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch();

    if ($package) {
        $imgStmt = $pdo->prepare("
            SELECT image_path, alt_text
            FROM package_images
            WHERE package_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $imgStmt->execute([$packageId]);
        $images = $imgStmt->fetchAll();

        $incStmt = $pdo->prepare("
            SELECT item_text
            FROM package_includes
            WHERE package_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $incStmt->execute([$packageId]);
        $includes = $incStmt->fetchAll();

        $excStmt = $pdo->prepare("
            SELECT item_text
            FROM package_excludes
            WHERE package_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $excStmt->execute([$packageId]);
        $excludes = $excStmt->fetchAll();

        $itiStmt = $pdo->prepare("
            SELECT day_number, title, description, overnight_stay
            FROM package_itinerary_days
            WHERE package_id = ?
            ORDER BY day_number ASC
        ");
        $itiStmt->execute([$packageId]);
        $itinerary = $itiStmt->fetchAll();

        $relatedStmt = $pdo->prepare("
            SELECT
                p.id,
                p.package_name,
                p.price,
                p.offer_price,
                p.duration_days,
                p.duration_nights,
                p.featured_image,
                d.name AS destination_name
            FROM packages p
            INNER JOIN destinations d ON d.id = p.destination_id
            WHERE p.destination_id = ? AND p.id != ? AND p.is_active = 1
            ORDER BY p.is_featured DESC, p.id DESC
            LIMIT 3
        ");
        $relatedStmt->execute([$package['destination_id'], $packageId]);
        $relatedPackages = $relatedStmt->fetchAll();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <?php if (!$package): ?>
            <div class="info-card">
                <h2>Package not found</h2>
                <p>The selected package does not exist or is inactive.</p>
                <div class="form-actions">
                    <a href="<?= BASE_URL ?>/index.php#packages" class="btn btn-primary">Back to Packages</a>
                </div>
            </div>
        <?php else: ?>
            <div class="section-head">
                <span class="badge"><?= e($package['destination_name']) ?></span>
                <h2><?= e($package['package_name']) ?></h2>
                <p><?= e($package['short_description'] ?: 'Discover this package in detail and book your trip easily.') ?></p>
            </div>

            <div class="info-grid" style="margin-bottom:24px;">
                <div class="info-card" style="grid-column: span 2;">
                    <div class="card-image" style="height:360px;border-radius:18px;overflow:hidden;margin-bottom:16px;">
                        <img src="<?= e(getImageUrl($package['featured_image'])) ?>" alt="<?= e($package['package_name']) ?>">
                    </div>

                    <?php if (!empty($images)): ?>
                        <div class="card-grid" style="grid-template-columns:repeat(3,1fr);margin-top:16px;">
                            <?php foreach ($images as $image): ?>
                                <div class="card-image" style="height:160px;border-radius:18px;overflow:hidden;">
                                    <img src="<?= e(getImageUrl($image['image_path'])) ?>" alt="<?= e($image['alt_text'] ?: $package['package_name']) ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Quick Info</h3>
                    <p><strong>Destination:</strong> <?= e($package['destination_name']) ?></p>
                    <p><strong>Duration:</strong> <?= (int)$package['duration_days'] ?> Days / <?= (int)$package['duration_nights'] ?> Nights</p>
                    <p><strong>Departure From:</strong> <?= e($package['departure_from'] ?: '-') ?></p>
                    <p><strong>Bus Number:</strong> <?= e($package['bus_number'] ?: '-') ?></p>
                    <p><strong>Driver Name:</strong> <?= e($package['driver_name'] ?: '-') ?></p>
                    <p><strong>Seats Available:</strong> <?= (int)$package['seats_available'] ?></p>

                    <div class="rating-row" style="margin-top:12px;">
                        <span class="stars"><?= e(renderStars((float)$package['average_rating'])) ?></span>
                        <span><?= number_format((float)$package['average_rating'], 1) ?> (<?= (int)$package['total_reviews'] ?> reviews)</span>
                    </div>

                    <div class="price-row" style="margin-top:16px;">
                        <?php if (!empty($package['offer_price'])): ?>
                            <strong><?= e(formatPrice($package['offer_price'])) ?></strong>
                            <del><?= e(formatPrice($package['price'])) ?></del>
                        <?php else: ?>
                            <strong><?= e(formatPrice($package['price'])) ?></strong>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions" style="margin-top:16px;">
                        <a href="<?= BASE_URL ?>/booking.php?package_id=<?= (int)$package['id'] ?>" class="btn btn-primary">Book Now</a>
                        <a href="<?= BASE_URL ?>/index.php#packages" class="btn btn-soft">Back</a>
                    </div>
                </div>
            </div>

            <div class="info-grid" style="margin-bottom:24px;">
                <div class="info-card" style="grid-column: span 2;">
                    <h3>Package Description</h3>
                    <p><?= nl2br(e($package['description'] ?: 'No detailed description available yet.')) ?></p>

                    <?php if (!empty($package['recommendations'])): ?>
                        <h3 style="margin-top:24px;">Recommendations</h3>
                        <p><?= nl2br(e($package['recommendations'])) ?></p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Destination Overview</h3>
                    <p><?= nl2br(e($package['destination_description'] ?: 'No destination description available.')) ?></p>
                </div>
            </div>

            <div class="info-grid" style="margin-bottom:24px;">
                <div class="info-card">
                    <h3>Includes</h3>
                    <?php if (!empty($includes)): ?>
                        <ul style="margin:0;padding-left:18px;">
                            <?php foreach ($includes as $item): ?>
                                <li style="margin-bottom:8px;"><?= e($item['item_text']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No include items added yet.</p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Excludes</h3>
                    <?php if (!empty($excludes)): ?>
                        <ul style="margin:0;padding-left:18px;">
                            <?php foreach ($excludes as $item): ?>
                                <li style="margin-bottom:8px;"><?= e($item['item_text']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No exclude items added yet.</p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h3>Booking Help</h3>
                    <p>Once logged in, you can book this package by selecting your travel date, number of passengers, Aadhar no. and contact details.</p>
                    <div class="form-actions" style="margin-top:12px;">
                        <a href="<?= BASE_URL ?>/booking.php?package_id=<?= (int)$package['id'] ?>" class="btn btn-primary">Proceed to Booking</a>
                    </div>
                </div>
            </div>

            <div class="info-card" style="margin-bottom:24px;">
                <h3>Full Itinerary</h3>

                <?php if (!empty($itinerary)): ?>
                    <?php foreach ($itinerary as $day): ?>
                        <div style="border:1px solid #e5eaf2;border-radius:16px;padding:16px;margin-bottom:14px;background:#fff;">
                            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
                                <strong>Day <?= (int)$day['day_number'] ?> - <?= e($day['title']) ?></strong>
                                <span class="muted"><?= e($day['overnight_stay'] ?: 'No overnight stay listed') ?></span>
                            </div>
                            <p style="margin:0;"><?= nl2br(e($day['description'] ?: 'No description available.')) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No itinerary added yet.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($relatedPackages)): ?>
                <div class="section-head" style="margin-top:10px;">
                    <span class="badge">Related Packages</span>
                    <h2>More packages from <?= e($package['destination_name']) ?></h2>
                </div>

                <div class="card-grid">
                    <?php foreach ($relatedPackages as $related): ?>
                        <div class="card">
                            <div class="card-image">
                                <img src="<?= e(getImageUrl($related['featured_image'])) ?>" alt="<?= e($related['package_name']) ?>">
                            </div>
                            <div class="card-body">
                                <div class="card-meta">
                                    <span><?= e($related['destination_name']) ?></span>
                                    <span><?= (int)$related['duration_days'] ?>D / <?= (int)$related['duration_nights'] ?>N</span>
                                </div>

                                <h3><?= e($related['package_name']) ?></h3>

                                <div class="price-row">
                                    <?php if (!empty($related['offer_price'])): ?>
                                        <strong><?= e(formatPrice($related['offer_price'])) ?></strong>
                                        <del><?= e(formatPrice($related['price'])) ?></del>
                                    <?php else: ?>
                                        <strong><?= e(formatPrice($related['price'])) ?></strong>
                                    <?php endif; ?>
                                </div>

                                <div class="card-actions">
                                    <a class="btn btn-small btn-soft" href="<?= BASE_URL ?>/package-details.php?id=<?= (int)$related['id'] ?>">View Details</a>
                                    <a class="btn btn-small btn-primary" href="<?= BASE_URL ?>/booking.php?package_id=<?= (int)$related['id'] ?>">Book Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>