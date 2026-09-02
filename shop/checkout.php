<?php
require_once '../includes/init.php';
include '../includes/connect.php';
include '../includes/razorpay_config.php';

// Force login for checkout
if (!checkSession()) {
    header("Location: ../login-registration.php?redirect=shop/checkout.php&msg=Please+login+to+complete+your+purchase");
    exit();
}

$user = getCurrentUser();

if (!$user) {
    header("Location: ../login-registration.php?redirect=shop/checkout.php&msg=Please+login+to+complete+your+purchase");
    exit();
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: shop.php");
    exit();
}

$cart_items = [];
$total_amount = 0;
$ids = implode(',', array_keys($cart));
$res = mysqli_query($con, "SELECT * FROM products WHERE id IN ($ids)");
while ($product = mysqli_fetch_assoc($res)) {
    $product['qty'] = $cart[$product['id']];
    $product['subtotal'] = $product['price'] * $product['qty'];
    $total_amount += $product['subtotal'];
    $cart_items[] = $product;
}

// Razorpay Order Creation
$display_amount = $total_amount;
$razorpay_order_id = null;

if ($total_amount > 0) {
    $api_url = "https://api.razorpay.com/v1/orders";
    $auth = base64_encode(RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET);

    $data = [
        'amount'          => $total_amount * 100, // Amount in paise
        'currency'        => CURRENCY,
        'payment_capture' => 1 // Auto capture
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Basic $auth"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix local SSL issues

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200) {
        $order_data = json_decode($response, true);
        $razorpay_order_id = $order_data['id'] ?? null;
        if (!$razorpay_order_id) {
            $error = "Payment gateway response invalid. Please contact support.";
            error_log("Razorpay Order ID missing in response: " . $response);
        }
    } else {
        $error = "Failed to initialize payment gateway. Error: " . ($curl_error ?: "HTTP $http_code");
        error_log("Razorpay Order Error: Code $http_code, Error: $curl_error, Response: " . $response);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - YogaMart</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-top: 2rem;
        }

        .checkout-box {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .order-summary {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            height: fit-content;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #666;
            font-size: 0.95rem;
        }

        .summary-total {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid #f9f9f9;
            display: flex;
            justify-content: space-between;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--brand);
        }

        .checkout-btn {
            width: 100%;
            background: var(--brand);
            color: white;
            border: none;
            padding: 1.2rem;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 2rem;
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.3);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5d4e;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--sage);
        }

        #payment-loader {
            display: none;
            margin-top: 1.5rem;
            color: var(--brand);
            font-weight: 600;
            text-align: center;
            padding: 1rem;
            background: #fdfaf0;
            border-radius: 12px;
            border: 1px solid #f0e6d2;
        }

        .error-banner {
            display: none;
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            color: #9b2c2c;
            padding: 1.25rem 1.5rem;
            border-radius: 16px;
            border-left: 6px solid #e53e3e;
            margin-bottom: 2rem;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(229, 62, 62, 0.15);
            position: relative;
            overflow: hidden;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .error-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: #e53e3e;
            width: 100%;
            animation: progress 8s linear forwards;
        }

        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }

        .error-banner i {
            margin-right: 12px;
            font-size: 1.2rem;
            color: #e53e3e;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div style="display: flex; align-items: center; gap: 15px; margin-top: 2rem;">
            <a href="cart.php" style="color: #666; text-decoration: none; transition: 0.3s;" onmouseover="this.style.color='var(--brand)'" onmouseout="this.style.color='#666'"><i class="fas fa-arrow-left"></i> Back to Cart</a>
        </div>
        
        <h1 style="font-family: 'Playfair Display', serif; margin-top: 1rem; color: #4a5d4e; font-size: 2.5rem;">Secure Checkout</h1>

        <!-- Attractive Error Banner -->
        <div id="error-banner" class="error-banner">
            <div style="display: flex; align-items: center;">
                <i class="fas fa-circle-exclamation"></i>
                <span id="error-message"></span>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid #fecaca; display: block;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="checkout-layout">
            <!-- Left Side: Shipping -->
            <div class="checkout-box">
                <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-truck" style="color: var(--brand); margin-right: 10px;"></i> Shipping Information</h3>

                <form id="shippingForm" onsubmit="event.preventDefault();">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Shipping Address</label>
                        <input type="text" id="shipping_address" placeholder="House/Apartment #, Street, Locality" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" id="shipping_city" placeholder="e.g. Mumbai" required>
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" id="shipping_state" placeholder="e.g. Maharashtra" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Zip Code / PIN</label>
                        <input type="text" id="shipping_zip" placeholder="6-digit PIN" required>
                    </div>

                    <div id="payment-loader">
                        <i class="fas fa-spinner fa-spin"></i> <span id="loader-text">Processing payment...</span>
                    </div>

                    <button type="button" id="pay-button" class="checkout-btn" <?php echo !$razorpay_order_id ? 'disabled' : ''; ?>>
                        <i class="fas fa-shield-alt"></i> Pay ₹<?php echo number_format($total_amount, 2); ?> Securely
                    </button>
                </form>
            </div>

            <!-- Right Side: Summary -->
            <div class="order-summary">
                <h3 style="margin-bottom: 1.5rem;">Order Summary</h3>
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['qty']; ?></span>
                        <span>₹<?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="summary-total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total_amount, 2); ?></span>
                </div>

                <div style="margin-top: 2rem; font-size: 0.85rem; color: #999; text-align: center;">
                    <i class="fas fa-lock"></i> Secured by Razorpay
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
    (function() {
        const payButton = document.getElementById('pay-button');
        const loader = document.getElementById('payment-loader');
        const errorBanner = document.getElementById('error-banner');
        const errorMessage = document.getElementById('error-message');

        function showError(msg) {
            errorMessage.textContent = msg;
            errorBanner.style.display = 'block';
            errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Hide after 8 seconds
            setTimeout(() => {
                errorBanner.style.display = 'none';
            }, 8000);
        }

        if (payButton) {
            payButton.onclick = function(e) {
                errorBanner.style.display = 'none'; // Clear previous errors
                
                const address = document.getElementById('shipping_address').value.trim();
                const city = document.getElementById('shipping_city').value.trim();
                const state = document.getElementById('shipping_state').value.trim();
                const zip = document.getElementById('shipping_zip').value.trim();

                if (!address || !city || !state || !zip) {
                    showError('Please fill in all shipping details to proceed.');
                    if (!address) document.getElementById('shipping_address').focus();
                    else if (!city) document.getElementById('shipping_city').focus();
                    else if (!state) document.getElementById('shipping_state').focus();
                    else if (!zip) document.getElementById('shipping_zip').focus();
                    return;
                }

                const options = {
                    "key": "<?php echo RAZORPAY_KEY_ID; ?>",
                    "amount": "<?php echo $total_amount * 100; ?>",
                    "currency": "<?php echo CURRENCY; ?>",
                    "name": "YogaMart",
                    "description": "Secure Purchase from YogaMart",
                    "image": "../assets/images/img.avif",
                    "order_id": "<?php echo $razorpay_order_id; ?>",
                    "handler": function (response) {
                        // Payment successful
                        payButton.disabled = true;
                        payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying Payment...';
                        loader.style.display = 'block';

                        // Send to verification script
                        const formData = new FormData();
                        formData.append('razorpay_payment_id', response.razorpay_payment_id);
                        formData.append('razorpay_order_id', response.razorpay_order_id);
                        formData.append('razorpay_signature', response.razorpay_signature);
                        
                        // Detailed shipping
                        formData.append('shipping_address', address);
                        formData.append('shipping_city', city);
                        formData.append('shipping_state', state);
                        formData.append('shipping_zip', zip);

                        fetch('verify_payment.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = 'order_confirmation.php?id=' + data.order_id;
                            } else {
                                showError('Verification failed: ' + data.error);
                                payButton.disabled = false;
                                payButton.innerHTML = '<i class="fas fa-redo"></i> Retry Verification';
                                loader.style.display = 'none';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showError('A network error occurred. Please check your connection and try again.');
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="fas fa-shield-alt"></i> Pay Securely';
                            loader.style.display = 'none';
                        });
                    },
                    "prefill": {
                        "name": "<?php echo $user['username']; ?>",
                        "email": "<?php echo $user['email']; ?>"
                    },
                    "theme": {
                        "color": "#4a7c59"
                    }
                };

                const rzp = new Razorpay(options);
                rzp.on('payment.failed', function (response){
                    showError("Payment Failed: " + response.error.description + " (Error Code: " + response.error.code + ")");
                });
                rzp.open();
                e.preventDefault();
            }
        }
    })();
    </script>
</body>
</html>
