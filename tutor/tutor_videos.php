<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];

$query = "SELECT cv.*, c.title as course_title FROM course_videos cv JOIN courses c ON cv.course_id = c.id WHERE c.tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$videos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $videos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Videos</title>
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
                    <h1 class="page-title">My Course Videos</h1>
                    <a href="tutor_upload_video.php" class="btn"><i class="fas fa-plus"></i> Upload New Video</a>
                </header>
                
                <div class="content">
                    <div class="videos-grid">
                        <?php if (empty($videos)): ?>
                            <p>You haven't uploaded any course videos yet.</p>
                        <?php else: ?>
                            <?php foreach ($videos as $video): ?>
                                <div class="video-card">
                                    <div class="video-card-thumbnail">
                                        <?php 
                                        $v_thumb = $video['thumbnail'] ?? '';
                                        $show_v_thumb = false;
                                        if (!empty($v_thumb)) {
                                            $v_full_path = '../' . $v_thumb;
                                            if (file_exists($v_full_path)) {
                                                $show_v_thumb = true;
                                                $v_thumb_url = $v_full_path;
                                            }
                                        }
                                        ?>
                                        <?php if ($show_v_thumb): ?>
                                            <img src="<?php echo htmlspecialchars($v_thumb_url); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-play-circle" style="font-size: 3rem; color: #ccc; display: flex; align-items: center; justify-content: center; height: 100%;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="video-card-content">
                                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                                        <p><?php echo htmlspecialchars(substr($video['description'], 0, 100)) . (strlen($video['description']) > 100 ? '...' : ''); ?></p>
                                        <p style="margin-top: 8px; font-size: 0.9rem; color: var(--brand); font-weight: 500;">
                                            <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($video['course_title']); ?>
                                        </p>
                                    </div>
                                    <div class="video-card-actions">
                                        <a href="tutor_edit_video.php?id=<?php echo $video['id']; ?>" class="btn">Edit</a>
                                        <a href="tutor_delete_video.php?id=<?php echo $video['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this video?');">Delete</a>
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

