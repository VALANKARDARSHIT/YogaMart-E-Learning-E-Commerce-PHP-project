<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/init.php';

// Check if user is an admin using the standard session helper
if (!isAdmin()) {
    exit('<div style="padding:20px; color:red;">Unauthorized Access. Please re-login as admin.</div>');
}

include '../includes/connect.php';
include '../includes/shop_admin_functions.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$order_id) exit('<div style="padding:20px; color:red;">Invalid Order ID provided.</div>');

try {
    // Fetch order details with customer info - using standard mysqli for maximum compatibility
    $sql = "SELECT o.*, u.username, u.email as customer_email 
            FROM orders o 
            LEFT JOIN users_tbl u ON o.user_id = u.id 
            WHERE o.id = $order_id";
    
    $order_query = mysqli_query($con, $sql);
    if (!$order_query) {
        throw new Exception("Database Error: " . mysqli_error($con));
    }
    
    $order_details = mysqli_fetch_assoc($order_query);

    if (!$order_details) {
        exit('<div style="padding:20px; color:orange;">Order #'.$order_id.' not found in database.</div>');
    }

    $items = getOrderItems($con, $order_id);
} catch (Exception $e) {
    exit('<div style="padding:20px; color:red;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>');
}
?>

<style>
    @media print {
        .admin-sidebar, .admin-header, .btn-print-action, .modal-footer, .site-header, .user-menu {
            display: none !important;
        }
        body { background: white !important; margin: 0; padding: 20px; }
        .order-details-wrapper { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; }
        .print-header-receipt { display: block !important; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2a5948; padding-bottom: 10px; }
    }
</style>

<div class="order-details-wrapper" style="font-family: 'Open Sans', sans-serif; color: #333;">
    <!-- Print Only Header -->
    <div class="print-header-receipt" style="display: none;">
        <h1 style="color: #2a5948; margin: 0;">YogaMart Official Receipt</h1>
        <p style="color: #666;">Order #<?php echo $order_id; ?> | Customer Receipt</p>
    </div>

    <!-- Order Summary Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
        <div>
            <h3 style="margin: 0; color: #2a5948; font-family: 'Playfair Display', serif; font-size: 1.5rem;">Order #<?php echo $order_id; ?></h3>
            <p style="margin: 5px 0 0; color: #888; font-size: 0.9rem;">
                <i class="far fa-calendar-alt"></i> <?php echo date('F d, Y \a\t h:i A', strtotime($order_details['created_at'])); ?>
            </p>
        </div>
        <div style="text-align: right;" class="btn-print-action">
            <button onclick="window.print();" style="background: #f0f0f0; border: 1px solid #ddd; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: 600; color: #444; margin-right: 10px;">
                <i class="fas fa-print"></i> Print/Download Receipt
            </button>
            <span style="display: inline-block; padding: 6px 15px; border-radius: 20px; background: #e8f6ef; color: #2ecc71; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; border: 1px solid #d1f0e4;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($order_details['payment_status']); ?>
            </span>
        </div>
    </div>

    <!-- Info Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
        <!-- Customer & Shipping -->
        <div style="background: #f9fbf9; padding: 20px; border-radius: 12px; border: 1px solid #e0e9e3;">
            <h4 style="margin: 0 0 15px 0; color: #2a5948; font-size: 1rem; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                <i class="fas fa-user"></i> Customer & Shipping
            </h4>
            <p style="margin: 0 0 10px 0;"><strong>Customer:</strong> <?php echo htmlspecialchars($order_details['username']); ?></p>
            <p style="margin: 0 0 10px 0;"><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email']); ?></p>
            <div style="margin-top: 15px;">
                <p style="margin: 0 0 5px 0; color: #666; font-size: 0.85rem; font-weight: 600;">SHIPPING ADDRESS:</p>
                <p style="margin: 0; line-height: 1.5; color: #444; background: #fff; padding: 10px; border-radius: 8px; border: 1px solid #eee;">
                    <?php echo nl2br(htmlspecialchars(($order_details['shipping_address'] ?? '') ?: 'No address provided')); ?>
                </p>
            </div>
        </div>

        <!-- Payment Details -->
        <div style="background: #f9fbf9; padding: 20px; border-radius: 12px; border: 1px solid #e0e9e3;">
            <h4 style="margin: 0 0 15px 0; color: #2a5948; font-size: 1rem; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                <i class="fas fa-credit-card"></i> Payment Details
            </h4>
            <p style="margin: 0 0 10px 0;"><strong>Method:</strong> <?php echo ucfirst(htmlspecialchars(($order_details['payment_method'] ?? '') ?: 'razorpay')); ?></p>
            <p style="margin: 0 0 10px 0;"><strong>Transaction ID:</strong></p>
            <code style="display: block; background: #fff; padding: 8px; border-radius: 6px; border: 1px solid #eee; font-size: 0.85rem; color: #e67e22; word-break: break-all;">
                <?php echo htmlspecialchars(($order_details['transaction_id'] ?? '') ?: 'N/A'); ?>
            </code>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: #666;">Total Paid:</span>
                <span style="font-size: 1.4rem; font-weight: 800; color: #2a5948;">₹<?php echo number_format($order_details['total_amount'], 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div style="border: 1px solid #eee; border-radius: 12px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
            <thead>
                <tr style="background: #2a5948; color: white;">
                    <th style="padding: 15px;">Product Item</th>
                    <th style="padding: 15px; text-align: center;">Price</th>
                    <th style="padding: 15px; text-align: center;">Qty</th>
                    <th style="padding: 15px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal_sum = 0;
                foreach ($items as $item): 
                    $qty = $item['quantity'] ?? $item['qty'] ?? 0;
                    $subtotal = $qty * $item['price'];
                    $subtotal_sum += $subtotal;
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px;">
                        <div style="font-weight: 600; color: #4a5d4e;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <small style="color: #999;">PID: #<?php echo $item['product_id']; ?></small>
                    </td>
                    <td style="padding: 15px; text-align: center;">₹<?php echo number_format($item['price'], 2); ?></td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="display: inline-block; padding: 2px 10px; background: #eee; border-radius: 10px; font-weight: 600;"><?php echo $qty; ?></span>
                    </td>
                    <td style="padding: 15px; text-align: right; font-weight: 600;">₹<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot style="background: #f9fbf9;">
                <tr>
                    <td colspan="3" style="padding: 15px; text-align: right; font-weight: 700; color: #666;">Grand Total:</td>
                    <td style="padding: 15px; text-align: right; font-weight: 800; color: #2a5948; font-size: 1.2rem;">₹<?php echo number_format($order_details['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

