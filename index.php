<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$destinations = [];
$packages = [];
$testimonials = [];
$search = trim($_GET['search'] ?? '');

$contactError = '';
$contactSuccess = '';
$contactOld = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'contact_form') {
    $contactOld['full_name'] = trim($_POST['full_name'] ?? '');
    $contactOld['email'] = trim($_POST['email'] ?? '');
    $contactOld['phone'] = trim($_POST['phone'] ?? '');
    $contactOld['subject'] = trim($_POST['subject'] ?? '');
    $contactOld['message'] = trim($_POST['message'] ?? '');

    if (
        $contactOld['full_name'] === '' ||
        $contactOld['email'] === '' ||
        $contactOld['message'] === ''
    ) {
        $contactError = 'Please fill all required fields: Full Name, Email Address, and Message.';
    } elseif (!filter_var($contactOld['email'], FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Please enter a valid email address.';
    } elseif ($contactOld['phone'] !== '' && !preg_match('/^[0-9]{10}$/', $contactOld['phone'])) {
        $contactError = 'Phone number must be exactly 10 digits.';
    } else {
        try {
            $contactStmt = $pdo->prepare("
                INSERT INTO contact_messages (
                    full_name, email, phone, subject, message, is_replied, created_at
                ) VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            $contactStmt->execute([
                $contactOld['full_name'],
                $contactOld['email'],
                $contactOld['phone'] !== '' ? $contactOld['phone'] : null,
                $contactOld['subject'] !== '' ? $contactOld['subject'] : null,
                $contactOld['message'],
            ]);

            $contactSuccess = 'Thank you. Your message has been sent successfully.';
            $contactOld = [
                'full_name' => '',
                'email' => '',
                'phone' => '',
                'subject' => '',
                'message' => '',
            ];
        } catch (Throwable $e) {
            $contactError = 'Something went wrong while sending your message.';
        }
    }
}

try {
    $destStmt = $pdo->query("
        SELECT id, name, slug, short_description, full_description, state_name, country_name, hero_image
        FROM destinations
        WHERE is_active = 1
        ORDER BY is_trending DESC, id DESC
        LIMIT 6
    ");
    $destinations = $destStmt->fetchAll();
} catch (Throwable $e) {
    $destinations = [];
}

try {
    if ($search !== '') {
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
            LIMIT 12
        ");
        $like = '%' . $search . '%';
        $pkgStmt->execute([$like, $like, $like, $like]);
    } else {
        $pkgStmt = $pdo->query("
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
                d.name AS destination_name,
                COALESCE(v.average_rating, 0) AS average_rating,
                COALESCE(v.total_reviews, 0) AS total_reviews
            FROM packages p
            INNER JOIN destinations d ON d.id = p.destination_id
            LEFT JOIN vw_package_rating_summary v ON v.package_id = p.id
            WHERE p.is_active = 1
              AND p.is_featured = 1
            ORDER BY p.id DESC
            LIMIT 12
        ");
    }

    $packages = $pkgStmt->fetchAll();
} catch (Throwable $e) {
    $packages = [];
}

try {
    $testimonialStmt = $pdo->query("
        SELECT customer_name, customer_role, rating, testimonial_text
        FROM testimonials
        WHERE is_active = 1
        ORDER BY sort_order ASC, id DESC
        LIMIT 6
    ");
    $testimonials = $testimonialStmt->fetchAll();
} catch (Throwable $e) {
    $testimonials = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section" id="home">
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="badge">Prime Holiday</span>
            <h1>Travel beautifully. Book easily. Explore confidently.</h1>
            <p>
                Discover handpicked packages, trending destinations, family trips, beach escapes, and adventure tours
                with a cleaner and more professional holiday booking experience.
            </p>

            <form id="homeSearchForm" method="get" action="<?= BASE_URL ?>/index.php#packages" class="contact-form" style="margin-top:18px;">
                <div class="form-grid-2">
                    <div class="field-wrap">
                        <label class="field-label">Search Packages or Destinations</label>
                        <input type="text" id="homeSearchInput" name="search" value="<?= e($search) ?>" placeholder="Search package">
                        <div class="field-hint">Try package name or destination name.</div>
                    </div>
                </div>
            </form>

            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/index.php#packages" class="btn btn-primary">Explore Packages</a>
                <a href="#destinations" class="btn btn-soft">Trending Destinations</a>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <strong><?= count($packages) ?>+</strong>
                    <span>Curated Packages</span>
                </div>
                <div class="stat-card">
                    <strong><?= count($destinations) ?>+</strong>
                    <span>Top Destinations</span>
                </div>
                <div class="stat-card">
                    <strong>24/7</strong>
                    <span>Travel Support</span>
                </div>
            </div>
        </div>

        <div class="hero-image-card">
            <img src="uploads/heroimage.jpeg" alt="Prime Holiday">
        </div>
    </div>
</section>

<section class="section" id="about">
    <div class="container">
        <div class="section-head">
            <span class="badge">About Prime Holiday</span>
            <h2>A holiday platform designed for clarity and comfort</h2>
            <p>
                Prime Holiday helps users browse destinations, compare packages, and plan memorable trips with less confusion and better presentation.
                The idea is simple: show useful travel details beautifully, and make booking feel easy.
            </p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h3>Our Mission</h3>
                <p>To make travel booking simple, transparent, and enjoyable for every traveler.</p>
            </div>

            <div class="info-card">
                <h3>Our Vision</h3>
                <p>To become a trusted digital holiday platform for families, couples, friends, and adventure lovers.</p>
            </div>

            <div class="info-card">
                <h3>Why Choose Us</h3>
                <p>Clean UI, clear package details, helpful booking flow, and smart admin control over quality.</p>
            </div>
        </div>
    </div>
</section>

<section class="section alt-section" id="destinations">
    <div class="container">
        <div class="section-head destinations-head-row">
            <div>
                <span class="badge">Trending Destinations</span>
                <h2>Explore popular travel places</h2>
                <p>Beautiful places your users can discover quickly and confidently.</p>
            </div>

            <div class="destinations-head-action">
                <a href="<?= BASE_URL ?>/destinations_details.php" class="btn btn-soft">
                    More Destinations
                </a>
            </div>
        </div>

        <div class="card-grid">
    <?php if (!empty($destinations)): ?>
        <?php foreach ($destinations as $destination): ?>
            <a
                class="card destination-card-link"
                href="<?= BASE_URL ?>/destinations.php?slug=<?= urlencode($destination['slug']) ?>"
                style="text-decoration:none; color:inherit; display:block;"
            >
                <div class="card-image">
                    <img src="<?= e(getImageUrl($destination['hero_image'])) ?>" alt="<?= e($destination['name']) ?>">
                </div>
                <div class="card-body">
                    <div class="card-meta">
                        <span><?= e($destination['state_name'] ?: $destination['country_name']) ?></span>
                        <span>Destination</span>
                    </div>

                    <h3><?= e($destination['name']) ?></h3>
                    <p><?= e($destination['short_description'] ?? 'Discover this beautiful destination with Prime Holiday.') ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No destinations found.</p>
    <?php endif; ?>
</div>
    </div>
</section>

<section class="section" id="packages">
    <div class="container">
        <div class="section-head destinations-head-row">
            <div>
                <span class="badge">Travel Packages</span>
                <h2>Choose the right package for your next trip</h2>
                <p>
                    Browse our featured packages here. To view all active packages, open the full packages page.
                </p>
            </div>

            <div class="destinations-head-action">
                <a href="<?= BASE_URL ?>/packages.php" class="btn btn-soft">
                    More Packages
                </a>
            </div>
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
                    <span><?= e($package['destination_name']) ?></span>
                    <span><?= (int)$package['duration_days'] ?>D / <?= (int)$package['duration_nights'] ?>N</span>
                </div>

                <h3><?= e($package['package_name']) ?></h3>
                <p><?= e($package['short_description'] ?? 'Enjoy a professionally curated travel experience.') ?></p>

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

                <div class="card-actions">
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
                    <h3>No packages found</h3>
                    <p>Please try a different search term.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section alt-section" id="testimonials">
    <div class="container">
        <div class="section-head">
            <span class="badge">Testimonials</span>
            <h2>What travelers say about Prime Holiday</h2>
            <p>Positive experiences build trust and make the website feel more real.</p>
        </div>

        <div class="testimonial-grid">
            <?php if (!empty($testimonials)): ?>
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <div class="rating-row">
                            <span class="stars"><?= e(renderStars((float)$testimonial['rating'])) ?></span>
                            <span><?= (int)$testimonial['rating'] ?>/5</span>
                        </div>

                        <p>“<?= e($testimonial['testimonial_text']) ?>”</p>
                        <h4><?= e($testimonial['customer_name']) ?></h4>
                        <span class="muted"><?= e($testimonial['customer_role'] ?: 'Traveler') ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No testimonials found.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section" id="contact">
    <div class="container">
        <div class="section-head">
            <span class="badge">Contact Us</span>
            <h2>Let’s help you plan your next holiday</h2>
            <p>Reach out to Prime Holiday for your travel queries and package support.</p>
        </div>

        <div class="contact-box">
            <div class="info-card contact-info-card">
                <h3>Get in Touch</h3>
                <p><strong>Email:</strong> hello@primeholiday.com</p>
                <p><strong>Phone:</strong> +91 98765 43210</p>
                <p><strong>Office:</strong> Bangalore, India</p>
                <p><strong>Support:</strong> Monday to Saturday, 9 AM to 7 PM</p>

                <div class="contact-map-wrap">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3888.286837189749!2d77.58383137413071!3d12.953488487360172!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bae15c2bd709005%3A0x72246a69e152cb34!2sAl-Ameen%20Institute%20of%20Information%20Sciences!5e0!3m2!1sen!2sin!4v1775407934639!5m2!1sen!2sin"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <div class="info-card">
                <h3>Quick Contact</h3>

                <?php if ($contactError !== ''): ?>
                    <div class="notice-error" style="margin-bottom:14px;">
                        <?= e($contactError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($contactSuccess !== ''): ?>
                    <div class="notice-success" style="margin-bottom:14px;">
                        <?= e($contactSuccess) ?>
                    </div>
                <?php endif; ?>

                <form class="contact-form" id="contactForm" action="<?= BASE_URL ?>/index.php#contact" method="post" novalidate>
    <input type="hidden" name="form_type" value="contact_form">

    <div class="field-wrap">
        <label class="field-label">Full Name <span style="color:red;">*</span></label>
        <input
            type="text"
            name="full_name"
            placeholder="Enter your name"
            value="<?= e($contactOld['full_name']) ?>"
            required
        >
    </div>

    <div class="field-wrap">
        <label class="field-label">Email Address <span style="color:red;">*</span></label>
        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            value="<?= e($contactOld['email']) ?>"
            required
        >
    </div>

    <div class="field-wrap">
        <label class="field-label">Phone Number</label>
        <input
            type="text"
            name="phone"
            placeholder="Enter your phone number"
            maxlength="10"
            pattern="[0-9]{10}"
            inputmode="numeric"
            value="<?= e($contactOld['phone']) ?>"
        >
        <div class="field-hint">Optional. If entered, it must be 10 digits.</div>
    </div>

    <div class="field-wrap">
        <label class="field-label">Subject</label>
        <input
            type="text"
            name="subject"
            placeholder="Enter subject"
            value="<?= e($contactOld['subject']) ?>"
        >
        <div class="field-hint">Optional.</div>
    </div>

    <div class="field-wrap">
        <label class="field-label">Message <span style="color:red;">*</span></label>
        <textarea
            name="message"
            placeholder="Write your message"
            required
        ><?= e($contactOld['message']) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Send Message</button>
    </div>
</form>
            </div>
        </div>
    </div>
</section>
<script>
(function () {
    const baseUrl = '<?= BASE_URL ?>/index.php';
    const url = new URL(window.location.href);
    const hasSearch = url.searchParams.has('search') && url.searchParams.get('search').trim() !== '';
    const isReload =
        performance &&
        performance.getEntriesByType &&
        performance.getEntriesByType('navigation')[0] &&
        performance.getEntriesByType('navigation')[0].type === 'reload';

    if (hasSearch && isReload) {
        window.location.replace(baseUrl);
        return;
    }

    const searchForm = document.getElementById('homeSearchForm');
    const searchInput = document.getElementById('homeSearchInput');

    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function () {
            const value = searchInput.value.trim();
            if (value === '') {
                window.location.href = baseUrl;
                return false;
            }
        });
    }
})();

(function () {
    const contactForm = document.getElementById('contactForm');

    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            const fullName = contactForm.querySelector('[name="full_name"]');
            const email = contactForm.querySelector('[name="email"]');
            const phone = contactForm.querySelector('[name="phone"]');
            const message = contactForm.querySelector('[name="message"]');

            const fullNameValue = fullName.value.trim();
            const emailValue = email.value.trim();
            const phoneValue = phone.value.trim();
            const messageValue = message.value.trim();

            if (!fullNameValue || !emailValue || !messageValue) {
                e.preventDefault();
                alert('Please fill all required fields: Full Name, Email Address, and Message.');
                return;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailValue)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }

            if (phoneValue !== '' && !/^[0-9]{10}$/.test(phoneValue)) {
                e.preventDefault();
                alert('Phone number must be exactly 10 digits.');
                return;
            }
        });
    }
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>