<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$currentFile = basename($_SERVER['PHP_SELF']);

$headerProfilePhoto = '';
$headerInitial = strtoupper(substr(currentUserName(), 0, 1));

if (isLoggedIn()) {
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("
                SELECT up.profile_photo, u.full_name
                FROM users u
                LEFT JOIN user_profiles up ON up.user_id = u.id
                WHERE u.id = ?
                LIMIT 1
            ");
            $stmt->execute([(int)($_SESSION['user_id'] ?? 0)]);
            $headerUser = $stmt->fetch();

            if ($headerUser) {
                $headerProfilePhoto = trim((string)($headerUser['profile_photo'] ?? ''));
                $headerName = trim((string)($headerUser['full_name'] ?? ''));
                if ($headerName !== '') {
                    $headerInitial = strtoupper(substr($headerName, 0, 1));
                }
            }
        }
    } catch (Throwable $e) {
        $headerProfilePhoto = '';
    }
}

function navActive(string $needle, string $currentFile): string
{
    return $currentFile === $needle ? 'is-active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/style.css">
</head>
<body>
<header class="site-header site-header--travel">
        <div class="container">
        <div class="travel-nav-shell">
            <a href="<?= BASE_URL ?>/index.php" class="travel-brand travel-brand--logo-only" aria-label="<?= e(APP_NAME) ?> home">
    <div class="travel-brand__logo">
        <img src="<?= BASE_URL ?>/uploads/web%20LOGO.png" alt="<?= e(APP_NAME) ?> Logo">
    </div>
</a>
            <button class="travel-menu-toggle" type="button" aria-label="Toggle menu" onclick="document.body.classList.toggle('travel-menu-open')">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="travel-nav-panel">
                <nav class="travel-nav" aria-label="Main navigation">
                    <a class="<?= navActive('index.php', $currentFile) ?>" href="<?= BASE_URL ?>/index.php#home">Home</a>
                    <a href="<?= BASE_URL ?>/index.php#about">Why Us</a>
                    <a href="<?= BASE_URL ?>/index.php#destinations">Destinations</a>
                    <a href="<?= BASE_URL ?>/index.php#packages">Tour Packages</a>
                    <a href="<?= BASE_URL ?>/index.php#testimonials">Reviews</a>
                    <a href="<?= BASE_URL ?>/index.php#contact">Contact</a>
                </nav>

                <div class="travel-nav-actions">
                    <?php if (isLoggedIn()): ?>
                        <a class="travel-account-pill" href="<?= BASE_URL ?>/profile.php" title="<?= e(currentUserName()) ?>">
                            <span class="travel-account-pill__avatar">
                                <?php if ($headerProfilePhoto !== ''): ?>
                                    <img src="<?= e(getImageUrl($headerProfilePhoto)) ?>" alt="<?= e(currentUserName()) ?>">
                                <?php else: ?>
                                    <span class="travel-account-pill__initial"><?= e($headerInitial) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="travel-account-pill__meta">
                                <span class="travel-account-pill__label">Account</span>
                                <span class="travel-account-pill__name"><?= e(currentUserName()) ?></span>
                            </span>
                        </a>
                    <?php else: ?>
                        <a class="travel-link-btn" href="<?= BASE_URL ?>/login.php">Login</a>
                        <a class="travel-cta-btn" href="<?= BASE_URL ?>/signup.php">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<?php
$globalToastMessage = '';
$globalToastType = '';

if (!empty($success)) {
    $globalToastMessage = $success;
    $globalToastType = 'success';
} elseif (!empty($error)) {
    $globalToastMessage = $error;
    $globalToastType = 'error';
}
?>

<div
    id="adminToast"
    class="admin-toast <?= $globalToastType !== '' ? 'show toast-' . e($globalToastType) : '' ?>"
>
    <span id="adminToastText"><?= e($globalToastMessage) ?></span>
</div>

<script>
(function () {
    const toast = document.getElementById('adminToast');
    if (!toast || !toast.classList.contains('show')) return;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
})();
</script>
<main>