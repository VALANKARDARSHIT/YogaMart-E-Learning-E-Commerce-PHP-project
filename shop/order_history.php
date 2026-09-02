<?php
require_once '../includes/init.php';
include '../includes/connect.php';

requireLogin();
$user = getCurrentUser();

// Fetch orders for the current user
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History - YogaMart</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .history-container {
            max-width: 1000px;
            margin: 4rem auto;
            padding: 0 20px;
        }
        .page-title {
            font-family: 'Playfair Display', serif;
            color: #2a5948;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .order-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .order-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            align-items: center;
            gap: 30px;
            transition: transform 0.2s;
        }
        .order-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .order-icon {
            width: 50px;
            height: 50px;
            background: #e8f6ef;
            color: #2a5948;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .order-meta h4 {
            margin: 0 0 5px 0;
            color: #444;
        }
        .order-meta p {
            margin: 0;
            color: #888;
            font-size: 0.9rem;
        }
        .order-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-paid { background: #e8f6ef; color: #2ecc71; }
        .status-pending { background: #fff4e6; color: #f39c12; }
        .order-total {
            font-weight: 800;
            color: #2a5948;
            font-size: 1.2rem;
        }
        .btn-view {
            color: var(--brand);
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .no-orders {
            text-align: center;
            padding: 5rem 2rem;
            background: #f9fbf8;
            border-radius: 20px;
            border: 2px dashed #ccc;
        }
        @media (max-width: 768px) {
            .order-item {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            .order-total, .btn-view {
                text-align: right;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="history-container">
        <h1 class="page-title"><i class="fas fa-history"></i> My Order History</h1>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-shopping-basket" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders in our shop yet. Start your wellness journey today!</p>
                <a href="shop.php" class="btn" style="display: inline-block; margin-top: 20px; background: var(--brand); color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none;">Go to Shop</a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-item">
                        <div class="order-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="order-meta">
                            <h4>Order #<?php echo $order['id']; ?></h4>
                            <p><?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div>
                            <span class="order-status <?php echo (strtolower($order['payment_status'] ?? 'paid') == 'paid') ? 'status-paid' : 'status-pending'; ?>">
                                <?php echo htmlspecialchars($order['payment_status'] ?? 'PAID'); ?>
                            </span>
                        </div>
                        <div class="order-total">
                            ₹<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                        <a href="order_confirmation.php?id=<?php echo $order['id']; ?>" class="btn-view">
                            View Details <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
