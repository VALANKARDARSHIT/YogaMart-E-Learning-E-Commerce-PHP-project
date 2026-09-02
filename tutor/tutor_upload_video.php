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

// Fetch tutor's courses for the dropdown
$query_courses = "SELECT id, title FROM courses WHERE tutor_id = ?";
$stmt_courses = $con->prepare($query_courses);
$stmt_courses->bind_param('i', $tutor_id);
$stmt_courses->execute();
$result_courses = $stmt_courses->get_result();
$courses = [];
if ($result_courses) {
    while ($row = $result_courses->fetch_assoc()) {
        $courses[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_valid_tutor) {
    $title = mysqli_real_escape_string($con, $_POST['title'] ?? '');
    $description = mysqli_real_escape_string($con, $_POST['description'] ?? '');
    $course_id = $_POST['course_id'] ?? '';
    $duration = mysqli_real_escape_string($con, $_POST['duration'] ?? '00:00'); 

    // Security check: Verify that the selected course_id belongs to the logged-in tutor
    $course_check_query = "SELECT title FROM courses WHERE id = ? AND tutor_id = ?";
    $course_check_stmt = $con->prepare($course_check_query);
    $course_check_stmt->bind_param('ii', $course_id, $tutor_id);
    $course_check_stmt->execute();
    $course_result = $course_check_stmt->get_result();
    
    if ($course_result->num_rows == 0) {
        $message = "Error: You are not authorized to upload videos to this course.";
    } else {
        $course_data = $course_result->fetch_assoc();
        $course_name_folder = preg_replace('/[^a-zA-Z0-9_]/', '_', $course_data['title']);

        $video_url = '';
        if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
            $target_dir = "../content/courses/" . $course_name_folder . "/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $video_name = time() . '_' . basename($_FILES["video"]["name"]);
            $target_file = $target_dir . $video_name;
            
            if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
                $video_url = "content/courses/" . $course_name_folder . "/" . $video_name;
            } else {
                $message = "Sorry, there was an error uploading your video file.";
            }
        } else {
            $message = "An error occurred with the video upload. Please try again.";
        }

        $thumbnail_url = '';
        if (empty($message) && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $thumb_target_dir = "../content/courses/" . $course_name_folder . "/thumbnails/";
            if (!is_dir($thumb_target_dir)) {
                mkdir($thumb_target_dir, 0777, true);
            }

            $thumbnail_name = time() . '_' . basename($_FILES["thumbnail"]["name"]);
            $thumb_target_file = $thumb_target_dir . $thumbnail_name;

            if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $thumb_target_file)) {
                $thumbnail_url = "content/courses/" . $course_name_folder . "/thumbnails/" . $thumbnail_name;
            } else {
                $message = "Sorry, there was an error uploading your thumbnail image.";
            }
        } else if (empty($message)) {
            $message = "An error occurred with the thumbnail upload. Please try again.";
        }


        if (empty($message) && !empty($video_url) && !empty($thumbnail_url)) {
            $insert_query = "INSERT INTO course_videos (course_id, title, description, video_url, thumbnail, duration) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $con->prepare($insert_query);
            $insert_stmt->bind_param('isssss', $course_id, $title, $description, $video_url, $thumbnail_url, $duration);
            
            if ($insert_stmt->execute()) {
                setMessage("Video uploaded successfully!", "success");
                header("Location: tutor_videos.php");
                exit();
            } else {
                $message = "Error saving video information: " . $insert_stmt->error;
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
    <title>Upload Course Video - Tutor Panel</title>
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
                    <h1 class="page-title">Upload New Course Video</h1>
                    <a href="tutor_videos.php" class="btn"><i class="fas fa-arrow-left"></i> Back to My Videos</a>
                </header>
                <div class="content">
                    <?php if (!empty($message)) : ?>
                        <div class="alert alert-error">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
                        <input type="hidden" name="duration" id="video_duration" value="00:00">
                        <div class="form-group">
                            <label for="title">Video Title</label>
                            <input type="text" name="title" id="title" placeholder="e.g., Session 1: Sun Salutation Basics" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Video Description</label>
                            <textarea name="description" id="description" rows="4" placeholder="Briefly explain what this video covers." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="course_id">Associated Course</label>
                            <select name="course_id" id="course_id" required>
                                <option value="">-- Select the parent course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="video">Video File (MP4, MOV)</label>
                                <input type="file" name="video" id="video" accept="video/*" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="thumbnail">Video Thumbnail (JPG, PNG)</label>
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/*" required>
                            </div>
                        </div>

                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn" style="padding: 15px 40px; font-size: 1rem;">
                                <i class="fas fa-upload"></i> Upload & Link to Course
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
        document.getElementById('video').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const video = document.createElement('video');
                video.preload = 'metadata';
                video.onloadedmetadata = function() {
                    window.URL.revokeObjectURL(video.src);
                    const duration = video.duration;
                    const minutes = Math.floor(duration / 60);
                    const seconds = Math.floor(duration % 60);
                    const formattedDuration = 
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
