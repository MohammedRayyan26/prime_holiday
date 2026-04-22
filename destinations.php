<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$error = '';
$destination = null;
$packages = [];

if ($slug === '') {
    $error = 'Destination not found.';
} else {
    try {
        $destStmt = $pdo->prepare("
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
                gallery_image_1,
                gallery_image_2,
                gallery_image_3,
                map_embed_url,
                is_trending,
                is_active
            FROM destinations
            WHERE slug = ? AND is_active = 1
            LIMIT 1
        ");
        $destStmt->execute([$slug]);
        $destination = $destStmt->fetch();

        if (!$destination) {
            $error = 'Destination not found.';
        } else {
            $pkgStmt = $pdo->prepare("
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
                    COALESCE(v.average_rating, 0) AS average_rating,
                    COALESCE(v.total_reviews, 0) AS total_reviews
                FROM packages p
                LEFT JOIN vw_package_rating_summary v ON v.package_id = p.id
                WHERE p.destination_id = ? AND p.is_active = 1
                ORDER BY p.is_featured DESC, p.id DESC
            ");
            $pkgStmt->execute([(int)$destination['id']]);
            $packages = $pkgStmt->fetchAll();
        }
    } catch (Throwable $e) {
        $error = 'Unable to load destination details.';
    }
}

function splitLines(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }

    $items = preg_split('/\r\n|\r|\n/', $text);
    $items = array_map('trim', $items);
    return array_values(array_filter($items, static fn($item) => $item !== ''));
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <?php if ($error !== ''): ?>
            <div class="info-card">
                <h2>Destination Page</h2>
                <p style="color:#991b1b;"><?= e($error) ?></p>
                <div class="card-actions" style="margin-top:16px;">
                    <a class="btn btn-primary" href="<?= BASE_URL ?>/index.php#destinations">Back to Destinations</a>
                </div>
            </div>
        <?php else: ?>
            <div class="info-card" style="overflow:hidden;padding:0;">
                <div style="position:relative;">
                    <img
                        src="<?= e(getImageUrl($destination['hero_image'])) ?>"
                        alt="<?= e($destination['name']) ?>"
                        style="width:100%;height:360px;object-fit:cover;display:block;"
                    >
                    <div style="position:absolute;left:0;right:0;bottom:0;padding:28px;background:linear-gradient(to top, rgba(0,0,0,.65), rgba(0,0,0,.15));color:#fff;">
                        <span class="badge" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.25);">
                            Destination
                        </span>
                        <h1 style="margin:12px 0 8px;"><?= e($destination['name']) ?></h1>
                        <p style="margin:0;font-size:15px;opacity:.95;">
                            <?= e(($destination['state_name'] ?: '') . (($destination['state_name'] && $destination['country_name']) ? ', ' : '') . ($destination['country_name'] ?: '')) ?>
                        </p>
                    </div>
                </div>

                <div style="padding:24px;">
                    <div class="info-grid" style="margin-bottom:24px;">
                        <div class="info-card" style="grid-column: span 2;">
                            <h3>About <?= e($destination['name']) ?></h3>
                            <p style="margin-bottom:12px;">
                                <?= e($destination['short_description'] ?: 'Explore this destination with Prime Holiday.') ?>
                            </p>
                            <p style="margin:0;white-space:pre-line;">
                                <?= e($destination['full_description'] ?: 'Detailed destination information will be added soon.') ?>
                            </p>
                        </div>

                        <div class="info-card">
                            <h3>Quick Info</h3>
                            <p><strong>State:</strong> <?= e($destination['state_name'] ?: '-') ?></p>
                            <p><strong>Country:</strong> <?= e($destination['country_name'] ?: '-') ?></p>
                            <p><strong>Trending:</strong> <?= (int)$destination['is_trending'] === 1 ? 'Yes' : 'No' ?></p>
                            <p><strong>Best Time:</strong> <?= e($destination['best_time_to_visit'] ?: '-') ?></p>
                        </div>
                    </div>

                    <?php $bestPlaces = splitLines($destination['best_places'] ?? ''); ?>
                    <?php $topActivities = splitLines($destination['top_activities'] ?? ''); ?>
                    <?php $foodToTry = splitLines($destination['food_to_try'] ?? ''); ?>

                    <?php
                    $accordionItems = [
                        [
                            'title' => 'Best Places to Visit in ' . $destination['name'],
                            'type' => 'list',
                            'content' => $bestPlaces,
                        ],
                        [
                            'title' => 'Top Things to Do',
                            'type' => 'list',
                            'content' => $topActivities,
                        ],
                        [
                            'title' => 'Food to Try',
                            'type' => 'list',
                            'content' => $foodToTry,
                        ],
                        [
                            'title' => 'Local Transport',
                            'type' => 'text',
                            'content' => trim((string)($destination['local_transport'] ?? '')),
                        ],
                        [
                            'title' => 'Travel Tips',
                            'type' => 'text',
                            'content' => trim((string)($destination['travel_tips'] ?? '')),
                        ],
                    ];

                    $accordionItems = array_values(array_filter($accordionItems, function ($item) {
                        if ($item['type'] === 'list') {
                            return !empty($item['content']);
                        }
                        return $item['content'] !== '';
                    }));
                    ?>

                    <?php if (!empty($accordionItems)): ?>
    <div style="margin-top:24px;">
        <h3 style="margin:0 0 16px;">Destination Guide</h3>

        <div class="destination-accordion">
            <?php foreach ($accordionItems as $index => $item): ?>
                <div class="destination-accordion-item">
                    <button
                        type="button"
                        class="destination-accordion-toggle"
                        data-accordion-target="destination-accordion-panel-<?= $index ?>"
                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                    >
                        <span><?= e($item['title']) ?></span>
                        <span class="destination-accordion-icon"><?= $index === 0 ? '−' : '+' ?></span>
                    </button>

                    <div
                        id="destination-accordion-panel-<?= $index ?>"
                        class="destination-accordion-panel<?= $index === 0 ? ' is-open' : '' ?>"
                    >
                        <div class="destination-accordion-panel-inner">
                            <?php if ($item['type'] === 'list'): ?>
                                <ul style="margin:0;padding-left:18px;">
                                    <?php foreach ($item['content'] as $line): ?>
                                        <li style="margin-bottom:8px;"><?= e($line) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p style="margin:0;white-space:pre-line;"><?= e($item['content']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

                    <?php
                    $gallery = array_values(array_filter([
                        $destination['gallery_image_1'] ?? '',
                        $destination['gallery_image_2'] ?? '',
                        $destination['gallery_image_3'] ?? '',
                    ]));
                    ?>

                    <?php if (!empty($gallery)): ?>
                        <div style="margin-top:24px;">
                            <h3 style="margin-bottom:14px;">Gallery</h3>
                            <div class="card-grid">
                                <?php foreach ($gallery as $img): ?>
                                    <div class="card">
                                        <div class="card-image">
                                            <img src="<?= e(getImageUrl($img)) ?>" alt="<?= e($destination['name']) ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($destination['map_embed_url'])): ?>
                        <div style="margin-top:24px;">
                            <h3 style="margin-bottom:14px;">Location Map</h3>
                            <div style="border-radius:18px;overflow:hidden;border:1px solid #dbe4f0;background:#fff;">
                                <iframe
                                    src="<?= e($destination['map_embed_url']) ?>"
                                    width="100%"
                                    height="380"
                                    style="border:0;display:block;"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <section class="section" id="destination-packages" style="padding-bottom:0;">
                <div class="section-head">
                    <span class="badge">Packages</span>
                    <h2><?= e($destination['name']) ?> Packages</h2>
                    <p>Browse all active packages available for this destination.</p>
                </div>

                <div class="card-grid">
                    <?php if (!empty($packages)): ?>
                        <?php foreach ($packages as $package): ?>
                            <div
                                class="card package-card-link"
                                onclick="window.location.href='<?= BASE_URL ?>/package-details.php?id=<?= (int)$package['id'] ?>'"
                                style="cursor:pointer;"
                            >
                                <div class="card-image">
                                    <img src="<?= e(getImageUrl($package['featured_image'])) ?>" alt="<?= e($package['package_name']) ?>">
                                </div>

                                <div class="card-body">
                                    <div class="card-meta">
                                        <span><?= e($destination['name']) ?></span>
                                        <span><?= (int)$package['duration_days'] ?>D / <?= (int)$package['duration_nights'] ?>N</span>
                                    </div>

                                    <h3><?= e($package['package_name']) ?></h3>
                                    <p><?= e($package['short_description'] ?: 'Enjoy a curated travel experience with Prime Holiday.') ?></p>

                                    <div class="rating-row">
                                        <span class="stars"><?= e(renderStars((float)$package['average_rating'])) ?></span>
                                        <span><?= number_format((float)$package['average_rating'], 1) ?> (<?= (int)$package['total_reviews'] ?> reviews)</span>
                                    </div>

                                    <div class="price-row">
                                        <?php if (!empty($package['offer_price'])): ?>
                                            <strong><?= e(formatPrice($package['offer_price'])) ?></strong>
                                            <del><?= e(formatPrice($package['price'])) ?></del>
                                        <?php else: ?>
                                            <strong><?= e(formatPrice($package['price'])) ?></strong>
                                        <?php endif; ?>
                                    </div>

                                    <p style="margin:10px 0 0;color:#64748b;">
                                        <strong>Departure:</strong> <?= e($package['departure_from'] ?: '-') ?>
                                    </p>

                                    <div class="card-actions" style="margin-top:14px;">
                                        <a
                                            class="btn btn-small btn-primary"
                                            href="<?= BASE_URL ?>/booking.php?package_id=<?= (int)$package['id'] ?>"
                                            onclick="event.stopPropagation();"
                                        >
                                            Book Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="info-card">
                            <h3>No packages available</h3>
                            <p>There are no active packages for this destination right now.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<style>
.destination-accordion {
    display: grid;
    gap: 10px;
}

.destination-accordion-item {
    border-bottom: 1px solid #dbe4f0;
    background: transparent;
}

.destination-accordion-toggle {
    width: 100%;
    border: none;
    background: transparent;
    padding: 16px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    cursor: pointer;
    text-align: left;
    font-size: 16px;
    font-weight: 800;
    color: #162033;
}

.destination-accordion-toggle:hover {
    color: #0f6cbd;
}

.destination-accordion-icon {
    font-size: 22px;
    line-height: 1;
    color: #0f6cbd;
    flex: 0 0 auto;
}

.destination-accordion-panel {
    display: none;
    padding: 0 0 16px;
}

.destination-accordion-panel.is-open {
    display: block;
}

.destination-accordion-panel-inner {
    padding: 0;
    color: #5f6d83;
}
</style>

<script>
(function () {
    const toggles = document.querySelectorAll('.destination-accordion-toggle');

    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-accordion-target');
            const panel = document.getElementById(targetId);
            if (!panel) return;

            const isOpen = panel.classList.contains('is-open');
            const icon = this.querySelector('.destination-accordion-icon');

            if (isOpen) {
                panel.classList.remove('is-open');
                this.setAttribute('aria-expanded', 'false');
                if (icon) icon.textContent = '+';
            } else {
                panel.classList.add('is-open');
                this.setAttribute('aria-expanded', 'true');
                if (icon) icon.textContent = '−';
            }
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>