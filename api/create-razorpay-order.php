<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

requireLogin();

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$packageId = (int)($_POST['package_id'] ?? 0);
$travelDate = trim($_POST['travel_date'] ?? '');
$numberOfPassengers = (int)($_POST['number_of_passengers'] ?? 1);
$customerName = trim($_POST['customer_name'] ?? '');
$customerEmail = trim($_POST['customer_email'] ?? '');
$customerPhone = trim($_POST['customer_phone'] ?? '');
$pickupPoint = trim($_POST['pickup_point'] ?? '');
$specialRequest = trim($_POST['special_request'] ?? '');

if ($packageId <= 0 || $travelDate === '' || $customerName === '' || $customerEmail === '' || $customerPhone === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill all required booking fields.'
    ]);
    exit;
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address.'
    ]);
    exit;
}

if ($numberOfPassengers < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Passengers must be at least 1.'
    ]);
    exit;
}

if (!defined('RAZORPAY_KEY_ID') || !defined('RAZORPAY_KEY_SECRET')) {
    echo json_encode([
        'success' => false,
        'message' => 'Razorpay keys are missing in config.'
    ]);
    exit;
}

if (!function_exists('curl_init')) {
    echo json_encode([
        'success' => false,
        'message' => 'PHP cURL is not enabled. Enable cURL in XAMPP PHP.'
    ]);
    exit;
}

try {
    $packageStmt = $pdo->prepare("
        SELECT id, package_name, price, offer_price
        FROM packages
        WHERE id = ? AND is_active = 1
        LIMIT 1
    ");
    $packageStmt->execute([$packageId]);
    $package = $packageStmt->fetch();

    if (!$package) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid package selected.'
        ]);
        exit;
    }

    $priceToUse = !empty($package['offer_price']) ? (float)$package['offer_price'] : (float)$package['price'];
    $totalAmount = $priceToUse * $numberOfPassengers;
    $amountInPaise = (int) round($totalAmount * 100);

    if ($amountInPaise < 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Order amount must be at least ₹1.00.'
        ]);
        exit;
    }

    $bookingReference = 'PH' . date('YmdHis') . rand(100, 999);

    $payloadArray = [
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'receipt' => $bookingReference,
        'notes' => [
            'package_id' => (string)$packageId,
            'user_id' => (string)$userId,
            'booking_reference' => $bookingReference
        ]
    ];

    $payload = json_encode($payloadArray);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError) {
        echo json_encode([
            'success' => false,
            'message' => 'cURL error while creating Razorpay order.',
            'debug' => $curlError
        ]);
        exit;
    }

    $order = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $errorMessage = $order['error']['description'] ?? $order['error']['reason'] ?? $response;

        echo json_encode([
            'success' => false,
            'message' => 'Failed to create Razorpay order.',
            'debug' => $errorMessage,
            'http_code' => $httpCode
        ]);
        exit;
    }

    if (empty($order['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid order response from Razorpay.',
            'debug' => $response
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $bookingStmt = $pdo->prepare("
        INSERT INTO bookings (
            booking_reference,
            user_id,
            package_id,
            travel_date,
            number_of_passengers,
            customer_name,
            customer_email,
            customer_phone,
            pickup_point,
            special_request,
            package_price,
            total_amount,
            booking_status,
            payment_status,
            booked_at,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW(), NOW(), NOW())
    ");
    $bookingStmt->execute([
        $bookingReference,
        $userId,
        $packageId,
        $travelDate,
        $numberOfPassengers,
        $customerName,
        $customerEmail,
        $customerPhone,
        $pickupPoint !== '' ? $pickupPoint : null,
        $specialRequest !== '' ? $specialRequest : null,
        $priceToUse,
        $totalAmount
    ]);

    $bookingId = (int)$pdo->lastInsertId();

    $paymentStmt = $pdo->prepare("
        INSERT INTO payments (
            booking_id,
            user_id,
            payment_gateway,
            razorpay_order_id,
            amount,
            currency,
            payment_status,
            created_at,
            updated_at
        ) VALUES (?, ?, 'razorpay', ?, ?, 'INR', 'created', NOW(), NOW())
    ");
    $paymentStmt->execute([
        $bookingId,
        $userId,
        $order['id'],
        $totalAmount
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'key' => RAZORPAY_KEY_ID,
        'order_id' => $order['id'],
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'booking_id' => $bookingId,
        'booking_reference' => $bookingReference,
        'package_name' => $package['package_name'],
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Server error while creating booking/order.',
        'debug' => $e->getMessage()
    ]);
}