<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];
$packageId = (int)($_GET['package_id'] ?? 0);
$error = '';
$user = null;
$package = null;

$bangalorePickupPoints = [
    'Anand Rao Circle',
    'Majestic',
    'Madiwala',
    'Central Silk Board',
    'Electronic City',
    'Hebbal',
    'Marathahalli',
    'Tin Factory',
    'Kalasipalayam',
    'Bellandur',
    'Yeshwanthpur',
    'HSR Layout',
    'K R Puram',
    'Bommasandra',
    'Yelahanka',
];

$userStmt = $pdo->prepare("
    SELECT id, full_name, email, phone
    FROM users
    WHERE id = ?
    LIMIT 1
");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

if ($packageId > 0) {
    $packageStmt = $pdo->prepare("
        SELECT p.*, d.name AS destination_name
        FROM packages p
        INNER JOIN destinations d ON d.id = p.destination_id
        WHERE p.id = ? AND p.is_active = 1
        LIMIT 1
    ");
    $packageStmt->execute([$packageId]);
    $package = $packageStmt->fetch();
}

if (!$package) {
    $error = 'Invalid package selected.';
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section admin-packages-section">
    <div class="container">
        <div class="section-head admin-packages-head" style="margin-bottom:12px;">
            <span class="badge">Booking & Payment</span>
            <h2 style="margin:8px 0 6px;">Complete Your Booking</h2>
            <p style="margin:0;">Enter your trip details, upload Aadhar, choose pickup point, and pay securely.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="notice-error" style="margin-bottom:12px;"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($package): ?>
            <div class="info-grid" style="margin-bottom:14px; gap:16px;">
                <div class="info-card" style="padding:20px 22px;">
                    <h3 style="margin-bottom:10px;"><?= e($package['package_name']) ?></h3>
                    <p style="margin:0 0 8px;"><strong>Destination:</strong> <?= e($package['destination_name']) ?></p>
                    <p style="margin:0 0 8px;"><strong>Duration:</strong> <?= (int)$package['duration_days'] ?> Days / <?= (int)$package['duration_nights'] ?> Nights</p>
                    <p style="margin:0 0 8px;"><strong>Departure From:</strong> <?= e($package['departure_from'] ?? '-') ?></p>
                    <p style="margin:0;"><strong>Price Per Person:</strong> <?= e(formatPrice(!empty($package['offer_price']) ? $package['offer_price'] : $package['price'])) ?></p>
                </div>

                <div class="info-card" style="grid-column: span 2; padding:20px 22px;">
                    <h3 style="margin-bottom:12px;">Booking Details</h3>

                    <form id="bookingForm" novalidate enctype="multipart/form-data">
                        <input type="hidden" name="package_id" value="<?= (int)$package['id'] ?>">

                        <div class="form-grid-2" style="gap:14px;">
                            <div class="field-wrap">
                                <label class="field-label">Travel Date</label>
                                <input type="date" name="travel_date" required>
                                <div class="field-hint">Select your trip start date.</div>
                                <div class="field-error" data-error-for="travel_date" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Number of Passengers</label>
                                <input type="number" name="number_of_passengers" min="1" value="1" required>
                                <div class="field-hint">Enter how many people are travelling.</div>
                                <div class="field-error" data-error-for="number_of_passengers" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Full Name</label>
                                <input type="text" name="customer_name" value="<?= e($user['full_name'] ?? '') ?>" required>
                                <div class="field-hint">Main traveler name.</div>
                                <div class="field-error" data-error-for="customer_name" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Email Address</label>
                                <input type="email" name="customer_email" value="<?= e($user['email'] ?? '') ?>" required>
                                <div class="field-hint">Receipt and updates will come here.</div>
                                <div class="field-error" data-error-for="customer_email" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Mobile Number</label>
                                <input type="text" name="customer_phone" value="<?= e($user['phone'] ?? '') ?>" required maxlength="10">
                                <div class="field-hint">Use your active number.</div>
                                <div class="field-error" data-error-for="customer_phone" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Aadhar Card Number</label>
                                <input type="text" name="aadhar_number" maxlength="12" required>
                                <div class="field-hint">Enter 12 digit Aadhar number.</div>
                                <div class="field-error" data-error-for="aadhar_number" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Aadhar Card Image</label>
                                <input type="file" name="aadhar_image" accept=".jpg,.jpeg,.png,.webp" required>
                                <div class="field-hint">Upload JPG, JPEG, PNG, or WEBP. Max 5 MB.</div>
                                <div class="field-error" data-error-for="aadhar_image" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Pickup Point</label>
                                <select name="pickup_point" required>
                                    <option value="">Select Bangalore pickup point</option>
                                    <?php foreach ($bangalorePickupPoints as $point): ?>
                                        <option value="<?= e($point) ?>"><?= e($point) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="field-hint">Choose your Bangalore pickup point.</div>
                                <div class="field-error" data-error-for="pickup_point" style="color:#dc2626;font-size:12px;margin-top:5px;display:none;"></div>
                            </div>
                        </div>

                        <div class="field-wrap" style="margin-top:10px;">
                            <label class="field-label">Special Request</label>
                            <textarea name="special_request"></textarea>
                            <div class="field-hint">Optional travel note.</div>
                        </div>

                        <div class="form-actions" style="margin-top:14px;">
                            <button type="submit" class="btn btn-primary" id="payBtn">Proceed to Payment</button>
                        </div>
                    </form>

                    <div id="bookingMessage" style="margin-top:12px;"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="feedbackModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="max-width:560px; width:100%; background:#fff; border-radius:22px; padding:20px 22px; box-shadow:0 20px 60px rgba(0,0,0,.18);">
        <h3 style="margin:0 0 8px;">Thank you for your payment</h3>
        <p style="margin:0 0 12px;">Booking confirmed. Please rate your booking experience.</p>

        <form id="feedbackForm">
            <input type="hidden" name="booking_id" id="feedback_booking_id">
            <input type="hidden" name="package_id" id="feedback_package_id">

            <div class="field-wrap">
                <label class="field-label">Rating</label>
                <select name="rating" required>
                    <option value="">Select rating</option>
                    <option value="1">1 Star</option>
                    <option value="2">2 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="5">5 Stars</option>
                </select>
            </div>

            <div class="field-wrap" style="margin-top:10px;">
                <label class="field-label">Feedback</label>
                <textarea name="review_text" placeholder="Write your feedback"></textarea>
            </div>

            <div class="form-actions" style="margin-top:14px;">
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
                <button type="button" class="btn btn-soft" id="closeFeedback">Close</button>
            </div>

            <div id="feedbackMessage" style="margin-top:12px;"></div>
        </form>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const bookingForm = document.getElementById('bookingForm');
const bookingMessage = document.getElementById('bookingMessage');
const feedbackModal = document.getElementById('feedbackModal');
const feedbackForm = document.getElementById('feedbackForm');
const feedbackMessage = document.getElementById('feedbackMessage');
const closeFeedback = document.getElementById('closeFeedback');
const payBtn = document.getElementById('payBtn');

function showMessage(el, text, ok = false) {
    el.innerHTML = '<div class="' + (ok ? 'notice-success' : 'notice-error') + '">' + text + '</div>';
}

function setPayButtonState(loading) {
    if (!payBtn) return;
    payBtn.disabled = loading;
    payBtn.textContent = loading ? 'Processing...' : 'Proceed to Payment';
}

function getErrorEl(fieldName) {
    return document.querySelector('[data-error-for="' + fieldName + '"]');
}

function setFieldError(fieldName, message) {
    const errorEl = getErrorEl(fieldName);
    const field = bookingForm ? bookingForm.querySelector('[name="' + fieldName + '"]') : null;

    if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = message ? 'block' : 'none';
    }

    if (field) {
        field.style.borderColor = message ? '#dc2626' : '';
    }
}

function clearFieldError(fieldName) {
    setFieldError(fieldName, '');
}

function validateBookingForm() {
    if (!bookingForm) return false;

    let isValid = true;

    const travelDate = bookingForm.travel_date.value.trim();
    const passengers = bookingForm.number_of_passengers.value.trim();
    const customerName = bookingForm.customer_name.value.trim();
    const customerEmail = bookingForm.customer_email.value.trim();
    const customerPhone = bookingForm.customer_phone.value.trim();
    const aadharNumber = bookingForm.aadhar_number.value.trim();
    const pickupPoint = bookingForm.pickup_point.value.trim();
    const aadharImageField = bookingForm.querySelector('[name="aadhar_image"]');
    const aadharImage = aadharImageField && aadharImageField.files ? aadharImageField.files[0] : null;

    clearFieldError('travel_date');
    clearFieldError('number_of_passengers');
    clearFieldError('customer_name');
    clearFieldError('customer_email');
    clearFieldError('customer_phone');
    clearFieldError('aadhar_number');
    clearFieldError('aadhar_image');
    clearFieldError('pickup_point');

    if (travelDate === '') {
        setFieldError('travel_date', 'Please select travel date.');
        isValid = false;
    }

    if (passengers === '' || Number(passengers) < 1) {
        setFieldError('number_of_passengers', 'Please enter at least 1 passenger.');
        isValid = false;
    }

    if (customerName === '') {
        setFieldError('customer_name', 'Please enter full name.');
        isValid = false;
    }

    if (customerEmail === '') {
        setFieldError('customer_email', 'Please enter email address.');
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail)) {
        setFieldError('customer_email', 'Please enter a valid email address.');
        isValid = false;
    }

    if (customerPhone === '') {
        setFieldError('customer_phone', 'Please enter mobile number.');
        isValid = false;
    } else if (!/^\d{10}$/.test(customerPhone)) {
        setFieldError('customer_phone', 'Mobile number must be exactly 10 digits.');
        isValid = false;
    }

    if (aadharNumber === '') {
        setFieldError('aadhar_number', 'Please enter Aadhar card number.');
        isValid = false;
    } else if (!/^\d{12}$/.test(aadharNumber)) {
        setFieldError('aadhar_number', 'Aadhar number must be exactly 12 digits.');
        isValid = false;
    }

    if (!aadharImage) {
        setFieldError('aadhar_image', 'Please upload Aadhar image.');
        isValid = false;
    } else {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (allowedTypes.indexOf(aadharImage.type) === -1) {
            setFieldError('aadhar_image', 'Only JPG, JPEG, PNG, or WEBP files are allowed.');
            isValid = false;
        } else if (aadharImage.size > 5 * 1024 * 1024) {
            setFieldError('aadhar_image', 'Aadhar image size must be 5 MB or less.');
            isValid = false;
        }
    }

    if (pickupPoint === '') {
        setFieldError('pickup_point', 'Please select pickup point.');
        isValid = false;
    }

    return isValid;
}

if (bookingForm) {
    const requiredFields = [
        'travel_date',
        'number_of_passengers',
        'customer_name',
        'customer_email',
        'customer_phone',
        'aadhar_number',
        'aadhar_image',
        'pickup_point'
    ];

    requiredFields.forEach(function(fieldName) {
        const field = bookingForm.querySelector('[name="' + fieldName + '"]');
        if (!field) return;

        field.addEventListener('input', function() {
            validateBookingForm();
        });

        field.addEventListener('change', function() {
            validateBookingForm();
        });
    });

    bookingForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        bookingMessage.innerHTML = '';

        const formValid = validateBookingForm();
        if (!formValid) {
            showMessage(bookingMessage, 'Please fill all required fields correctly.');
            return;
        }

        setPayButtonState(true);

        try {
            const formData = new FormData(bookingForm);

            const createRes = await fetch('<?= BASE_URL ?>/api/create-razorpay-order.php', {
                method: 'POST',
                body: formData
            });

            const createData = await createRes.json();

            if (!createData.success) {
                let msg = createData.message || 'Failed to create payment order.';
                if (createData.debug) {
                    msg += '<br><small>' + createData.debug + '</small>';
                }
                showMessage(bookingMessage, msg);
                setPayButtonState(false);
                return;
            }

            const options = {
                key: createData.key,
                amount: createData.amount,
                currency: createData.currency,
                name: 'Prime Holiday',
                description: createData.package_name,
                order_id: createData.order_id,
                handler: async function (response) {
                    try {
                        const verifyData = new FormData();
                        verifyData.append('booking_id', createData.booking_id);
                        verifyData.append('razorpay_payment_id', response.razorpay_payment_id);
                        verifyData.append('razorpay_order_id', response.razorpay_order_id);
                        verifyData.append('razorpay_signature', response.razorpay_signature);

                        const verifyRes = await fetch('<?= BASE_URL ?>/api/verify-razorpay-payment.php', {
                            method: 'POST',
                            body: verifyData
                        });

                        const verifyJson = await verifyRes.json();

                        if (!verifyJson.success) {
                            showMessage(bookingMessage, verifyJson.message || 'Payment verification failed.');
                            setPayButtonState(false);
                            return;
                        }

                        let successText = 'Payment successful. Booking confirmed.';
                        if (verifyJson.mail_sent) {
                            successText += ' Confirmation email sent.';
                        } else {
                            successText += ' Booking confirmed, but email could not be sent right now.';
                        }

                        showMessage(bookingMessage, successText, true);

                        if (verifyJson.mail_error) {
                            console.log('Mail error:', verifyJson.mail_error);
                        }

                        document.getElementById('feedback_booking_id').value = verifyJson.booking_id;
                        document.getElementById('feedback_package_id').value = verifyJson.package_id;
                        feedbackModal.style.display = 'flex';
                    } catch (err) {
                        showMessage(bookingMessage, 'Payment completed, but there was an issue finalizing the response.');
                    } finally {
                        setPayButtonState(false);
                    }
                },
                prefill: {
                    name: createData.customer_name,
                    email: createData.customer_email,
                    contact: createData.customer_phone
                },
                theme: {
                    color: '#2563eb'
                },
                modal: {
                    ondismiss: function () {
                        setPayButtonState(false);
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.open();
        } catch (err) {
            showMessage(bookingMessage, 'Something went wrong while starting payment.');
            setPayButtonState(false);
        }
    });
}

if (feedbackForm) {
    feedbackForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        feedbackMessage.innerHTML = '';

        try {
            const formData = new FormData(feedbackForm);

            const res = await fetch('<?= BASE_URL ?>/api/save-feedback.php', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (!data.success) {
                showMessage(feedbackMessage, data.message || 'Failed to save feedback.');
                return;
            }

            showMessage(feedbackMessage, 'Thank you. Your feedback was saved.', true);

            setTimeout(function () {
                feedbackModal.style.display = 'none';
                feedbackForm.reset();
                window.location.href = '<?= BASE_URL ?>/profile.php';
            }, 900);
        } catch (err) {
            showMessage(feedbackMessage, 'Something went wrong while saving feedback.');
        }
    });
}

if (closeFeedback) {
    closeFeedback.addEventListener('click', function () {
        feedbackModal.style.display = 'none';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>