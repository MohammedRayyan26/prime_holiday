<?php
require_once __DIR__ . '/../config/config.php';

function loadPhpMailer(?string &$errorMessage = null): bool
{
    $errorMessage = null;

    $exceptionFile = __DIR__ . '/../phpmailer/src/Exception.php';
    $phpMailerFile = __DIR__ . '/../phpmailer/src/PHPMailer.php';
    $smtpFile = __DIR__ . '/../phpmailer/src/SMTP.php';

    if (!file_exists($exceptionFile) || !file_exists($phpMailerFile) || !file_exists($smtpFile)) {
        $errorMessage = 'PHPMailer files not found in /phpmailer/src/';
        return false;
    }

    require_once $exceptionFile;
    require_once $phpMailerFile;
    require_once $smtpFile;

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $errorMessage = 'PHPMailer class could not be loaded.';
        return false;
    }

    return true;
}

function createMailer(?string &$errorMessage = null)
{
    $errorMessage = null;

    if (!loadPhpMailer($errorMessage)) {
        return null;
    }

    $mailClass = 'PHPMailer\\PHPMailer\\PHPMailer';
    $mail = new $mailClass(true);

    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = trim((string) MAIL_USERNAME);
    $mail->Password = trim((string) MAIL_PASSWORD);

    $port = defined('MAIL_PORT') ? (int) MAIL_PORT : 587;
    $mail->Port = $port;

    if ($port === 465) {
        $mail->SMTPSecure = 'ssl';
    } else {
        $mail->SMTPSecure = 'tls';
    }

    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;

    // Uncomment only for debugging
    // $mail->SMTPDebug = 2;
    // $mail->Debugoutput = 'html';

    $mail->setFrom(trim((string) MAIL_FROM_ADDRESS), (string) MAIL_FROM_NAME);
    $mail->isHTML(true);

    return $mail;
}

function sendGeneralEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string &$errorMessage = null,
    string $altBody = ''
): bool {
    $errorMessage = null;

    try {
        $mail = createMailer($errorMessage);
        if (!$mail) {
            return false;
        }

        $mail->addAddress(trim($toEmail), $toName);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        $errorMessage = $e->getMessage();
        return false;
    }
}

function sendCustomEmail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string &$errorMessage = null,
    string $altBody = ''
): bool {
    return sendGeneralEmail($toEmail, $toName, $subject, $htmlBody, $errorMessage, $altBody);
}

function sendOtpLoginEmail(string $toEmail, string $toName, string $otpCode, ?string &$errorMessage = null): bool
{
    $errorMessage = null;

    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');

    $subject = 'Your Prime Holiday Login OTP';

    $htmlBody = '
    <div style="font-family:Arial,sans-serif;background:#f6f8fc;padding:24px;">
        <div style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5eaf2;">
            <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;padding:24px;">
                <h2 style="margin:0;">Prime Holiday</h2>
                <p style="margin:8px 0 0;">Login verification code</p>
            </div>
            <div style="padding:24px;color:#172033;">
                <p>Hi <strong>' . $safeName . '</strong>,</p>
                <p>Use the OTP below to login to your Prime Holiday account:</p>

                <div style="margin:22px 0;padding:18px;text-align:center;background:#f8fbff;border:1px solid #dbe4f0;border-radius:14px;">
                    <div style="font-size:32px;font-weight:800;letter-spacing:8px;color:#2563eb;">' . $safeOtp . '</div>
                </div>

                <p>This OTP is valid for <strong>10 minutes</strong>.</p>
                <p>If you did not request this login, please ignore this email.</p>
            </div>
        </div>
    </div>';

    $altBody =
        "Prime Holiday Login OTP\n" .
        "Hello " . $toName . ",\n" .
        "Your OTP is: " . $otpCode . "\n" .
        "This OTP is valid for 10 minutes.\n";

    return sendGeneralEmail($toEmail, $toName, $subject, $htmlBody, $errorMessage, $altBody);
}

function sendSignupOtpEmail(string $toEmail, string $toName, string $otpCode, ?string &$errorMessage = null): bool
{
    $errorMessage = null;

    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');

    $subject = 'Prime Holiday Signup OTP';

    $htmlBody = '
    <div style="font-family:Arial,sans-serif;background:#f6f8fc;padding:24px;">
        <div style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5eaf2;">
            <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;padding:24px;">
                <h2 style="margin:0;">Prime Holiday</h2>
                <p style="margin:8px 0 0;">Email verification OTP</p>
            </div>
            <div style="padding:24px;color:#172033;">
                <p>Hi <strong>' . $safeName . '</strong>,</p>
                <p>Use the OTP below to verify your email and complete signup:</p>

                <div style="margin:22px 0;padding:18px;text-align:center;background:#f8fbff;border:1px solid #dbe4f0;border-radius:14px;">
                    <div style="font-size:32px;font-weight:800;letter-spacing:8px;color:#2563eb;">' . $safeOtp . '</div>
                </div>

                <p>This OTP is valid for <strong>10 minutes</strong>.</p>
                <p>If you did not create this account, please ignore this email.</p>
            </div>
        </div>
    </div>';

    $altBody =
        "Prime Holiday Signup OTP\n" .
        "Hello " . $toName . ",\n" .
        "Your signup OTP is: " . $otpCode . "\n" .
        "This OTP is valid for 10 minutes.\n";

    return sendGeneralEmail($toEmail, $toName, $subject, $htmlBody, $errorMessage, $altBody);
}

function sendBookingConfirmationEmail(array $data, ?string &$errorMessage = null): bool
{
    $errorMessage = null;

    try {
        $siteUrl = defined('SITE_URL') ? SITE_URL : 'http://localhost/prime_holiday';

        $requiredKeys = [
            'booking_reference',
            'customer_name',
            'customer_email',
            'package_name',
            'travel_date',
            'number_of_passengers',
            'total_amount',
            'razorpay_payment_id'
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                $errorMessage = 'Missing mail data field: ' . $key;
                return false;
            }
        }

        $customerName = htmlspecialchars((string)$data['customer_name'], ENT_QUOTES, 'UTF-8');
        $customerEmail = trim((string)$data['customer_email']);
        $bookingReference = htmlspecialchars((string)$data['booking_reference'], ENT_QUOTES, 'UTF-8');
        $packageName = htmlspecialchars((string)$data['package_name'], ENT_QUOTES, 'UTF-8');
        $travelDate = htmlspecialchars((string)$data['travel_date'], ENT_QUOTES, 'UTF-8');
        $passengers = (int)$data['number_of_passengers'];
        $paymentId = htmlspecialchars((string)$data['razorpay_payment_id'], ENT_QUOTES, 'UTF-8');
        $amount = '₹' . number_format((float)$data['total_amount'], 2);

        $subject = 'Prime Holiday Booking Confirmation - ' . $bookingReference;

        $htmlBody = '
        <div style="font-family: Arial, sans-serif; background:#f6f8fc; padding:24px;">
            <div style="max-width:700px; margin:0 auto; background:#ffffff; border-radius:18px; overflow:hidden; border:1px solid #e5eaf2;">
                <div style="background:linear-gradient(135deg,#2563eb,#1d4ed8); color:#ffffff; padding:24px;">
                    <h2 style="margin:0;">Prime Holiday</h2>
                    <p style="margin:8px 0 0;">Your booking is confirmed</p>
                </div>

                <div style="padding:24px; color:#172033;">
                    <p>Hi <strong>' . $customerName . '</strong>,</p>
                    <p>Your payment was successful and your booking has been confirmed.</p>

                    <table style="width:100%; border-collapse:collapse; margin-top:18px;">
                        <tr>
                            <td style="padding:10px; border:1px solid #e5eaf2; background:#f8fbff;"><strong>Booking Reference</strong></td>
                            <td style="padding:10px; border:1px solid #e5eaf2;">' . $bookingReference . '</td>
                        </tr>
                        <tr>
                            <td style="padding:10px; border:1px solid #e5eaf2; background:#f8fbff;"><strong>Package</strong></td>
                            <td style="padding:10px; border:1px solid #e5eaf2;">' . $packageName . '</td>
                        </tr>
                        <tr>
                            <td style="padding:10px; border:1px solid #e5eaf2; background:#f8fbff;"><strong>Travel Date</strong></td>
                            <td style="padding:10px; border:1px solid #e5eaf2;">' . $travelDate . '</td>
                        </tr>
                        <tr>
                            <td style="padding:10px; border:1px solid #e5eaf2; background:#f8fbff;"><strong>Passengers</strong></td>
                            <td style="padding:10px; border:1px solid #e5eaf2;">' . $passengers . '</td>
                        </tr>
                        <tr>
                            <td style="padding:10px; border:1px solid #e5eaf2; background:#f8fbff;"><strong>Total Amount</strong></td>
                            <td style="padding:10px; border:1px solid #e5eaf2;">' . $amount . '</td>
                        </tr>
                        <tr>
                            <td style="padding:10px; border:1px solid #e5eaf2; background:#f8fbff;"><strong>Payment ID</strong></td>
                            <td style="padding:10px; border:1px solid #e5eaf2;">' . $paymentId . '</td>
                        </tr>
                    </table>

                    <p style="margin-top:20px;">
                        You can view your booking anytime from your profile:
                        <a href="' . htmlspecialchars($siteUrl . '/profile.php?tab=bookings', ENT_QUOTES, 'UTF-8') . '">My Bookings</a>
                    </p>

                    <p>Thank you for choosing Prime Holiday.</p>
                </div>
            </div>
        </div>';

        $altBody =
            "Prime Holiday Booking Confirmation\n" .
            "Booking Reference: " . $data['booking_reference'] . "\n" .
            "Package: " . $data['package_name'] . "\n" .
            "Travel Date: " . $data['travel_date'] . "\n" .
            "Passengers: " . $data['number_of_passengers'] . "\n" .
            "Amount: ₹" . number_format((float)$data['total_amount'], 2) . "\n" .
            "Payment ID: " . $data['razorpay_payment_id'] . "\n";

        return sendGeneralEmail($customerEmail, (string)$data['customer_name'], $subject, $htmlBody, $errorMessage, $altBody);
    } catch (\Throwable $e) {
        $errorMessage = $e->getMessage();
        return false;
    }
}