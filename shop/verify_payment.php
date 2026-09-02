<?php
require_once '../includes/init.php';
include '../includes/connect.php';
include '../includes/razorpay_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
$razorpay_signature = $_POST['razorpay_signature'] ?? '';

// Handle detailed shipping fields
$shipping_address_line = $_POST['shipping_address'] ?? '';
$shipping_city = $_POST['shipping_city'] ?? '';
$shipping_state = $_POST['shipping_state'] ?? '';
$shipping_zip = $_POST['shipping_zip'] ?? '';

$full_shipping_address = $shipping_address_line;
if ($shipping_city || $shipping_state || $shipping_zip) {
    $full_shipping_address .= "\n" . $shipping_city . ", " . $shipping_state . " - " . $shipping_zip;
}

if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
    echo json_encode(['success' => false, 'error' => 'Missing payment details']);
    exit();
}

// 1. Verify Signature
$expected_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);

if ($expected_signature !== $razorpay_signature) {
    echo json_encode(['success' => false, 'error' => 'Payment verification failed (Invalid Signature)']);
    exit();
}

// 2. Process the order
$user = getCurrentUser();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User session not found. Please log in.']);
    exit();
}

// Determine which ID to use based on account type
$user_id = null;
$purchaser_tutor_id = null;

if ($user['auth_source'] === 'users_tbl') {
    $user_id = $user['id'];
} else if ($user['auth_source'] === 'tutors') {
    $purchaser_tutor_id = $user['id'];
} else {
    echo json_encode(['success' => false, 'error' => 'Unsupported account type for purchases.']);
    exit();
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit();
}

// Re-calculate total
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

mysqli_begin_transaction($con);

try {
    $order_id = null;
    $transaction_id = $razorpay_payment_id;
    $payment_method = 'razorpay';

    // Standard strict insertion: one of the IDs must be filled
    $stmt = $con->prepare("INSERT INTO orders (user_id, purchaser_tutor_id, total_amount, payment_status, transaction_id, payment_method, shipping_address) VALUES (?, ?, ?, 'completed', ?, ?, ?)");
    $stmt->bind_param('iidsss', $user_id, $purchaser_tutor_id, $total_amount, $transaction_id, $payment_method, $full_shipping_address);
    
    if (!$stmt->execute()) {
        throw new Exception("Order record failed: " . $stmt->error);
    }
    
    $order_id = $con->insert_id;

    if (!$order_id) {
        throw new Exception("Failed to retrieve new order ID.");
    }

    // Insert into order_items and update stock
    foreach ($cart_items as $item) {
        // Detect if column is qty or quantity
        $check_col = mysqli_query($con, "SHOW COLUMNS FROM order_items LIKE 'quantity'");
        $col_name = (mysqli_num_rows($check_col) > 0) ? 'quantity' : 'qty';

        $oi_sql = "INSERT INTO order_items (order_id, product_id, $col_name, price) VALUES (?, ?, ?, ?)";
        $oi_stmt = $con->prepare($oi_sql);
        $oi_stmt->bind_param('iiid', $order_id, $item['id'], $item['qty'], $item['price']);
        $oi_stmt->execute();
        
        $stock_stmt = $con->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stock_stmt->bind_param('ii', $item['qty'], $item['id']);
        $stock_stmt->execute();
    }

    mysqli_commit($con);
    unset($_SESSION['cart']);
    
    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Throwable $e) {
    mysqli_rollback($con);
    error_log("Razorpay Critical Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Order processing failed: ' . $e->getMessage()]);
}
?>
