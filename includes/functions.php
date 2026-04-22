<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function normalizeRedirectTarget(?string $target, string $fallback = BASE_URL . '/profile.php'): string
{
    $target = trim((string)$target);

    if ($target === '') {
        return $fallback;
    }

    $target = str_replace(["\r", "\n"], '', $target);

    // block external redirects
    if (preg_match('#^https?://#i', $target)) {
        return $fallback;
    }

    if ($target[0] !== '/') {
        $target = '/' . ltrim($target, '/');
    }

    // allow only internal paths inside this project
    if (
        $target !== BASE_URL &&
        strpos($target, BASE_URL . '/') !== 0 &&
        strpos($target, BASE_URL . '?') !== 0
    ) {
        return $fallback;
    }

    return $target;
}

function currentRequestPath(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/');
    return normalizeRedirectTarget($uri, BASE_URL . '/');
}

function redirectToLoginWithReturn(): void
{
    $returnTo = currentRequestPath();
    redirectTo(BASE_URL . '/login.php?redirect=' . rawurlencode($returnTo));
}

function currentUserName(): string
{
    return $_SESSION['user_name'] ?? 'Guest';
}

function formatPrice($amount): string
{
    return '₹' . number_format((float)$amount, 2);
}

function getImageUrl(?string $path, string $fallback = 'https://via.placeholder.com/800x500?text=Prime+Holiday'): string
{
    if (!empty($path)) {
        return BASE_URL . '/' . ltrim($path, '/');
    }
    return $fallback;
}

function renderStars(float $rating): string
{
    $full = (int) round($rating);
    $full = max(0, min(5, $full));
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}