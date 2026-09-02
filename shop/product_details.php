<?php
require_once '../includes/init.php';
include '../includes/connect.php';

$product_id = $_GET['id'] ?? 0;
$product_id = (int)$product_id;

if (!$product_id) {
    header("Location: shop.php");
    exit();
}

// Fetch product details
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?";
$stmt = $con->prepare($sql);
if (!$stmt) {
    // If the query fails, likely due to missing columns/tables
    error_log("Product details query failed: " . $con->error);
    header("Location: shop.php?error=db_error");
    exit();
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: shop.php");
    exit();
}

// Fetch multiple images for this product
$extra_images_table = [];
try {
    $images_sql = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id ASC";
    $images_stmt = $con->prepare($images_sql);
    if ($images_stmt) {
        $images_stmt->bind_param("i", $product_id);
        $images_stmt->execute();
        $res = $images_stmt->get_result();
        if ($res) {
            $extra_images_table = $res->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    // Table might be missing, just log and continue with empty array
    error_log("Note: product_images table access failed (might be missing): " . $e->getMessage());
} catch (Error $e) {
    error_log("Note: product_images table access error: " . $e->getMessage());
}

// Combine main image with extra images for the gallery
$all_images = [];
// Main image always goes first
if (!empty($product['image'])) {
    $all_images[] = $product['image'];
}

// Add images from the JSON column in products table (if it exists)
if (isset($product['multiple_images']) && !empty($product['multiple_images'])) {
    $extra_from_column = json_decode($product['multiple_images'], true);
    if (is_array($extra_from_column)) {
        foreach ($extra_from_column as $img_path) {
            if (!in_array($img_path, $all_images)) {
                $all_images[] = $img_path;
            }
        }
    }
}

// Add images from the separate product_images table
foreach ($extra_images_table as $img) {
    if (!in_array($img['image_path'], $all_images)) {
        $all_images[] = $img['image_path'];
    }
}

// Fetch related products (same category)
$related_sql = "SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4";
$related_stmt = $con->prepare($related_sql);
$related_stmt->bind_param("ii", $product['category_id'], $product_id);
$related_stmt->execute();
$related_products = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - YogaMart</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --earth-green: #4a5d4e;
            --soft-clay: #d4a373;
            --cream: #fefae0;
            --moss: #606c38;
            --sage: #ccd5ae;
            --brand: #4a7c59;
        }

        body {
            background-color: #f8f9fa;
        }

        .product-details-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .breadcrumb {
            margin-bottom: 1.5rem;
            color: #777;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--brand);
            text-decoration: none;
        }

        /* Gallery Section at the TOP */
        .product-gallery-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        }

        .main-image-container {
            width: 100%;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
            border-radius: 20px;
            position: relative;
        }

        .main-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: opacity 0.3s;
        }

        .thumbnail-strip {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: thin;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
            overflow: hidden;
            flex-shrink: 0;
            transition: all 0.2s;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .thumbnail:hover, .thumbnail.active {
            border-color: var(--brand);
            transform: scale(1.05);
        }

        .thumbnail img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Info Grid Layout */
        .product-main-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 2rem;
        }

        .info-card, .buy-box {
            background: white;
            padding: 2.5rem;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.04);
        }

        /* Product Identity below Image */
        .product-category-tag {
            color: var(--soft-clay);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 0.8rem;
            display: block;
        }

        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--earth-green);
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .section-label {
            font-weight: 700;
            color: var(--earth-green);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 0.5rem;
            display: block;
        }

        .description-text {
            color: #555;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        /* Buy Box Section */
        .price-display {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--brand);
            margin-bottom: 1rem;
        }

        .stock-status {
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .status-in-stock { color: #2ecc71; }
        .status-low-stock { color: #f39c12; }
        .status-out-stock { color: #e74c3c; }

        .qty-label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 700;
            color: #333;
        }

        .qty-input {
            width: 80px;
            padding: 0.8rem;
            border: 2px solid #eee;
            border-radius: 12px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .qty-input:focus { border-color: var(--brand); }

        .btn-add-cart {
            width: 100%;
            background: #f7ca00;
            color: #111;
            border: none;
            padding: 1.2rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(247, 202, 0, 0.2);
        }

        .btn-add-cart:hover { background: #f2c200; transform: translateY(-2px); }

        .btn-buy-now {
            width: 100%;
            background: #ffa41c;
            color: #111;
            border: none;
            padding: 1.2rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 164, 28, 0.2);
        }

        .btn-buy-now:hover { background: #f59600; transform: translateY(-2px); }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
        }

        .toast {
            background: white;
            padding: 1.2rem 2rem;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
            transform: translateX(120%);
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-left: 8px solid #2ecc71;
            font-weight: 600;
        }

        .toast.show { transform: translateX(0); }

        /* Related Items */
        .related-section { margin-top: 5rem; }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 2rem;
        }

        .related-card {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .related-card:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); }
        .related-img { height: 180px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; }
        .related-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .related-name { font-weight: 700; font-size: 1rem; color: var(--earth-green); margin-bottom: 0.5rem; }
        .related-price { color: var(--brand); font-weight: 800; font-size: 1.1rem; }

        @media (max-width: 900px) {
            .product-main-grid { grid-template-columns: 1fr; }
            .main-image-container { height: 350px; }
            .product-title { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div id="toast-container" class="toast-container"></div>

    <main class="container">
        <div class="product-details-container">
            <nav class="breadcrumb">
                <a href="../home.php">Home</a> / 
                <a href="shop.php">Shop</a> / 
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </nav>

            <!-- Multi-Image Gallery at the TOP -->
            <div class="product-gallery-section">
                <div class="main-image-container">
                    <?php 
                    $mainImgPath = $product['image'];
                    if (!file_exists($mainImgPath)) {
                        if (file_exists('../' . $mainImgPath)) $mainImgPath = '../' . $mainImgPath;
                        else $mainImgPath = '../assets/images/img.avif';
                    }
                    ?>
                    <img id="main-product-image" src="<?php echo htmlspecialchars($mainImgPath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                
                <?php if (!empty($all_images) && count($all_images) > 1): ?>
                    <div class="thumbnail-strip">
                        <?php foreach ($all_images as $index => $imgPath): 
                            $fullPath = $imgPath;
                            if (!file_exists($fullPath)) {
                                if (file_exists('../' . $fullPath)) $fullPath = '../' . $fullPath;
                                else $fullPath = '../assets/images/img.avif';
                            }
                        ?>
                            <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 onclick="changeImage('<?php echo htmlspecialchars($fullPath); ?>', this)">
                                <img src="<?php echo htmlspecialchars($fullPath); ?>" alt="Product image <?php echo $index + 1; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-main-grid">
                <!-- Left Section: Name and Description -->
                <div class="info-card">
                    <span class="product-category-tag"><?php echo htmlspecialchars($product['category_name'] ?: 'Essential'); ?></span>
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <span class="section-label">Description</span>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
                    </div>
                </div>

                <!-- Right Section: Buy Box -->
                <div class="buy-box">
                    <div class="price-display">
                        ₹<?php echo number_format($product['price'], 2); ?>
                    </div>

                    <div class="stock-status">
                        <?php 
                        $stock = (int)$product['stock'];
                        if ($stock <= 0) {
                            echo '<span class="status-out-stock"><i class="fas fa-times-circle"></i> Currently unavailable.</span>';
                        } elseif ($stock <= 10) {
                            echo '<span class="status-low-stock"><i class="fas fa-clock"></i> Only ' . $stock . ' left - order soon!</span>';
                        } else {
                            echo '<span class="status-in-stock"><i class="fas fa-check-circle"></i> In Stock.</span>';
                        }
                        ?>
                    </div>

                    <label class="qty-label" for="qty-input">Quantity</label>
                    <input type="number" id="qty-input" class="qty-input" value="1" min="1" max="<?php echo $stock; ?>">

                    <button class="btn-add-cart" 
                            onclick="addToCart(event, <?php echo $product['id']; ?>)"
                            <?php echo ($stock <= 0) ? 'disabled' : ''; ?>>
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>

                    <button class="btn-buy-now" 
                            onclick="buyNow(<?php echo $product['id']; ?>)"
                            <?php echo ($stock <= 0) ? 'disabled' : ''; ?>>
                        Buy Now
                    </button>

                    <div style="margin-top: 2rem; font-size: 0.95rem; color: #666; border-top: 1px solid #f0f0f0; padding-top: 1.5rem;">
                        <div style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-shield-alt" style="color: #999; width: 20px;"></i> 
                            Secure payment processing
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-truck" style="color: #999; width: 20px;"></i> 
                            Fast & reliable delivery
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Items -->
            <?php if (!empty($related_products)): ?>
                <div class="related-section">
                    <h2 class="section-label" style="font-size: 1.5rem; margin-bottom: 2rem;">Similar items you might like</h2>
                    <div class="related-grid">
                        <?php foreach ($related_products as $rp): ?>
                            <a href="product_details.php?id=<?php echo $rp['id']; ?>" class="related-card">
                                <div class="related-img">
                                    <?php 
                                    $rpImg = $rp['image'];
                                    if (!file_exists($rpImg)) {
                                        if (file_exists('../' . $rpImg)) $rpImg = '../' . $rpImg;
                                        else $rpImg = '../assets/images/img.avif';
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($rpImg); ?>" alt="<?php echo htmlspecialchars($rp['name']); ?>">
                                </div>
                                <div class="related-name"><?php echo htmlspecialchars($rp['name']); ?></div>
                                <div class="related-price">₹<?php echo number_format($rp['price'], 2); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function changeImage(src, thumb) {
            const mainImg = document.getElementById('main-product-image');
            mainImg.style.opacity = '0';
            
            setTimeout(() => {
                mainImg.src = src;
                mainImg.style.opacity = '1';
                
                // Update active thumbnail
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            }, 300);
        }

        function addToCart(event, productId) {
            const qty = document.getElementById('qty-input').value;
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            btn.disabled = true;

            fetch('shop_cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&id=${productId}&qty=${qty}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Item added to cart!', 'success');
                    }
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    btn.style.background = '#2ecc71';
                    btn.style.color = 'white';
                    
                    // Update header cart count dynamically
                    if (typeof updateCartCount === 'function' && data.cart_count !== undefined) {
                        updateCartCount(data.cart_count);
                    }

                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                        btn.style.background = '#f7ca00';
                        btn.style.color = '#111';
                    }, 2000);
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Error adding to cart', 'error');
                    } else {
                        console.error(data.message || 'Error adding to cart');
                    }
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            });
        }

        function buyNow(productId) {
            const qty = document.getElementById('qty-input').value;
            fetch('shop_cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&id=${productId}&qty=${qty}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'cart.php';
                }
            });
        }
    </script>
</body>
</html>
