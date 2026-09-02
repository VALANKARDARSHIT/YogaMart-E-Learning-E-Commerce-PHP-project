<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];
$verify_tutor = mysqli_query($con, "SELECT id FROM tutors WHERE id = '$tutor_id'");
$is_valid_tutor = (mysqli_num_rows($verify_tutor) > 0);
$message = '';

if (!$is_valid_tutor) {
    $message = "Error: Your account is not registered as a Tutor. Only verified tutor accounts can manage content.";
}

// Check if tutor has any subscription plans
$plan_check = mysqli_query($con, "SELECT COUNT(*) as plan_count FROM subscription_plans WHERE tutor_id = '$tutor_id'");
$plan_data = mysqli_fetch_assoc($plan_check);
$has_plans = ($plan_data && $plan_data['plan_count'] > 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_valid_tutor) {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    
    // Validation: If trying to create a premium tutorial without a plan
    if (!$is_free && !$has_plans) {
        $message = "Error: You must create at least one subscription plan before you can add premium (paid) tutorial videos. <a href='tutor_plans.php' style='color:inherit; text-decoration:underline;'>Manage Plans here</a>.";
    } else {
        // File upload for video
        $video_file_name = '';
        $video_path = '';
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == UPLOAD_ERR_OK) {
            $video_dir = '../content/tutorials/';
            if (!is_dir($video_dir)) {
                mkdir($video_dir, 0777, true);
            }
            $video_ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
            $video_file_name = uniqid() . '.' . $video_ext;
            $video_target = $video_dir . $video_file_name;
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $video_target)) {
                // Path to store in DB (relative to project root)
                $video_path = 'content/tutorials/' . $video_file_name;
            } else {
                $message = "Failed to upload video file.";
            }
        } else {
            $message = "Video file upload error: " . ($_FILES['video_file']['error'] ?? 'No file selected');
        }

        // File upload for thumbnail
        $thumbnail_file_name = '';
        $thumbnail_path = '';
        if (empty($message) && isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] == UPLOAD_ERR_OK) {
            $thumbnail_dir = '../content/tutorials/thumbnails/';
            if (!is_dir($thumbnail_dir)) {
                mkdir($thumbnail_dir, 0777, true);
            }
            $thumbnail_ext = pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION);
            $thumbnail_file_name = uniqid() . '.' . $thumbnail_ext;
            $thumbnail_target = $thumbnail_dir . $thumbnail_file_name;
            if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $thumbnail_target)) {
                // Path to store in DB (relative to project root)
                $thumbnail_path = 'content/tutorials/thumbnails/' . $thumbnail_file_name;
            } else {
                $message = "Failed to upload thumbnail file.";
            }
        }

        if (empty($message) && !empty($video_path) && !empty($thumbnail_path)) {
            $duration = mysqli_real_escape_string($con, $_POST['duration'] ?? '00:00:00'); 

            $insert_query = "INSERT INTO tutorial_videos (tutor_id, title, description, duration, url, is_free, thumbnail) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($insert_query);
            $stmt->bind_param('issssis', $tutor_id, $title, $description, $duration, $video_path, $is_free, $thumbnail_path);

            if ($stmt->execute()) {
                setMessage("Tutorial added successfully!", "success");
                header("Location: tutor_tutorials.php");
                exit();
            } else {
                $message = "Error adding tutorial: " . $stmt->error;
            }
        } elseif (empty($message)) {
            $message = "Please upload both video and thumbnail files.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Tutorial - Tutor Panel</title>
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
                    <h1 class="page-title">Add New Tutorial Video</h1>
                    <a href="tutor_tutorials.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Tutorials</a>
                </header>
                <div class="content">
                    <?php if (!empty($message)) : ?>
                        <div class="alert <?php echo !$has_plans ? 'alert-warning' : 'alert-error'; ?>">
                            <i class="fas <?php echo !$has_plans ? 'fa-exclamation-triangle' : 'fa-info-circle'; ?>"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data" id="tutorialForm">
                        <input type="hidden" name="duration" id="video_duration" value="00:00:00">
                        <div class="form-group">
                            <label for="title">Video Title</label>
                            <input type="text" name="title" id="title" placeholder="Enter a descriptive title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Detailed Description</label>
                            <textarea name="description" id="description" rows="6" placeholder="What will students learn in this video?" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="video_file">Video File (MP4, MOV)</label>
                                <input type="file" name="video_file" id="video_file" accept="video/*" required>
                                <small style="color: #666; margin-top: 5px; display: block;">Maximum file size: 50MB</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="thumbnail_file">Cover Thumbnail (JPG, PNG)</label>
                                <input type="file" name="thumbnail_file" id="thumbnail_file" accept="image/*" required>
                                <small style="color: #666; margin-top: 5px; display: block;">Recommended size: 1280x720px</small>
                            </div>
                        </div>

                        <div class="form-group" style="background: #f9fbf8; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_free" id="is_free" style="width: auto;" <?php echo isset($_POST['is_free']) ? 'checked' : ''; ?>>
                                <span style="font-weight: 600; color: var(--primary-color);">Make this video FREE for all users</span>
                            </label>
                            <p style="font-size: 0.85rem; color: #666; margin-top: 5px; margin-left: 25px;">If unchecked, only premium subscribers can view this content.</p>
                        </div>

                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1rem;">
                                <i class="fas fa-cloud-upload-alt"></i> Publish Tutorial Video
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
    <script>
        document.getElementById('video_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const video = document.createElement('video');
                video.preload = 'metadata';
                video.onloadedmetadata = function() {
                    window.URL.revokeObjectURL(video.src);
                    const duration = video.duration;
                    const hours = Math.floor(duration / 3600);
                    const minutes = Math.floor((duration % 3600) / 60);
                    const seconds = Math.floor(duration % 60);
                    const formattedDuration = 
                        (hours < 10 ? '0' : '') + hours + ':' + 
                        (minutes < 10 ? '0' : '') + minutes + ':' + 
                        (seconds < 10 ? '0' : '') + seconds;
                    document.getElementById('video_duration').value = formattedDuration;
                }
                video.src = URL.createObjectURL(file);
            }
        });
    </script>
</body>
</html>
