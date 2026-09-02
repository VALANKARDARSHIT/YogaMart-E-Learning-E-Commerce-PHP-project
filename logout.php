<?php
// Enhanced logout with secure session destruction
require_once 'includes/init.php';

// Get user info before logout for goodbye message
$user = getCurrentUser();
$username = $user ? $user['username'] : 'User';

// Completely destroy the session
destroyUserSession();

// Set a logout message
$_SESSION['logout_message'] = "Goodbye, $username! You have been securely logged out.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header class="site-header">
        <div class="container nav">
            <a class="brand" href="home.php" aria-label="YogaMart home">
                <span class="sun"></span> YogaMart
            </a>
            <!-- <nav class="nav-links">
                <a href="home.php">Home</a>
                <a href="classes.php">Classes</a>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </nav> -->
        </div>
    </header>

    <main class="container loggedout-page">
        <div class="message-box">
            <h1>You have been logged out</h1>
            <p>Thank you for visiting YogaMart.</p>
            <a href="login-registration.php" class="btn">Login Again</a>
            <a href="home.php" class="btn secondary">Go to Home</a>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date("Y"); ?> YogaMart. All rights reserved.</p>
    </footer>
</body>
</html>


