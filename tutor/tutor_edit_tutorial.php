<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];
$tutorial_id = $_GET['id'] ?? null;
$message = '';

if (!$tutorial_id) {
    header("Location: tutor_tutorials.php");
    exit();
}

// Fetch tutorial data
$query = "SELECT * FROM tutorial_videos WHERE id = ? AND tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('ii', $tutorial_id, $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$tutorial = $result->fetch_assoc();

if (!$tutorial) {
    header("Location: tutor_tutorials.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $is_free = isset($_POST['is_free']) ? 1 : 0;
    
    $video_path = $tutorial['url'];
    $thumbnail_path = $tutorial['thumbnail'];

    // Handle video file upload
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == UPLOAD_ERR_OK) {
        $video_dir = '../content/tutorials/';
        if (!is_dir($video_dir)) {
            mkdir($video_dir, 0777, true);
        }
        $video_ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        $video_file_name = uniqid() . '.' . $video_ext;
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $video_dir . $video_file_name)) {
            if (!empty($tutorial['url']) && file_exists('../' . $tutorial['url'])) {
                unlink('../' . $tutorial['url']);
            }
            $video_path = 'content/tutorials/' . $video_file_name;
        }
    }

    // Handle thumbnail file upload
    if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] == UPLOAD_ERR_OK) {
        $thumbnail_dir = '../content/tutorials/thumbnails/';
        if (!is_dir($thumbnail_dir)) {
            mkdir($thumbnail_dir, 0777, true);
        }
        $thumbnail_ext = pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION);
        $thumbnail_file_name = uniqid() . '.' . $thumbnail_ext;
        if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $thumbnail_dir . $thumbnail_file_name)) {
            if (!empty($tutorial['thumbnail']) && file_exists('../' . $tutorial['thumbnail'])) {
                unlink('../' . $tutorial['thumbnail']);
            }
            $thumbnail_path = 'content/tutorials/thumbnails/' . $thumbnail_file_name;
        }
    }

    $update_query = "UPDATE tutorial_videos SET title = ?, description = ?, is_free = ?, url = ?, thumbnail = ? WHERE id = ? AND tutor_id = ?";
    $update_stmt = $con->prepare($update_query);
    $update_stmt->bind_param('ssissii', $title, $description, $is_free, $video_path, $thumbnail_path, $tutorial_id, $tutor_id);

    if ($update_stmt->execute()) {
        setMessage("Tutorial updated successfully!", "success");
        header("Location: tutor_tutorials.php");
        exit();
    } else {
        $message = "Error updating tutorial: " . $update_stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tutorial - Tutor Panel</title>
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
                    <h1 class="page-title">Edit Tutorial Video</h1>
                    <a href="tutor_tutorials.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Tutorials</a>
                </header>
                <div class="content">
                    <?php if (!empty($message)) : ?>
                        <div class="alert alert-error">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Tutorial Title</label>
                            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($tutorial['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Video Description</label>
                            <textarea name="description" id="description" rows="6" required><?php echo htmlspecialchars($tutorial['description']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="video_file">Update Video File (Optional)</label>
                                <input type="file" name="video_file" id="video_file" accept="video/*">
                                <?php if (!empty($tutorial['url'])): ?>
                                    <p class="form-note">Current: <a href="../<?php echo htmlspecialchars($tutorial['url']); ?>" target="_blank">View Video</a></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="thumbnail_file">Update Thumbnail (Optional)</label>
                                <input type="file" name="thumbnail_file" id="thumbnail_file" accept="image/*">
                                <?php if (!empty($tutorial['thumbnail'])): ?>
                                    <p class="form-note">Current: <a href="../<?php echo htmlspecialchars($tutorial['thumbnail']); ?>" target="_blank">View Image</a></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group" style="background: #f9fbf8; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_free" id="is_free" style="width: auto;" <?php echo $tutorial['is_free'] ? 'checked' : ''; ?>>
                                <span style="font-weight: 600; color: var(--primary-color);">Make this video FREE for all users</span>
                            </label>
                        </div>

                        <div style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1rem;">
                                <i class="fas fa-save"></i> Save Tutorial Changes
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
