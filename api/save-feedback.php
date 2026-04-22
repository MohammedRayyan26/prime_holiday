<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

requireLogin();

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$packageId = (int)($_POST['package_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$reviewText = trim($_POST['review_text'] ?? '');

if ($bookingId <= 0 || $packageId <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please give a valid rating.']);
    exit;
}

try {
    $checkStmt = $pdo->prepare("
        SELECT id
        FROM bookings
        WHERE id = ? AND user_id = ? AND payment_status = 'paid'
        LIMIT 1
    ");
    $checkStmt->execute([$bookingId, $userId]);
    $booking = $checkStmt->fetch();

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Valid paid booking not found.']);
        exit;
    }

    $existingStmt = $pdo->prepare("
        SELECT id
        FROM reviews
        WHERE user_id = ? AND booking_id = ?
        LIMIT 1
    ");
    $existingStmt->execute([$userId, $bookingId]);
    $existing = $existingStmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE reviews
            SET rating = ?, review_text = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$rating, $reviewText !== '' ? $reviewText : null, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, package_id, booking_id, rating, review_text, is_approved, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$userId, $packageId, $bookingId, $rating, $reviewText !== '' ? $reviewText : null]);
    }

    echo json_encode(['success' => true, 'message' => 'Feedback saved successfully.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error while saving feedback.']);
}