<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$destinations = [];
$search = trim($_GET['search'] ?? '');

try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                slug,
                short_description,
                full_description,
                best_places,
                top_activities,
                best_time_to_visit,
                food_to_try,
                local_transport,
                travel_tips,
                state_name,
                country_name,
                hero_image,
                is_trending
            FROM destinations
            WHERE is_active = 1
              AND (
                    name LIKE ?
                 OR state_name LIKE ?
                 OR country_name LIKE ?
                 OR short_description LIKE ?
                 OR full_description LIKE ?
                 OR best_places LIKE ?
                 OR top_activities LIKE ?
                 OR food_to_try LIKE ?
                 OR travel_tips LIKE ?
              )
            ORDER BY is_trending DESC, name ASC
        ");
        $like = '%' . $search . '%';
        $stmt->execute([$like, $like, $like, $like, $like, $like, $like, $like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT
                id,
                name,
                slug,
                short_description,
                full_description,
                best_places,
                top_activities,
                best_time_to_visit,
                food_to_try,
                local_transport,
                travel_tips,
                state_name,
                country_name,
                hero_image,
                is_trending
            FROM destinations
            WHERE is_active = 1
            ORDER BY is_trending DESC, name ASC
        ");
    }

    $destinations = $stmt->fetchAll();
} catch (Throwable $e) {
    $destinations = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section destinations-page-section">
    <div class="container">
        <div class="section-head destinations-page-head">
            <span class="badge">All Destinations</span>
            <h2>Discover all travel destinations</h2>
            <p>
                Browse all active destinations available in Prime Holiday and explore the places best suited for your next trip.
            </p>
        </div>

        <div class="destinations-page-topbar">
            <form method="get" action="<?= BASE_URL ?>/destinations_details.php" class="destinations-search-form">
                <div class="destinations-search-row">
                    <input
                        type="text"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Search Goa, Coorg, Kashmir, Kerala, Rajasthan"
                    >
                    <button type="submit" class="btn btn-primary">Search</button>

                    <?php if ($search !== ''): ?>
                        <a href="<?= BASE_URL ?>/destinations_details.php" class="btn btn-soft">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($search !== ''): ?>
            <div class="notice-success" style="margin-bottom:18px;">
                Showing destination results for: <strong><?= e($search) ?></strong>
            </div>
        <?php endif; ?>

        <div class="destinations-list-grid">
            <?php if (!empty($destinations)): ?>
                <?php foreach ($destinations as $destination): ?>
                    <div
                        class="card destination-list-card destination-card-link"
                        onclick="window.location.href='<?= BASE_URL ?>/destinations.php?slug=<?= urlencode($destination['slug']) ?>'"
                    >
                        <div class="card-image">
                            <img
                                src="<?= e(getImageUrl($destination['hero_image'])) ?>"
                                alt="<?= e($destination['name']) ?>"
                            >
                        </div>

                        <div class="card-body">
                            <div class="card-meta">
                                <span><?= e($destination['state_name'] ?: $destination['country_name']) ?></span>
                                <span><?= (int)$destination['is_trending'] === 1 ? 'Trending' : 'Destination' ?></span>
                            </div>

                            <h3><?= e($destination['name']) ?></h3>

                            <p>
                                <?= e($destination['short_description'] ?: 'Discover this beautiful destination with Prime Holiday.') ?>
                            </p>

                            <?php if (!empty($destination['best_time_to_visit'])): ?>
                                <p style="margin:0;color:#64748b;">
                                    <strong>Best Time:</strong> <?= e($destination['best_time_to_visit']) ?>
                                </p>
                            <?php endif; ?>

                            <div class="card-actions">
                                <a
                                    class="btn btn-small btn-primary"
                                    href="<?= BASE_URL ?>/index.php?search=<?= urlencode($destination['name']) ?>#packages"
                                    onclick="event.stopPropagation();"
                                >
                                    Search Packages
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="info-card">
                    <h3>No destinations found</h3>
                    <p>Please try a different search term.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>