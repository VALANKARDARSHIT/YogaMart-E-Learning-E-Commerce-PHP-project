<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];

$query = "SELECT * FROM courses WHERE tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
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
                    <h1 class="page-title">My Courses</h1>
                    <a href="tutor_create_course.php" class="btn"><i class="fas fa-plus"></i> Add New Course</a>
                </header>
                <div class="content">
                    <div class="courses-grid">
                        <?php foreach ($courses as $course): ?>
                            <div class="course-card">
                                <div class="course-card-thumbnail">
                                    <?php 
                                    $thumb_path = $course['thumbnail'];
                                    $show_thumb = false;
                                    if (!empty($thumb_path)) {
                                        $full_path = '../' . $thumb_path;
                                        if (file_exists($full_path)) {
                                            $show_thumb = true;
                                            $thumb_url = $full_path;
                                        }
                                    }
                                    ?>
                                    <?php if ($show_thumb): ?>
                                        <img src="<?php echo htmlspecialchars($thumb_url); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-book" style="font-size: 3rem; color: #ccc; display: flex; align-items: center; justify-content: center; height: 100%;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="course-card-content">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                                    <p><strong>Level:</strong> <?php echo htmlspecialchars($course['level']); ?></p>
                                    <p><strong>Price:</strong> <?php echo ($course['is_free'] ?? true) ? 'Free' : '$' . htmlspecialchars($course['price'] ?? '0.00'); ?></p>
                                </div>
                                <div class="course-card-actions">
                                    <a href="tutor_edit_course.php?id=<?php echo $course['id']; ?>" class="btn">Edit</a>
                                    <a href="tutor_delete_course.php?id=<?php echo $course['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

