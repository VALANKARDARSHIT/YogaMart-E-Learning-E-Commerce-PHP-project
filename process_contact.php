<?php
require_once 'includes/init.php';
require_once 'includes/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Get form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$newsletter = isset($_POST['newsletter']) ? true : false;

// Basic validation
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

try {
    // Ensure table exists
    $createTableSql = "CREATE TABLE IF NOT EXISTS contact_submissions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        newsletter TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_read TINYINT(1) DEFAULT 0
    )";
    mysqli_query($con, $createTableSql);

    // Save to database
    $newsletterVal = $newsletter ? 1 : 0;
    $stmt = mysqli_prepare($con, "INSERT INTO contact_submissions (name, email, phone, subject, message, newsletter) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $phone, $subject, $message, $newsletterVal);
        $dbSuccess = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if ($dbSuccess) {
            echo json_encode(['status' => 'success', 'message' => 'Thank you for your message! Your request has been recorded and will be reviewed by our team.']);
        } else {
            error_log("Database execution failed: " . mysqli_error($con));
            echo json_encode(['status' => 'error', 'message' => 'Failed to save your message. Please try again.']);
        }
    } else {
        error_log("Failed to prepare statement: " . mysqli_error($con));
        echo json_encode(['status' => 'error', 'message' => 'A database error occurred.']);
    }

} catch (Exception $e) {
    error_log("Contact Process Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred.']);
}
?>