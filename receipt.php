<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = (int)($_SESSION['user_id'] ?? 0);
$bookingId = (int)($_GET['booking_id'] ?? 0);

$error = '';
$receipt = null;
$passengers = [];

if ($bookingId <= 0) {
    $error = 'Invalid booking selected.';
} else {
    try {
        if (isAdmin()) {
            $stmt = $pdo->prepare("
                SELECT
                    b.id,
                    b.booking_reference,
                    b.user_id,
                    b.package_id,
                    b.travel_date,
                    b.number_of_passengers,
                    b.customer_name,
                    b.customer_email,
                    b.customer_phone,
                    b.pickup_point,
                    b.special_request,
                    b.package_price,
                    b.total_amount,
                    b.booking_status,
                    b.payment_status,
                    b.booked_at,
                    p.package_name,
                    p.duration_days,
                    p.duration_nights,
                    p.departure_from,
                    d.name AS destination_name,
                    pay.payment_gateway,
                    pay.razorpay_order_id,
                    pay.razorpay_payment_id,
                    pay.transaction_reference,
                    pay.amount AS payment_amount,
                    pay.currency,
                    pay.payment_status AS payment_record_status,
                    pay.paid_at
                FROM bookings b
                INNER JOIN packages p ON p.id = b.package_id
                INNER JOIN destinations d ON d.id = p.destination_id
                LEFT JOIN payments pay ON pay.booking_id = b.id
                WHERE b.id = ?
                ORDER BY pay.id DESC
                LIMIT 1
            ");
            $stmt->execute([$bookingId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT
                    b.id,
                    b.booking_reference,
                    b.user_id,
                    b.package_id,
                    b.travel_date,
                    b.number_of_passengers,
                    b.customer_name,
                    b.customer_email,
                    b.customer_phone,
                    b.pickup_point,
                    b.special_request,
                    b.package_price,
                    b.total_amount,
                    b.booking_status,
                    b.payment_status,
                    b.booked_at,
                    p.package_name,
                    p.duration_days,
                    p.duration_nights,
                    p.departure_from,
                    d.name AS destination_name,
                    pay.payment_gateway,
                    pay.razorpay_order_id,
                    pay.razorpay_payment_id,
                    pay.transaction_reference,
                    pay.amount AS payment_amount,
                    pay.currency,
                    pay.payment_status AS payment_record_status,
                    pay.paid_at
                FROM bookings b
                INNER JOIN packages p ON p.id = b.package_id
                INNER JOIN destinations d ON d.id = p.destination_id
                LEFT JOIN payments pay ON pay.booking_id = b.id
                WHERE b.id = ? AND b.user_id = ?
                ORDER BY pay.id DESC
                LIMIT 1
            ");
            $stmt->execute([$bookingId, $userId]);
        }

        $receipt = $stmt->fetch();

        if (!$receipt) {
            $error = 'Receipt not found.';
        } else {
            $passengerStmt = $pdo->prepare("
                SELECT passenger_name, passenger_age, passenger_gender
                FROM booking_passengers
                WHERE booking_id = ?
                ORDER BY id ASC
            ");
            $passengerStmt->execute([$bookingId]);
            $passengers = $passengerStmt->fetchAll();
        }
    } catch (Throwable $e) {
        $error = 'Unable to load receipt. ' . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width: 900px;">
        <?php if ($error !== ''): ?>
            <div class="info-card">
                <h2>Booking Receipt</h2>
                <p style="color:#991b1b;"><?= e($error) ?></p>
                <div class="card-actions" style="margin-top:16px;">
                    <a class="btn btn-primary" href="<?= isAdmin() ? BASE_URL . '/admin/bookings.php' : BASE_URL . '/profile.php' ?>">Back</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card-actions receipt-toolbar" style="justify-content:flex-end;margin-bottom:16px;">
                <button type="button" class="btn btn-soft" onclick="window.print()">Print Receipt</button>
                <button type="button" class="btn btn-primary" id="downloadPdfBtn">Download PDF</button>
                <a class="btn btn-soft" href="<?= isAdmin() ? BASE_URL . '/admin/bookings.php' : BASE_URL . '/profile.php' ?>">Back</a>
            </div>

            <div class="receipt-paper-wrap">
                <div class="receipt-paper" id="receiptPrintable">
                    <div class="receipt-title-row">
                        <div>
                            <div class="receipt-brand">Prime Holiday</div>
                            <div class="receipt-subtitle">Booking Receipt &amp; Payment Confirmation</div>
                        </div>
                        <div class="receipt-meta-top">
                            <div><strong>Ref:</strong> <?= e($receipt['booking_reference']) ?></div>
                            <div><strong>Booked:</strong> <?= e($receipt['booked_at']) ?></div>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-section-title">Customer Details</div>
                        <div class="receipt-two-col">
                            <div><strong>Name:</strong> <?= e($receipt['customer_name']) ?></div>
                            <div><strong>Email:</strong> <?= e($receipt['customer_email']) ?></div>
                            <div><strong>Phone:</strong> <?= e($receipt['customer_phone']) ?></div>
                            <div><strong>Pickup Point:</strong> <?= e($receipt['pickup_point'] ?: '-') ?></div>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-section-title">Package Details</div>
                        <div class="receipt-two-col">
                            <div><strong>Package:</strong> <?= e($receipt['package_name']) ?></div>
                            <div><strong>Destination:</strong> <?= e($receipt['destination_name']) ?></div>
                            <div><strong>Duration:</strong> <?= (int)$receipt['duration_days'] ?> Days / <?= (int)$receipt['duration_nights'] ?> Nights</div>
                            <div><strong>Departure:</strong> <?= e($receipt['departure_from'] ?: '-') ?></div>
                            <div><strong>Travel Date:</strong> <?= e($receipt['travel_date']) ?></div>
                            <div><strong>Passengers:</strong> <?= (int)$receipt['number_of_passengers'] ?></div>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-section-title">Booking Status</div>
                        <div class="receipt-two-col">
                            <div><strong>Booking Status:</strong> <?= e(ucfirst($receipt['booking_status'])) ?></div>
                            <div><strong>Payment Status:</strong> <?= e(ucfirst($receipt['payment_status'])) ?></div>
                            <div><strong>Paid At:</strong> <?= e($receipt['paid_at'] ?: '-') ?></div>
                            <div><strong>Booked At:</strong> <?= e($receipt['booked_at']) ?></div>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-section-title">Special Request</div>
                        <div class="receipt-note-box"><?= nl2br(e($receipt['special_request'] ?: '-')) ?></div>
                    </div>

                    <?php if (!empty($passengers)): ?>
                        <div class="receipt-section">
                            <div class="receipt-section-title">Passenger Details</div>
                            <table class="receipt-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Name</th>
                                        <th style="width:80px;">Age</th>
                                        <th style="width:120px;">Gender</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($passengers as $index => $row): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= e($row['passenger_name']) ?></td>
                                            <td><?= e((string)($row['passenger_age'] ?? '-')) ?></td>
                                            <td><?= e($row['passenger_gender'] ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="receipt-section">
                        <div class="receipt-section-title">Payment Summary</div>
                        <table class="receipt-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th style="width:35%;">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Package Price</td>
                                    <td><?= e(formatPrice($receipt['package_price'])) ?></td>
                                </tr>
                                <tr>
                                    <td>Total Amount</td>
                                    <td><?= e(formatPrice($receipt['total_amount'])) ?></td>
                                </tr>
                                <tr>
                                    <td>Payment Gateway</td>
                                    <td><?= e($receipt['payment_gateway'] ?: '-') ?></td>
                                </tr>
                                <tr>
                                    <td>Payment Record Status</td>
                                    <td><?= e(ucfirst($receipt['payment_record_status'] ?: '-')) ?></td>
                                </tr>
                                <tr>
                                    <td>Payment ID</td>
                                    <td><?= e($receipt['razorpay_payment_id'] ?: '-') ?></td>
                                </tr>
                                <tr>
                                    <td>Order ID</td>
                                    <td><?= e($receipt['razorpay_order_id'] ?: '-') ?></td>
                                </tr>
                                <tr>
                                    <td>Transaction Reference</td>
                                    <td><?= e($receipt['transaction_reference'] ?: '-') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="receipt-footer-line">
                        Prime Holiday • This receipt confirms your booking and payment details.
                    </div>
                </div>
            </div>

            <style>
                .receipt-paper-wrap {
                    background: transparent;
                }

                .receipt-paper {
                    background: #fff;
                    color: #111827;
                    width: 100%;
                    max-width: 820px;
                    margin: 0 auto;
                    padding: 28px 30px 24px;
                    border-radius: 16px;
                    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
                    border: 1px solid #e5e7eb;
                    box-sizing: border-box;
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 14px;
                    line-height: 1.45;
                }

                .receipt-paper *,
                .receipt-paper *::before,
                .receipt-paper *::after {
                    box-sizing: border-box;
                }

                .receipt-title-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 20px;
                    margin-bottom: 18px;
                    border-bottom: 1px solid #e5e7eb;
                    padding-bottom: 14px;
                }

                .receipt-brand {
                    font-size: 30px;
                    font-weight: 800;
                    line-height: 1.1;
                    color: #111827;
                    margin-bottom: 4px;
                }

                .receipt-subtitle {
                    font-size: 15px;
                    color: #4b5563;
                    font-weight: 500;
                }

                .receipt-meta-top {
                    text-align: right;
                    font-size: 13px;
                    line-height: 1.6;
                    color: #111827;
                    white-space: nowrap;
                }

                .receipt-section {
                    margin-bottom: 16px;
                }

                .receipt-section-title {
                    font-size: 16px;
                    font-weight: 800;
                    color: #111827;
                    margin-bottom: 8px;
                }

                .receipt-two-col {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 8px 18px;
                }

                .receipt-note-box {
                    min-height: 24px;
                    white-space: pre-wrap;
                    word-break: break-word;
                }

                .receipt-table {
                    width: 100%;
                    border-collapse: collapse;
                    table-layout: fixed;
                }

                .receipt-table th,
                .receipt-table td {
                    border: 1px solid #e5e7eb;
                    padding: 9px 10px;
                    text-align: left;
                    vertical-align: top;
                    word-break: break-word;
                }

                .receipt-table th {
                    background: #f8fafc;
                    font-weight: 700;
                }

                .receipt-footer-line {
                    margin-top: 20px;
                    padding-top: 12px;
                    border-top: 1px solid #e5e7eb;
                    text-align: center;
                    font-size: 13px;
                    color: #4b5563;
                }

                @media (max-width: 700px) {
                    .receipt-paper {
                        padding: 20px 18px;
                    }

                    .receipt-title-row {
                        flex-direction: column;
                        align-items: flex-start;
                    }

                    .receipt-meta-top {
                        text-align: left;
                        white-space: normal;
                    }

                    .receipt-two-col {
                        grid-template-columns: 1fr;
                    }
                }

                @media print {
                    .site-header,
                    .site-footer,
                    .btn,
                    .hero-actions,
                    nav,
                    .receipt-toolbar {
                        display: none !important;
                    }

                    html,
                    body {
                        background: #ffffff !important;
                    }

                    .section {
                        padding: 0 !important;
                    }

                    .container {
                        width: 100% !important;
                        max-width: 100% !important;
                    }

                    .receipt-paper-wrap {
                        margin: 0 !important;
                        padding: 0 !important;
                    }

                    .receipt-paper {
                        width: 100% !important;
                        max-width: 100% !important;
                        border: none !important;
                        box-shadow: none !important;
                        border-radius: 0 !important;
                        margin: 0 !important;
                        padding: 16px 18px !important;
                        font-size: 12px !important;
                    }

                    .receipt-brand {
                        font-size: 24px !important;
                    }

                    .receipt-subtitle {
                        font-size: 13px !important;
                    }

                    .receipt-section-title {
                        font-size: 14px !important;
                    }

                    .receipt-meta-top {
                        font-size: 12px !important;
                    }

                    .receipt-table th,
                    .receipt-table td {
                        padding: 6px 7px !important;
                        font-size: 11px !important;
                    }

                    .receipt-section {
                        margin-bottom: 12px !important;
                        break-inside: avoid;
                        page-break-inside: avoid;
                    }

                    .receipt-table {
                        page-break-inside: avoid;
                    }
                }
            </style>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var downloadBtn = document.getElementById('downloadPdfBtn');
                var printableArea = document.getElementById('receiptPrintable');

                if (!downloadBtn || !printableArea || typeof html2pdf === 'undefined') {
                    return;
                }

                downloadBtn.addEventListener('click', function () {
                    var reference = <?= json_encode((string)($receipt['booking_reference'] ?? 'receipt')) ?>;
                    var safeReference = String(reference || 'receipt').replace(/[^a-zA-Z0-9_-]/g, '_');
                    var originalText = downloadBtn.textContent;

                    downloadBtn.disabled = true;
                    downloadBtn.textContent = 'Preparing PDF...';

                    var opt = {
                        margin: [6, 6, 6, 6],
                        filename: 'PrimeHoliday_Receipt_' + safeReference + '.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 2,
                            useCORS: true,
                            logging: false,
                            backgroundColor: '#ffffff',
                            scrollX: 0,
                            scrollY: 0
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait'
                        },
                        pagebreak: {
                            mode: ['css', 'legacy']
                        }
                    };

                    html2pdf()
                        .set(opt)
                        .from(printableArea)
                        .save()
                        .then(function () {
                            downloadBtn.disabled = false;
                            downloadBtn.textContent = originalText;
                        })
                        .catch(function (error) {
                            console.error('Receipt PDF download failed:', error);
                            alert('Unable to download receipt PDF. Please try again.');
                            downloadBtn.disabled = false;
                            downloadBtn.textContent = originalText;
                        });
                });
            });
            </script>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>