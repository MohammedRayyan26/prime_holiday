<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$packages = [];
$search = trim($_GET['search'] ?? '');

try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT
                p.id,
                p.package_name,
                p.slug,
                p.short_description,
                p.description,
                p.price,
                p.offer_price,
                p.duration_days,
                p.duration_nights,
                p.departure_from,
                p.featured_image,
                p.is_featured,
                d.name AS destination_name,
                COALESCE(v.average_rating, 0) AS average_rating,
                COALESCE(v.total_reviews, 0) AS total_reviews
            FROM packages p
            INNER JOIN destinations d ON d.id = p.destination_id
            LEFT JOIN vw_package_rating_summary v ON v.package_id = p.id
            WHERE p.is_active = 1
              AND (
                    p.package_name LIKE ?
                 OR d.name LIKE ?
                 OR p.short_description LIKE ?
                 OR p.description LIKE ?
              )
            ORDER BY p.is_featured DESC, p.id DESC
        ");
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like, $like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT
                p.id,
                p.package_name,
                p.slug,
                p.short_description,
                p.description,
                p.price,
                p.offer_price,
                p.duration_days,
                p.duration_nights,
                p.departure_from,
                p.featured_image,
                p.is_featured,
                d.name AS destination_name,
                COALESCE(v.average_rating, 0) AS average_rating,
                COALESCE(v.total_reviews, 0) AS total_reviews
            FROM packages p
            INNER JOIN destinations d ON d.id = p.destination_id
            LEFT JOIN vw_package_rating_summary v ON v.package_id = p.id
            WHERE p.is_active = 1
            ORDER BY p.is_featured DESC, p.id DESC
        ");
    }

    $packages = $stmt->fetchAll();
} catch (Throwable $e) {
    $packages = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section destinations-page-section">
    <div class="container">
        <div class="section-head destinations-page-head">
            <span class="badge">All Packages</span>
            <h2>Explore all travel packages</h2>
            <p>
                Browse all active travel packages. Featured packages appear first.
            </p>
        </div>

        <div class="destinations-page-topbar">
            <form method="get" action="<?= BASE_URL ?>/packages.php" class="destinations-search-form">
                <div class="destinations-search-row">
                    <input
                        type="text"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Search package name or destination"
                    >
                    <button type="submit" class="btn btn-primary">Search</button>

                    <?php if ($search !== ''): ?>
                        <a href="<?= BASE_URL ?>/packages.php" class="btn btn-soft">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($search !== ''): ?>
            <div class="notice-success" style="margin-bottom:18px;">
                Showing package results for: <strong><?= e($search) ?></strong>
            </div>
        <?php endif; ?>

        <div class="destinations-list-grid">
            <?php if (!empty($packages)): ?>
                <?php foreach ($packages as $package): ?>
                    <div class="card destination-list-card">
                        <div class="card-image">
                            <img
                                src="<?= e(getImageUrl($package['featured_image'])) ?>"
                                alt="<?= e($package['package_name']) ?>"
                            >
                        </div>

                        <div class="card-body">
                            <div class="card-meta">
                                <span><?= e($package['destination_name']) ?></span>
                                <span>
                                    <?= (int)$package['duration_days'] ?>D / <?= (int)$package['duration_nights'] ?>N
                                </span>
                            </div>

                            <h3>
                                <?= e($package['package_name']) ?>
                                <?php if ((int)$package['is_featured'] === 1): ?>
                                    <span style="font-size:12px;background:#dbeafe;color:#1d4ed8;padding:4px 8px;border-radius:999px;margin-left:8px;vertical-align:middle;">
                                        Featured
                                    </span>
                                <?php endif; ?>
                            </h3>

                            <p>
                                <?= e($package['short_description'] ?: 'Enjoy a professionally curated travel experience.') ?>
                            </p>

                            <div class="rating-row">
                                <span class="stars"><?= e(renderStars((float)$package['average_rating'])) ?></span>
                                <span><?= number_format((float)$package['average_rating'], 1) ?> (<?= (int)$package['total_reviews'] ?> reviews)</span>
                            </div>

                            <div class="price-row" style="margin-top:12px;">
                                <?php if (!empty($package['offer_price'])): ?>
                                    <strong><?= e(formatPrice($package['offer_price'])) ?></strong>
                                    <del><?= e(formatPrice($package['price'])) ?></del>
                                <?php else: ?>
                                    <strong><?= e(formatPrice($package['price'])) ?></strong>
                                <?php endif; ?>
                            </div>

                            <div class="card-actions">
                                <a
                                    class="btn btn-small btn-soft"
                                    href="<?= BASE_URL ?>/package-details.php?id=<?= (int)$package['id'] ?>"
                                >
                                    View Details
                                </a>

                                <a
                                    class="btn btn-small btn-primary"
                                    href="<?= BASE_URL ?>/booking.php?package_id=<?= (int)$package['id'] ?>"
                                >
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="info-card">
                    <h3>No packages found</h3>
                    <p>Please try a different search term.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>