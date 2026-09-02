<?php
// verify_subscription.php - Handles subscription payment verification
require_once 'includes/init.php';
include 'includes/connect.php';
include 'includes/razorpay_config.php';
require_once 'vendor/autoload.php';

use Razorpay\Api\Api;

// We want to handle our own errors here to avoid JSON output for the user
set_exception_handler(null);
set_error_handler(null);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
    $razorpay_signature = $_POST['razorpay_signature'] ?? '';
    $plan_id = intval($_POST['plan_id'] ?? 0);

    if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature) || $plan_id <= 0) {
        throw new Exception("Missing or invalid payment details.");
    }

    // 1. Verify Signature
    $expected_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);

    if ($expected_signature !== $razorpay_signature) {
        throw new Exception("Payment verification failed (Invalid Signature).");
    }

    // 2. Fetch Plan and User
    $user = getCurrentUser();
    if (!$user) {
        throw new Exception("User session not found. Please log in.");
    }

    // Determine which ID to use based on account type
    $user_id = null;
    $purchaser_tutor_id = null;

    if ($user['auth_source'] === 'users_tbl') {
        $user_id = $user['id'];
    } else if ($user['auth_source'] === 'tutors') {
        $purchaser_tutor_id = $user['id'];
    } else {
        throw new Exception("Unsupported account type for subscriptions.");
    }

    $query = "SELECT * FROM subscription_plans WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $plan_id);
    $stmt->execute();
    $plan = $stmt->get_result()->fetch_assoc();

    if (!$plan) {
        throw new Exception("Subscription plan not found.");
    }

    // 3. Calculate start and end dates
    $start_date = date('Y-m-d H:i:s');
    $duration = $plan['duration_months'];
    $end_date = date('Y-m-d H:i:s', strtotime("+$duration months"));

    // 4. Record the subscription in premium_members
    $insert_query = "INSERT INTO premium_members (user_id, purchaser_tutor_id, tutor_id, plan_name, amount, transaction_id, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $con->prepare($insert_query);
    
    if (!$insert_stmt) {
        // Ensure table columns are correct
        @mysqli_query($con, "ALTER TABLE `premium_members` ADD COLUMN IF NOT EXISTS `purchaser_tutor_id` INT(11) NULL AFTER `user_id` ");
        $insert_stmt = $con->prepare($insert_query);
    }
    
    if (!$insert_stmt) {
        throw new Exception("Database preparation failed: " . $con->error);
    }

    $insert_stmt->bind_param('iiisdsss', $user_id, $purchaser_tutor_id, $plan['tutor_id'], $plan['plan_name'], $plan['price'], $razorpay_payment_id, $start_date, $end_date);
    
    if (!$insert_stmt->execute()) {
        throw new Exception("Subscription record failed: " . $insert_stmt->error);
    }

    setMessage("Namaste! Subscription successful. You now have premium access to " . $plan['plan_name'] . ".", "success");
    header("Location: tutor_page.php?id=" . $plan['tutor_id']);
    exit();

} catch (Exception $e) {
    error_log("Subscription Verification Error: " . $e->getMessage());
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Error</title>
        <link rel="stylesheet" href="assets/css/styles.css">
        <style>
            .error-container { max-width: 600px; margin: 100px auto; text-align: center; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
            .error-icon { font-size: 60px; color: #ff6b6b; margin-bottom: 20px; }
            .btn-back { display: inline-block; margin-top: 30px; padding: 12px 30px; background: #2a5948; color: white; text-decoration: none; border-radius: 50px; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <h1>Oops! Something went wrong</h1>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
            <p>If your payment was successful, please contact support with Transaction ID: <strong><?php echo htmlspecialchars($razorpay_payment_id); ?></strong></p>
            <a href="home.php" class="btn-back">Return to Home</a>
        </div>
    </body>
    </html>
    <?php
}
?>
