<?php
require_once '../includes/init.php';
include '../includes/connect.php';

$cart_items = [];
$total_amount = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $res = mysqli_query($con, "SELECT * FROM products WHERE id IN ($ids)");
    while ($product = mysqli_fetch_assoc($res)) {
        $product['qty'] = $_SESSION['cart'][$product['id']];
        $product['subtotal'] = $product['price'] * $product['qty'];
        $total_amount += $product['subtotal'];
        $cart_items[] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - YogaMart</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .cart-table th {
            text-align: left;
            padding: 1rem;
            border-bottom: 2px solid #f0f0f0;
            color: #4a5d4e;
            font-family: 'Playfair Display', serif;
        }

        .cart-table td {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #f9f9f9;
        }

        .cart-item-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .cart-item-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
        }

        .cart-item-name {
            font-weight: 600;
            color: #2d3436;
        }

        .qty-input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }

        .remove-btn {
            color: #ff4757;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.3s;
        }

        .remove-btn:hover {
            color: #ff6b81;
        }

        .cart-summary {
            background: #fdfaf0;
            padding: 2rem;
            border-radius: 15px;
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-size: 1.2rem;
            color: #4a5d4e;
            font-weight: 500;
        }

        .total-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--brand);
        }

        .checkout-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }

        .btn-continue {
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
        }

        .btn-checkout {
            background: var(--brand);
            color: white;
            text-decoration: none;
            padding: 1rem 3rem;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            transition: all 0.3s;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .empty-cart {
            text-align: center;
            padding: 4rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="container">
        <div class="cart-container">
            <h1 style="font-family: 'Playfair Display', serif; margin-bottom: 2rem; color: #4a5d4e;">Your Shopping Cart</h1>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket" style="font-size: 4rem; color: #ddd; margin-bottom: 1.5rem;"></i>
                    <h3>Your cart is empty</h3>
                    <p class="muted">Looks like you haven't added anything yet.</p>
                    <a href="shop.php" class="btn btn--primary" style="margin-top: 2rem;">Browse Shop</a>
                </div>
            <?php else: ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="cart-item-info">
                                        <?php 
                                        $imgPath = $item['image'];
                                        // Handle path if it's relative to root but we are in shop/
                                        if (empty($imgPath) || !file_exists($imgPath)) {
                                            if (!empty($imgPath) && file_exists('../' . $imgPath)) {
                                                $imgPath = '../' . $imgPath;
                                            } else {
                                                $imgPath = '../assets/images/img.avif'; // fallback
                                            }
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imgPath); ?>" class="cart-item-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <span class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <input type="number" class="qty-input" value="<?php echo $item['qty']; ?>" 
                                           onchange="updateQty(<?php echo $item['id']; ?>, this.value)" min="1" max="<?php echo $item['stock']; ?>">
                                </td>
                                <td style="font-weight: 600;">₹<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-summary">
                    <div class="total-label">Grand Total</div>
                    <div class="total-value">₹<?php echo number_format($total_amount, 2); ?></div>
                </div>

                <div class="checkout-actions">
                    <a href="shop.php" class="btn-continue">Continue Shopping</a>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function updateQty(id, qty) {
            fetch('shop_cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update&id=${id}&qty=${qty}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update header cart count
                    if (typeof updateCartCount === 'function' && data.cart_count !== undefined) {
                        updateCartCount(data.cart_count);
                    }
                    // For the cart page itself, we still need to update totals.
                    // Instead of full reload, we can just reload the main content or the window.
                    // For now, reload is acceptable here, but adding a product shouldn't.
                    window.location.reload(); 
                }
            });
        }

        async function removeItem(id) {
            if (await customConfirm('Remove Item', 'Are you sure you want to remove this item from your cart?')) {
                fetch('shop_cart_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof updateCartCount === 'function' && data.cart_count !== undefined) {
                            updateCartCount(data.cart_count);
                        }
                        window.location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>
