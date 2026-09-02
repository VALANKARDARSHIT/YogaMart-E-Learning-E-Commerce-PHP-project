<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];
$video_id = $_GET['id'] ?? null;
$message = '';

if (!$video_id) {
    header("Location: tutor_videos.php");
    exit();
}

// Fetch video data and ensure it belongs to the tutor
$query = "SELECT cv.*, c.title as course_title FROM course_videos cv JOIN courses c ON cv.course_id = c.id WHERE cv.id = ? AND c.tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('ii', $video_id, $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$video = $result->fetch_assoc();

if (!$video) {
    setMessage("Error: Video not found or unauthorized.", "error");
    header("Location: tutor_videos.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $thumbnail = $video['thumbnail'];
    $url = $video['url'];

    // Handle thumbnail upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_dir = "../content/courses/"; // Standardize directory
        $thumbnail_name = uniqid() . '_' . basename($_FILES["thumbnail"]["name"]);
        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_dir . $thumbnail_name)) {
            if (!empty($video['thumbnail']) && file_exists('../' . $video['thumbnail'])) {
                unlink('../' . $video['thumbnail']);
            }
            $thumbnail = 'content/courses/' . $thumbnail_name;
        }
    }

    // Handle video upload
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $target_dir = "../content/courses/";
        $video_name = uniqid() . '_' . basename($_FILES["video_file"]["name"]);
        if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_dir . $video_name)) {
            if (!empty($video['url']) && file_exists('../' . $video['url'])) {
                unlink('../' . $video['url']);
            }
            $url = 'content/courses/' . $video_name;
        }
    }

    $update_query = "UPDATE course_videos SET title = ?, description = ?, thumbnail = ?, url = ? WHERE id = ?";
    $update_stmt = $con->prepare($update_query);
    $update_stmt->bind_param('ssssi', $title, $description, $thumbnail, $url, $video_id);

    if ($update_stmt->execute()) {
        setMessage("Video updated successfully!", "success");
        header("Location: tutor_videos.php");
        exit();
    } else {
        $message = "Error updating video: " . $update_stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course Video - Tutor Panel</title>
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
                    <h1 class="page-title">Edit Course Video</h1>
                    <a href="tutor_videos.php" class="btn"><i class="fas fa-arrow-left"></i> Back to My Videos</a>
                </header>
                <div class="content">
                    <?php if (!empty($message)) : ?>
                        <div class="alert alert-error">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Belongs to Course</label>
                            <input type="text" value="<?php echo htmlspecialchars($video['course_title']); ?>" disabled style="background: #f0f0f0;">
                        </div>

                        <div class="form-group">
                            <label for="title">Video Title</label>
                            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($video['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Video Description</label>
                            <textarea name="description" id="description" rows="5" required><?php echo htmlspecialchars($video['description']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="video_file">Update Video File (Optional)</label>
                                <input type="file" name="video_file" id="video_file" accept="video/*">
                                <?php if (!empty($video['url'])): ?>
                                    <p style="margin-top: 10px; font-size: 0.85rem;">Current: <a href="../<?php echo htmlspecialchars($video['url']); ?>" target="_blank">View Video</a></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="thumbnail">Update Thumbnail (Optional)</label>
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/*">
                                <?php if (!empty($video['thumbnail'])): ?>
                                    <p style="margin-top: 10px; font-size: 0.85rem;">Current: <a href="../<?php echo htmlspecialchars($video['thumbnail']); ?>" target="_blank">View Image</a></p>
                                <?php endif; ?>
                            </div>
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
