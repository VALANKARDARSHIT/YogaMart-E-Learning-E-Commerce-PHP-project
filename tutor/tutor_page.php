<?php
include '../includes/init.php';

// Redirect if not a tutor
$current_user = getCurrentUser();
if ($current_user === null || $current_user['role'] !== 'tutor') {
    header("Location: ../home.php");
    exit();
}

// Redirect to the actual dashboard
header("Location: tutor_dashboard.php");
exit();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-panel.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="admin-panel">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="dashboardMain">
        <main class="tutor-content" id="tutorContent">
            <div class="card">
                <header class="header">
                    <h1 class="page-title">Welcome to your Dashboard</h1>
                </header>
                <div class="content">
                    <p>Here you can manage your courses, videos, and view your tutor details.</p>
                    <p>Use the menu on the left to navigate through the different sections.</p>
                </div>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>

    <script src="../assets/js/admin-sidebar.js"></script>
</body>
</html>

