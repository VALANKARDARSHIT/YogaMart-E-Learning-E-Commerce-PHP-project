<?php
require_once '../includes/init.php';
include '../includes/connect.php';

echo "<h1>Shop Debug</h1>";

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully<br>";

// Check products table
$res = mysqli_query($con, "SHOW COLUMNS FROM products");
if (!$res) {
    echo "Error: products table might be missing or other error: " . mysqli_error($con) . "<br>";
} else {
    echo "<h3>Products table columns:</h3><ul>";
    $columns = [];
    while ($row = mysqli_fetch_assoc($res)) {
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
        $columns[] = $row['Field'];
    }
    echo "</ul>";
    
    if (!in_array('description', $columns)) echo "<b style='color:red'>MISSING: description column</b><br>";
    if (!in_array('multiple_images', $columns)) echo "<b style='color:red'>MISSING: multiple_images column</b><br>";
}

// Check product_images table
$res = mysqli_query($con, "SHOW TABLES LIKE 'product_images'");
if (mysqli_num_rows($res) == 0) {
    echo "<b style='color:red'>MISSING: product_images table</b><br>";
} else {
    echo "<b style='color:green'>product_images table EXISTS</b><br>";
}

// Check if there are any products
$res = mysqli_query($con, "SELECT id, name FROM products LIMIT 5");
if ($res) {
    echo "<h3>Sample products:</h3><ul>";
    while ($row = mysqli_fetch_assoc($res)) {
        echo "<li>ID: " . $row['id'] . " - Name: " . $row['name'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error fetching products: " . mysqli_error($con) . "<br>";
}
?>
