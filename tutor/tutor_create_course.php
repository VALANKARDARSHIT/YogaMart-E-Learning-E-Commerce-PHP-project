<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];

// Security & Integrity: Ensure the user exists in the 'tutors' table
$verify_tutor = mysqli_query($con, "SELECT id FROM tutors WHERE id = '$tutor_id'");
$is_valid_tutor = (mysqli_num_rows($verify_tutor) > 0);

$message = '';
$can_create = true;

if (!$is_valid_tutor) {
    $message = "Error: Your account is not registered as a Tutor. Only verified tutor accounts can create courses. If you are an Admin, please register a Tutor account first.";
    $can_create = false;
}

// Check if tutor has any subscription plans
$plan_check = mysqli_query($con, "SELECT COUNT(*) as plan_count FROM subscription_plans WHERE tutor_id = '$tutor_id'");
$plan_data = mysqli_fetch_assoc($plan_check);
$has_plans = ($plan_data && $plan_data['plan_count'] > 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_create) {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $level = mysqli_real_escape_string($con, $_POST['level']);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    $thumbnail = '';

    // Validation: If trying to create a premium course without a plan
    if (!$is_free && !$has_plans) {
        $message = "Error: You must create at least one subscription plan before you can add premium (paid) courses. <a href='tutor_plans.php' style='color:inherit; text-decoration:underline;'>Manage Plans here</a>.";
    } else {
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $target_dir = "../content/courses/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $thumbnail_name = uniqid() . '_' . basename($_FILES["thumbnail"]["name"]);
            $target_file = $target_dir . $thumbnail_name;
            if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
                $thumbnail = 'content/courses/' . $thumbnail_name;
            } else {
                $message = "Sorry, there was an error uploading your thumbnail.";
            }
        }

        if (empty($message)) {
            // Price is now removed from logic, defaulting to 0.00 in DB if needed or just omitted
            $query = "INSERT INTO courses (title, description, level, price, is_free, tutor_id, thumbnail) VALUES (?, ?, ?, 0.00, ?, ?, ?)";
            $stmt = $con->prepare($query);
            $stmt->bind_param('sssiis', $title, $description, $level, $is_free, $tutor_id, $thumbnail);
            
            if ($stmt->execute()) {
                setMessage("Course created successfully!", "success");
                header("Location: tutor_courses.php");
                exit();
            } else {
                $message = "Error creating course: " . $stmt->error;
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
    <title>Create Course - Tutor Panel</title>
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
                    <h1 class="page-title">Create a New Yoga Course</h1>
                    <a href="tutor_courses.php" class="btn"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
                </header>
                <div class="content">
                    <?php if (!empty($message)) : ?>
                        <div class="alert <?php echo (strpos($message, 'created successfully') !== false) ? 'alert-success' : 'alert-warning'; ?>">
                            <i class="fas fa-info-circle"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data" id="courseForm">
                        <div class="form-group">
                            <label for="title">Course Title</label>
                            <input type="text" name="title" id="title" placeholder="e.g., Morning Vinyasa Flow" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Course Description</label>
                            <textarea name="description" id="description" rows="5" placeholder="Provide a comprehensive description of what students will achieve." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="level">Difficulty Level</label>
                                <select name="level" id="level">
                                    <option value="beginner">Beginner Friendly</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced Practice</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="background: #f9fbf8; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                                <input type="checkbox" name="is_free" id="is_free" style="width: auto;" <?php echo (isset($_POST['is_free']) || !isset($_POST['title'])) ? 'checked' : ''; ?>>
                                <span style="font-weight: 600; color: var(--primary-color);">This is a FREE course</span>
                            </label>
                            <p style="font-size: 0.85rem; color: #666; margin-top: 5px; margin-left: 25px;">If unchecked, only your premium subscribers can access this course.</p>
                        </div>

                        <div class="form-group">
                            <label for="thumbnail">Course Cover Image</label>
                            <input type="file" name="thumbnail" id="thumbnail" accept="image/*">
                            <small style="color: #666;">High-quality landscape images work best.</small>
                        </div>

                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1rem;">
                                <i class="fas fa-plus-circle"></i> Create Professional Course
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
