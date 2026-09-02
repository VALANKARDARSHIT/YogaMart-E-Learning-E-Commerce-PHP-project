<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];
$course_id = $_GET['id'] ?? null;
$message = '';

if (!$course_id) {
    header("Location: tutor_courses.php");
    exit();
}

// Fetch course data
$query = "SELECT * FROM courses WHERE id = ? AND tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('ii', $course_id, $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    header("Location: tutor_courses.php");
    exit();
}

// Check if tutor has any subscription plans
$plan_check = mysqli_query($con, "SELECT COUNT(*) as plan_count FROM subscription_plans WHERE tutor_id = '$tutor_id'");
$plan_data = mysqli_fetch_assoc($plan_check);
$has_plans = ($plan_data && $plan_data['plan_count'] > 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $level = mysqli_real_escape_string($con, $_POST['level']);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    $thumbnail = $course['thumbnail'];

    // Validation: If trying to make course premium without a plan
    if (!$is_free && !$has_plans) {
        $message = "Error: You must create at least one subscription plan before you can set a course to premium. <a href='tutor_plans.php' style='color:inherit; text-decoration:underline;'>Manage Plans here</a>.";
    } else {
        // Handle thumbnail upload
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $target_dir = "../content/courses/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Delete old thumbnail if exists
            if (!empty($thumbnail) && file_exists('../' . $thumbnail)) {
                unlink('../' . $thumbnail);
            }

            $thumbnail_name = uniqid() . '_' . basename($_FILES["thumbnail"]["name"]);
            $target_file = $target_dir . $thumbnail_name;
            if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
                $thumbnail = 'content/courses/' . $thumbnail_name;
            } else {
                $message = "Error uploading thumbnail.";
            }
        }

        if (empty($message)) {
            $update_query = "UPDATE courses SET title = ?, description = ?, level = ?, price = 0.00, is_free = ?, thumbnail = ? WHERE id = ? AND tutor_id = ?";
            $update_stmt = $con->prepare($update_query);
            $update_stmt->bind_param('sssiisi', $title, $description, $level, $is_free, $thumbnail, $course_id, $tutor_id);

            if ($update_stmt->execute()) {
                setMessage("Course updated successfully!", "success");
                header("Location: tutor_courses.php");
                exit();
            } else {
                $message = "Error updating course: " . $update_stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - Tutor Panel</title>
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
                    <h1 class="page-title">Edit Yoga Course</h1>
                    <a href="tutor_courses.php" class="btn"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
                </header>
                <div class="content">
                    <?php if (!empty($message)) : ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Course Title</label>
                            <input type="text" name="title" id="title" placeholder="e.g., Morning Vinyasa Flow" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Course Description</label>
                            <textarea name="description" id="description" rows="5" placeholder="Provide a comprehensive description of what students will achieve." required><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="level">Difficulty Level</label>
                                <select name="level" id="level">
                                    <option value="beginner" <?php echo ($course['level'] == 'beginner') ? 'selected' : ''; ?>>Beginner Friendly</option>
                                    <option value="intermediate" <?php echo ($course['level'] == 'intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo ($course['level'] == 'advanced') ? 'selected' : ''; ?>>Advanced Practice</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="background: #f9fbf8; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="is_free" id="is_free" style="width: auto;" <?php echo ($course['is_free']) ? 'checked' : ''; ?>>
                                <span style="font-weight: 600; color: var(--primary-color);">This is a FREE course</span>
                            </label>
                            <p style="font-size: 0.85rem; color: #666; margin-top: 5px; margin-left: 25px;">If unchecked, only your premium subscribers can access this course.</p>
                        </div>

                        <div class="form-group">
                            <label for="thumbnail">Update Course Cover Image (Optional)</label>
                            <input type="file" name="thumbnail" id="thumbnail" accept="image/*">
                            <?php if (!empty($course['thumbnail'])): ?>
                                <p style="margin-top: 10px; font-size: 0.85rem;">Current Image: <a href="../<?php echo htmlspecialchars($course['thumbnail']); ?>" target="_blank"><?php echo basename($course['thumbnail']); ?></a></p>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1rem;">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>

    <script src="../assets/js/admin-sidebar.js"></script>
</body>
</html>
