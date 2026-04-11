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