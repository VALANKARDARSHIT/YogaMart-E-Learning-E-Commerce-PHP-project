<?php
require_once '../includes/init.php';
include '../includes/connect.php';

$order_id = intval($_GET['id'] ?? 0);
$user = getCurrentUser();

if (!$order_id || !$user) {
    header("Location: shop.php");
    exit();
}

// Fetch order
$stmt = $con->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order_res = $stmt->get_result();
$order = $order_res->fetch_assoc();

if (!$order) {
    header("Location: shop.php");
    exit();
}

// Verify ownership or access rights
// Allow access if: 
// 1. User is the owner
// 2. Order has no owner (fallback case for tutors/admins)
// 3. User is an admin
$is_owner = ($order['user_id'] !== null && $order['user_id'] == $user['id']);
$is_fallback_case = ($order['user_id'] === null);
$is_admin = ($user['role'] === 'admin');

if (!$is_owner && !$is_fallback_case && !$is_admin) {
    header("Location: shop.php");
    exit();
}

// Fetch items - using a more robust join and fetch method
$items = [];
$items_query = "SELECT oi.*, p.name as prod_name, p.image as prod_img 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$items_stmt = $con->prepare($items_query);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_res = $items_stmt->get_result();
while ($row = $items_res->fetch_assoc()) {
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - YogaMart</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <style>
        .conf-container {
            max-width: 800px;
            margin: 3rem auto;
            text-align: center;
            background: white;
            padding: 3rem 2rem;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
        }

        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: #2ecc71;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            box-shadow: 0 10px 20px rgba(46, 204, 113, 0.2);
            animation: scaleUp 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleUp {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .order-card {
            background: #fdfaf0;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2.5rem;
            text-align: left;
            border: 1px solid #f0e6d2;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 1.5rem;
            border-bottom: 1px dashed #dcd3be;
            margin-bottom: 1.5rem;
        }

        .order-info h4 { margin: 0 0 5px 0; color: #4a5d4e; font-size: 1.1rem; }
        .order-info p { margin: 0; color: #888; font-size: 0.9rem; }

        .payment-tag {
            background: #e8f6ef;
            color: #2ecc71;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 2rem;
        }

        .detail-block h5 {
            color: #999;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1px;
            margin: 0 0 8px 0;
        }

        .detail-block p {
            color: #4a5d4e;
            font-weight: 600;
            margin: 0;
            line-height: 1.4;
        }

        .items-list {
            margin-top: 2rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.03);
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .item-name {
            font-weight: 600;
            color: #4a5d4e;
        }

        .item-qty {
            color: #888;
            font-size: 0.85rem;
        }

        .item-price {
            font-weight: 700;
            color: #4a5d4e;
        }

        .total-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #4a5d4e;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-size: 1.2rem;
            font-weight: 700;
            color: #4a5d4e;
        }

        .total-amount {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--brand);
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 3rem;
        }

        .action-btn {
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: var(--brand);
            color: white;
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.2);
        }

        .btn-outline {
            background: white;
            color: #4a5d4e;
            border: 2px solid #4a5d4e;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #666;
            border: 1px solid #ddd;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }

        @media print {
            .site-header, .btn-group, footer, .success-checkmark, p[style*="margin-top: 2rem;"] {
                display: none !important;
            }
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .conf-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .order-card {
                border: 1px solid #eee;
                background: white !important;
            }
            .order-header, .details-grid, .items-list {
                page-break-inside: avoid;
            }
            h1 {
                font-size: 1.5rem !important;
                margin-top: 0 !important;
            }
        }

        @media (max-width: 600px) {
            .details-grid { grid-template-columns: 1fr; gap: 20px; }
            .order-header { flex-direction: column; gap: 15px; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="conf-container">
            <div class="success-checkmark">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 style="font-family: 'Playfair Display', serif; color: #4a5d4e; font-size: 2.5rem; margin-bottom: 10px;">Order Confirmed!</h1>
            <p style="color: #666; font-size: 1.1rem;">Thank you, <?php echo htmlspecialchars($user['username']); ?>. Your journey to wellness continues!</p>

            <div class="order-card" id="printableReceipt">
                <div style="display: none; text-align: center; margin-bottom: 20px;" class="print-only">
                    <h2 style="color: #2a5948; margin: 0;">YogaMart Receipt</h2>
                    <p style="color: #888; font-size: 0.8rem;">www.yogamart.com | Order #<?php echo $order['id']; ?></p>
                </div>

                <div class="order-header">
                    <div class="order-info">
                        <h4>Order #<?php echo $order['id']; ?></h4>
                        <p><?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="payment-tag">
                        <i class="fas fa-shield-alt"></i> <?php echo strtoupper($order['payment_status'] ?? 'PAID'); ?>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-block">
                        <h5>Customer Info</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="detail-block">
                        <h5>Shipping Address</h5>
                        <p><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?: 'Will be shared via email')); ?></p>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail-block">
                        <h5>Payment Method</h5>
                        <p>
                            <i class="fas fa-credit-card"></i> <?php echo ucfirst(htmlspecialchars($order['payment_method'] ?? 'Razorpay')); ?><br>
                            <span style="font-size: 0.75rem; color: #888; font-family: monospace;">TXN: <?php echo htmlspecialchars($order['transaction_id']); ?></span>
                        </p>
                    </div>
                </div>

                <div class="items-list">
                    <h5>Items Purchased</h5>
                    <?php if (empty($items)): ?>
                        <p style="color: #888; font-style: italic;">Item details are being processed...</p>
                    <?php else: ?>
                        <?php foreach ($items as $item): 
                            $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                        ?>
                            <div class="item-row">
                                <div class="item-info">
                                    <span class="item-name"><?php echo htmlspecialchars($item['prod_name'] ?? 'Yoga Product'); ?></span>
                                    <span class="item-qty">× <?php echo $qty; ?></span>
                                </div>
                                <div class="item-price">₹<?php echo number_format($item['price'] * $qty, 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="total-section">
                    <span class="total-label">Total Amount Paid</span>
                    <span class="total-amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <div class="btn-group">
                <button onclick="window.print();" class="action-btn btn-secondary">
                    <i class="fas fa-download"></i> Download Receipt
                </button>
                <a href="shop.php" class="action-btn btn-outline">
                    <i class="fas fa-shopping-bag"></i> Continue Shopping
                </a>
                <a href="../home.php" class="action-btn btn-primary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
            
            <p style="margin-top: 2rem; color: #999; font-size: 0.85rem;">
                A confirmation email has been sent to <strong><?php echo htmlspecialchars($user['email']); ?></strong>
            </p>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
