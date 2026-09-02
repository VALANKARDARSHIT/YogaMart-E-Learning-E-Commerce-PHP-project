<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];

// Fetch all tutorial videos uploaded by the current tutor
$query = "SELECT * FROM tutorial_videos WHERE tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$tutorials = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tutorials[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tutorial Videos</title>
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
    <!-- Mobile Toggle Button (Floating) -->
    <button class="sidebar-toggle-mobile" id="mobileSidebarToggle" onclick="document.getElementById('sidebarToggle').click()">
        <i class="fas fa-bars"></i>
    </button>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="dashboardMain">
        <main class="tutor-content" id="tutorContent">
            <div class="card">
                <header class="header">
                    <h1 class="page-title">My Tutorial Videos</h1>
                    <a href="tutor_add_tutorial.php" class="btn">Add New Tutorial</a>
                </header>
                <div class="content">
                    <div class="tutorials-grid">
                        <?php if (empty($tutorials)): ?>
                            <p>You haven't uploaded any tutorial videos yet.</p>
                        <?php else: ?>
                            <?php foreach ($tutorials as $tutorial): ?>
                                <div class="tutorial-card">
                                    <div class="tutorial-card-thumbnail">
                                        <?php 
                                        $t_thumb = $tutorial['thumbnail'] ?? '';
                                        $show_t_thumb = false;
                                        if (!empty($t_thumb)) {
                                            $t_full_path = '../' . $t_thumb;
                                            if (file_exists($t_full_path)) {
                                                $show_t_thumb = true;
                                                $t_thumb_url = $t_full_path;
                                            }
                                        }
                                        ?>
                                        <?php if ($show_t_thumb): ?>
                                            <img src="<?php echo htmlspecialchars($t_thumb_url); ?>" alt="<?php echo htmlspecialchars($tutorial['title']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-video" style="font-size: 3rem; color: #ccc; display: flex; align-items: center; justify-content: center; height: 100%;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tutorial-card-content">
                                        <h3><?php echo htmlspecialchars($tutorial['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($tutorial['description']); ?></p>
                                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($tutorial['duration']); ?></p>
                                        <p><strong>Status:</strong> <?php echo $tutorial['is_free'] ? 'Free' : 'Premium'; ?></p>
                                    </div>
                                    <div class="tutorial-card-actions">
                                        <a href="tutor_edit_tutorial.php?id=<?php echo $tutorial['id']; ?>" class="btn">Edit</a>
                                        <a href="tutor_delete_tutorial.php?id=<?php echo $tutorial['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this tutorial?');">Delete</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>

    <script src="../assets/js/admin-sidebar.js"></script>
</body>
</html>

