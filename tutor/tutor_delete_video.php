<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];

if (isset($_GET['id'])) {
    $video_id = $_GET['id'];

    // Security check: Ensure the video belongs to a course owned by the logged-in tutor
    $query = "SELECT cv.id, cv.video_url FROM course_videos cv JOIN courses c ON cv.course_id = c.id WHERE cv.id = ? AND c.tutor_id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('ii', $video_id, $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($video = $result->fetch_assoc()) {
        // Video belongs to the tutor, proceed with deletion

        // 1. Delete the actual video file from the filesystem
        if (!empty($video['video_url']) && file_exists($video['video_url'])) {
            unlink($video['video_url']);
        }

        // 2. Delete the video record from the database
        $delete_query = "DELETE FROM course_videos WHERE id = ?";
        $delete_stmt = $con->prepare($delete_query);
        $delete_stmt->bind_param('i', $video_id);
        
        if ($delete_stmt->execute()) {
            setMessage("Video has been deleted successfully.", "success");
        } else {
            setMessage("Error: Could not delete the video record.", "error");
        }
    } else {
        // Video not found or doesn't belong to the tutor
        setMessage("Error: You are not authorized to delete this video.", "error");
    }
}

header("Location: tutor_videos.php");
exit();
?>

