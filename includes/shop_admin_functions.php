<?php
// shop_admin_functions.php

// --- Categories ---
function getCategories($con) {
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = mysqli_query($con, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function addCategory($con, $name) {
    $sql = "INSERT INTO categories (name) VALUES (?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('s', $name);
    return $stmt->execute();
}

function updateCategory($con, $id, $name) {
    $sql = "UPDATE categories SET name = ? WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('si', $name, $id);
    return $stmt->execute();
}

function deleteCategory($con, $id) {
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

// --- Products ---
function getProducts($con, $limit = 0) {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.id DESC";
    if ($limit > 0) {
        $sql .= " LIMIT " . (int)$limit;
    }
    $result = mysqli_query($con, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function addProduct($con, $name, $description, $price, $image, $stock, $category_id, $multiple_images = null) {
    $sql = "INSERT INTO products (name, description, price, image, stock, category_id, multiple_images) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('ssdsiis', $name, $description, $price, $image, $stock, $category_id, $multiple_images);
    return $stmt->execute();
}

function updateProduct($con, $id, $name, $description, $price, $stock, $category_id, $image = null, $multiple_images = null) {
    $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?";
    $params = [$name, $description, $price, $stock, $category_id];
    $types = 'ssdii';
    
    if ($image) {
        $sql .= ", image = ?";
        $params[] = $image;
        $types .= 's';
    }

    if ($multiple_images !== null) {
        $sql .= ", multiple_images = ?";
        $params[] = $multiple_images;
        $types .= 's';
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    return $stmt->execute();
}

function deleteProduct($con, $id) {
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

// --- Orders ---
function getOrders($con) {
    $sql = "SELECT o.*, u.username 
            FROM orders o 
            JOIN users_tbl u ON o.user_id = u.id 
            ORDER BY o.created_at DESC";
    $result = mysqli_query($con, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getOrderItems($con, $order_id) {
    $sql = "SELECT oi.*, p.name as product_name 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalSales($con) {
    $sql = "SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'completed'";
    $result = mysqli_query($con, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?: 0;
}

function getTotalProducts($con) {
    $sql = "SELECT COUNT(*) as count FROM products";
    $result = mysqli_query($con, $sql);
    return mysqli_fetch_assoc($result)['count'];
}

function getTotalOrders($con) {
    $sql = "SELECT COUNT(*) as count FROM orders";
    $result = mysqli_query($con, $sql);
    return mysqli_fetch_assoc($result)['count'];
}
?>