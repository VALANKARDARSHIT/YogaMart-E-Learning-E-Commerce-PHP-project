<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];

// Table maintenance: Ensure premium_members table exists
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'premium_members'");
if (mysqli_num_rows($table_check) == 0) {
    $create_sql = "CREATE TABLE `premium_members` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `tutor_id` INT(11) NOT NULL,
        `plan_name` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `transaction_id` VARCHAR(255) NOT NULL,
        `start_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `end_date` TIMESTAMP,
        INDEX idx_premium_members_userid (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users_tbl`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`tutor_id`) REFERENCES `tutors`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    mysqli_query($con, $create_sql);
}

// Fetch subscribers for this tutor
$query = "SELECT pm.*, u.username, u.email 
          FROM premium_members pm 
          JOIN users_tbl u ON pm.user_id = u.id 
          WHERE pm.tutor_id = ? 
          ORDER BY pm.start_date DESC";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$subscribers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscribers - Tutor Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-panel.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="admin-panel">
    <!-- Mobile Toggle Button (Floating) -->
    <button class="sidebar-toggle-mobile" id="mobileSidebarToggle" onclick="document.getElementById('sidebarToggle').click()">
        <i class="fas fa-bars"></i>
    </button>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="dashboardMain">
        <main class="tutor-content">
            <div class="card">
                <header class="header">
                    <h1 class="page-title">My Subscribers</h1>
                </header>
                <div class="content">
                    <table class="plans-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #eee;">
                                <th style="padding: 15px;">User</th>
                                <th style="padding: 15px;">Plan</th>
                                <th style="padding: 15px;">Amount</th>
                                <th style="padding: 15px;">Status</th>
                                <th style="padding: 15px;">Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscribers)): ?>
                                <tr>
                                    <td colspan="5" style="padding: 30px; text-align: center; color: #888;">No subscribers yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subscribers as $sub): 
                                    $is_active = strtotime($sub['end_date']) > time();
                                ?>
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td style="padding: 15px;">
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($sub['username']); ?></div>
                                            <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($sub['email']); ?></div>
                                        </td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($sub['plan_name']); ?></td>
                                        <td style="padding: 15px;">₹<?php echo number_format($sub['amount'], 2); ?></td>
                                        <td style="padding: 15px;">
                                            <span class="badge" style="background: <?php echo $is_active ? '#d4edda; color: #155724;' : '#f8d7da; color: #721c24;'; ?>">
                                                <?php echo $is_active ? 'Active' : 'Expired'; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px;"><?php echo date('M d, Y', strtotime($sub['end_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>

<script src="../assets/js/admin-sidebar.js"></script>
</body>
</html>
