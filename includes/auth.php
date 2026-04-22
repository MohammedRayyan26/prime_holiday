<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirectToLoginWithReturn();
    }
}

function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        redirectTo(BASE_URL . '/login.php');
    }
}