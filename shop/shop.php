<?php
require_once '../includes/init.php';
include '../includes/connect.php';

$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';

// Fetch categories for sidebar
$cat_res = mysqli_query($con, "SELECT * FROM categories ORDER BY name ASC");
$categories = mysqli_fetch_all($cat_res, MYSQLI_ASSOC);

// Build products query
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];
$types = "";

if ($category_filter) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($search_query) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $con->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - YogaMart</title>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --earth-green: #4a5d4e;
            --soft-clay: #d4a373;
            --cream: #fefae0;
            --moss: #606c38;
            --sage: #ccd5ae;
        }

        .shop-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
            padding: 2rem 0;
        }

        /* Sidebar Styling */
        .shop-sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            height: fit-content;
        }

        .sidebar-title {
            font-family: 'Playfair Display', serif;
            color: var(--earth-green);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            border-bottom: 2px solid var(--sage);
            padding-bottom: 0.5rem;
        }

        .category-list {
            list-style: none;
            padding: 0;
        }

        .category-list li {
            margin-bottom: 0.8rem;
        }

        .category-list a {
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s;
            display: block;
            padding: 0.5rem;
            border-radius: 8px;
        }

        .category-list a:hover, .category-list a.active {
            background: var(--sage);
            color: var(--earth-green);
            padding-left: 1rem;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f9f9f9;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-cat {
            font-size: 0.8rem;
            color: var(--soft-clay);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            color: var(--earth-green);
            margin-bottom: 0.8rem;
            line-height: 1.2;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--brand);
            margin-top: auto;
        }

        .add-to-cart-btn {
            margin-top: 1rem;
            width: 100%;
            background: var(--earth-green);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .add-to-cart-btn:hover {
            background: var(--moss);
            box-shadow: 0 5px 15px rgba(74, 93, 78, 0.3);
        }

        .add-to-cart-btn i {
            font-size: 1.1rem;
        }

        /* Search Bar */
        .search-container {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
        }

        .search-input {
            flex-grow: 1;
            padding: 1rem 1.5rem;
            border: 2px solid var(--sage);
            border-radius: 15px;
            font-size: 1rem;
            outline: none;
        }

        .search-btn {
            background: var(--brand);
            color: white;
            border: none;
            padding: 0 2rem;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Stock Badges */
        .stock-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stock-low {
            background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%);
            color: white;
            animation: pulse 2s infinite;
        }

        .stock-out {
            background: #666;
            color: white;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .sold-out-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.6);
            backdrop-filter: grayscale(1);
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
        }

        .toast {
            background: white;
            color: #333;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            min-width: 300px;
            border-left: 6px solid var(--earth-green);
            transform: translateX(120%);
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.error {
            border-left-color: #ff6b6b;
        }

        .toast i {
            font-size: 1.2rem;
        }

        .toast-success i { color: #2ecc71; }
        .toast-error i { color: #ff6b6b; }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .shop-container {
                grid-template-columns: 1fr;
            }
            .toast-container {
                bottom: 20px;
                right: 20px;
                left: 20px;
            }
            .toast {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <main class="container">
        <section class="section">
            <header class="section__head" style="text-align: center; margin-bottom: 3rem;">
                <h1 style="font-family: 'Playfair Display', serif; font-size: 3rem; color: var(--earth-green);">The Yoga Shop</h1>
                <p class="muted">Premium essentials for your mindfulness journey</p>
            </header>

            <div class="search-container">
                <form action="shop.php" method="GET" style="display: flex; width: 100%; gap: 1rem;">
                    <input type="text" name="search" class="search-input" placeholder="Search for mats, props, wellness..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php if($category_filter): ?>
                        <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                    <?php endif; ?>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>

            <div class="shop-container">
                <!-- Sidebar -->
                <aside class="shop-sidebar">
                    <h3 class="sidebar-title">Categories</h3>
                    <ul class="category-list">
                        <li>
                            <a href="shop.php" class="<?php echo !$category_filter ? 'active' : ''; ?>">All Products</a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="shop.php?category=<?php echo $cat['id']; ?>&search=<?php echo urlencode($search_query); ?>" 
                                   class="<?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>

                <!-- Products -->
                <div class="shop-content">
                    <?php if (empty($products)): ?>
                        <div style="text-align: center; padding: 5rem; background: white; border-radius: 20px;">
                            <i class="fas fa-search" style="font-size: 3rem; color: var(--sage); margin-bottom: 1rem;"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your search or category filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="product-grid">
                            <?php foreach ($products as $p): ?>
                                <div class="product-card">
                                    <a href="product_details.php?id=<?php echo $p['id']; ?>" class="product-link" style="text-decoration: none; color: inherit; display: block;">
                                        <div class="product-image">
                                            <?php 
                                            $imgPath = $p['image'];
                                            // Handle path if it's relative to root but we are in shop/
                                            if (!file_exists($imgPath)) {
                                                if (file_exists('../' . $imgPath)) {
                                                    $imgPath = '../' . $imgPath;
                                                } else {
                                                    $imgPath = '../assets/images/img.avif'; // fallback
                                                }
                                            }
                                            
                                            $stock = (int)$p['stock'];
                                            if ($stock <= 0): ?>
                                                <div class="sold-out-overlay">
                                                    <span class="stock-badge stock-out">Sold Out</span>
                                                </div>
                                            <?php elseif ($stock <= 10): ?>
                                                <span class="stock-badge stock-low">Only <?php echo $stock; ?> Left!</span>
                                            <?php endif; ?>
                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                        </div>
                                        <div class="product-content">
                                            <span class="product-cat"><?php echo htmlspecialchars($p['category_name'] ?: 'Essential'); ?></span>
                                            <h3 class="product-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                                            <div class="product-price">₹<?php echo number_format($p['price'], 2); ?></div>
                                        </div>
                                    </a>
                                    <div style="padding: 0 1.5rem 1.5rem;">
                                        <button class="add-to-cart-btn" 
                                                onclick="addToCart(event, <?php echo $p['id']; ?>)"
                                                <?php echo ($stock <= 0) ? 'disabled' : ''; ?>>
                                            <?php if ($stock <= 0): ?>
                                                <i class="fas fa-times-circle"></i> Out of Stock
                                            <?php else: ?>
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            toast.innerHTML = `
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            `;
            
            container.appendChild(toast);
            
            // Force reflow
            toast.offsetHeight;
            
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    container.removeChild(toast);
                }, 500);
            }, 4000);
        }

        function addToCart(event, productId) {
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            fetch('shop_cart_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Added to cart!', 'success');
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    btn.style.background = '#2ecc71';
                    
                    // Update header cart count dynamically
                    if (typeof updateCartCount === 'function' && data.cart_count !== undefined) {
                        updateCartCount(data.cart_count);
                    }
                    
                    setTimeout(() => {
                        btn.innerHTML = originalContent;
                        btn.style.background = '';
                        btn.disabled = false;
                    }, 2000);
                } else {
                    showToast(data.message || 'Error adding to cart', 'error');
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Something went wrong. Please try again.', 'error');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
