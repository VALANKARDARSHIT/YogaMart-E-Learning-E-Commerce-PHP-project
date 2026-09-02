<?php
require_once 'includes/init.php';
include 'includes/connect.php';
include 'includes/razorpay_config.php';
require_once 'vendor/autoload.php';

use Razorpay\Api\Api;

requireLogin();

$user = getCurrentUser();
$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;

if ($plan_id <= 0) {
    header("Location: home.php");
    exit();
}

// Fetch plan details (must be enabled)
$query = "SELECT sp.*, t.username as tutor_name, t.profile_picture FROM subscription_plans sp JOIN tutors t ON sp.tutor_id = t.id WHERE sp.id = ? AND sp.is_enabled = 1";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $plan_id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();

if (!$plan) {
    header("Location: home.php?msg=This+plan+is+currently+unavailable");
    exit();
}

// Initialize Razorpay
$api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);

// Create Razorpay Order
$orderData = [
    'receipt'         => 'sub_' . time() . '_' . $plan_id,
    'amount'          => $plan['price'] * 100, // in paise
    'currency'        => 'INR',
    'payment_capture' => 1
];

$razorpayOrderId = null;
try {
    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];
    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
} catch (Exception $e) {
    $error = "Failed to create Razorpay order: " . $e->getMessage();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Checkout - YogaMart</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 600px;
            margin: 5rem auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }
        .order-summary {
            background: #f9fbf8;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
            font-weight: 700;
            font-size: 1.2rem;
            color: #2a5948;
        }
        .btn-pay {
            width: 100%;
            background: #ff6b6b;
            color: white;
            padding: 18px;
            border-radius: 12px;
            border: none;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-pay:hover {
            background: #e65a5a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        .tutor-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 2rem;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .tutor-avatar-checkout {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f0f0f0;
        }
        .tutor-info-checkout h3 {
            margin: 0;
            color: #2a5948;
            font-family: 'Playfair Display', serif;
        }
        .tutor-info-checkout p {
            margin: 5px 0 0;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="checkout-container">
            <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 2rem; color: #2a5948;">Confirm Subscription</h2>
            
            <div class="tutor-header">
                <?php
                $pic_src = 'https://img.icons8.com/color/96/user-male-circle--v1.png';
                if (!empty($plan['profile_picture'])) {
                    $pic_path = $plan['profile_picture'];
                    if (filter_var($pic_path, FILTER_VALIDATE_URL)) {
                        $pic_src = $pic_path;
                    } else {
                        $pic_src = $web_root . ltrim($pic_path, '/');
                    }
                }
                ?>
                <img src="<?php echo htmlspecialchars($pic_src); ?>" alt="<?php echo htmlspecialchars($plan['tutor_name']); ?>" class="tutor-avatar-checkout" onerror="this.src='https://img.icons8.com/color/96/user-male-circle--v1.png';">
                <div class="tutor-info-checkout">
                    <h3><?php echo htmlspecialchars($plan['tutor_name']); ?></h3>
                    <p>Professional Yoga Instructor</p>
                </div>
            </div>

            <div class="order-summary">
                <div class="summary-item">
                    <span>Instructor</span>
                    <span><?php echo htmlspecialchars($plan['tutor_name']); ?></span>
                </div>
                <div class="summary-item">
                    <span>Plan Name</span>
                    <span><?php echo htmlspecialchars($plan['plan_name']); ?></span>
                </div>
                <div class="summary-item">
                    <span>Duration</span>
                    <span><?php echo $plan['duration_months']; ?> Months</span>
                </div>
                <div class="summary-item">
                    <span>Total Amount</span>
                    <span>₹<?php echo number_format($plan['price'], 2); ?></span>
                </div>
            </div>

            <button id="rzp-button1" class="btn-pay">Pay with Razorpay</button>
        </div>
    </div>

    <script>
    (function() {
        const options = {
            "key": "<?php echo RAZORPAY_KEY_ID; ?>",
            "amount": "<?php echo $orderData['amount']; ?>",
            "currency": "INR",
            "name": "YogaMart Subscription",
            "description": "Subscription for <?php echo addslashes($plan['plan_name']); ?>",
            "image": "assets/images/img.avif",
            "order_id": "<?php echo $razorpayOrderId; ?>",
            "handler": function (response){
                // Submit to verification script
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'verify_subscription.php';
                
                const fields = {
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature,
                    plan_id: '<?php echo $plan_id; ?>'
                };

                for (const key in fields) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            },
            "prefill": {
                "name": "<?php echo addslashes($user['username']); ?>",
                "email": "<?php echo addslashes($user['email']); ?>"
            },
            "theme": {
                "color": "#2a5948"
            }
        };
        const rzp1 = new Razorpay(options);
        const btn = document.getElementById('rzp-button1');
        if (btn) {
            btn.onclick = function(e){
                rzp1.open();
                e.preventDefault();
            }
        }
    })();
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
