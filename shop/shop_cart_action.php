<?php
require_once '../includes/init.php';
include '../includes/connect.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$product_id = intval($_POST['id'] ?? 0);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
    exit();
}

if ($action === 'add') {
    $qty = intval($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    // Check if product exists
    $res = mysqli_query($con, "SELECT id, name, price, stock FROM products WHERE id = $product_id");
    $product = mysqli_fetch_assoc($res);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit();
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $current_qty = $_SESSION['cart'][$product_id] ?? 0;
    $new_qty = $current_qty + $qty;

    if ($new_qty <= $product['stock']) {
        $_SESSION['cart'][$product_id] = $new_qty;
        $total_count = array_sum($_SESSION['cart']);
        echo json_encode(['success' => true, 'message' => 'Product added to cart.', 'cart_count' => $total_count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Only ' . ($product['stock'] - $current_qty) . ' more items available.']);
    }
    exit();
}

if ($action === 'remove') {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $total_count = array_sum($_SESSION['cart']);
        echo json_encode(['success' => true, 'cart_count' => $total_count]);
        exit();
    }
}

if ($action === 'update') {
    $qty = intval($_POST['qty'] ?? 1);
    if ($qty > 0) {
        $_SESSION['cart'][$product_id] = $qty;
        $total_count = array_sum($_SESSION['cart']);
        echo json_encode(['success' => true, 'cart_count' => $total_count]);
        exit();
    }
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
?>
