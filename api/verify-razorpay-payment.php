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

$bookingId = (int)($_POST['booking_id'] ?? 0);
$razorpayPaymentId = trim($_POST['razorpay_payment_id'] ?? '');
$razorpayOrderId = trim($_POST['razorpay_order_id'] ?? '');
$razorpaySignature = trim($_POST['razorpay_signature'] ?? '');

if ($bookingId <= 0 || $razorpayPaymentId === '' || $razorpayOrderId === '' || $razorpaySignature === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Missing payment verification fields.'
    ]);
    exit;
}

try {
    $paymentStmt = $pdo->prepare("
        SELECT p.id, p.razorpay_order_id, p.amount, b.package_id
        FROM payments p
        INNER JOIN bookings b ON b.id = p.booking_id
        WHERE p.booking_id = ? AND p.user_id = ?
        LIMIT 1
    ");
    $paymentStmt->execute([$bookingId, $userId]);
    $payment = $paymentStmt->fetch();

    if (!$payment) {
        echo json_encode([
            'success' => false,
            'message' => 'Payment record not found.'
        ]);
        exit;
    }

    $serverOrderId = $payment['razorpay_order_id'];

    $generatedSignature = hash_hmac(
        'sha256',
        $serverOrderId . '|' . $razorpayPaymentId,
        RAZORPAY_KEY_SECRET
    );

    if (!hash_equals($generatedSignature, $razorpaySignature)) {
        echo json_encode([
            'success' => false,
            'message' => 'Signature verification failed.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $updatePayment = $pdo->prepare("
        UPDATE payments
        SET razorpay_payment_id = ?, razorpay_signature = ?, payment_status = 'success', paid_at = NOW(), updated_at = NOW()
        WHERE booking_id = ? AND user_id = ?
    ");
    $updatePayment->execute([
        $razorpayPaymentId,
        $razorpaySignature,
        $bookingId,
        $userId
    ]);

    $updateBooking = $pdo->prepare("
        UPDATE bookings
        SET payment_status = 'paid', booking_status = 'confirmed', updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $updateBooking->execute([$bookingId, $userId]);

    $pdo->commit();

    $mailSent = false;
    $mailError = null;

    try {
        require_once __DIR__ . '/../includes/mailer.php';

        if (!function_exists('sendBookingConfirmationEmail')) {
            throw new Exception('sendBookingConfirmationEmail function not found in mailer.php');
        }

        $detailsStmt = $pdo->prepare("
            SELECT
                b.id,
                b.booking_reference,
                b.customer_name,
                b.customer_email,
                b.travel_date,
                b.number_of_passengers,
                b.total_amount,
                p.package_name
            FROM bookings b
            INNER JOIN packages p ON p.id = b.package_id
            WHERE b.id = ? AND b.user_id = ?
            LIMIT 1
        ");
        $detailsStmt->execute([$bookingId, $userId]);
        $details = $detailsStmt->fetch();

        if (!$details) {
            throw new Exception('Booking details not found for email.');
        }

        $mailSent = sendBookingConfirmationEmail([
            'booking_reference' => $details['booking_reference'],
            'customer_name' => $details['customer_name'],
            'customer_email' => $details['customer_email'],
            'package_name' => $details['package_name'],
            'travel_date' => $details['travel_date'],
            'number_of_passengers' => $details['number_of_passengers'],
            'total_amount' => $details['total_amount'],
            'razorpay_payment_id' => $razorpayPaymentId
        ], $mailError);
    } catch (Throwable $mailEx) {
        $mailSent = false;
        $mailError = $mailEx->getMessage();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully.',
        'booking_id' => $bookingId,
        'package_id' => (int)$payment['package_id'],
        'mail_sent' => $mailSent,
        'mail_error' => $mailError
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Server error while verifying payment.',
        'debug' => $e->getMessage()
    ]);
}