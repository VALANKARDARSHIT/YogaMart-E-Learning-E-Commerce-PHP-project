<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];

if (isset($_GET['id'])) {
    $course_id = $_GET['id'];

    // Security check: Ensure the course belongs to the logged-in tutor
    $check_query = "SELECT id, thumbnail FROM courses WHERE id = ? AND tutor_id = ?";
    $check_stmt = $con->prepare($check_query);
    $check_stmt->bind_param('ii', $course_id, $tutor_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $course = $result->fetch_assoc();

    if ($course) {
        // Course belongs to the tutor, proceed with deletion

        // Delete the course thumbnail file if it exists
        if (!empty($course['thumbnail']) && file_exists($course['thumbnail'])) {
            unlink($course['thumbnail']);
        }

        // First, delete associated videos' files (optional but good practice)
        $video_query = "SELECT video_url FROM course_videos WHERE course_id = ?";
        $video_stmt = $con->prepare($video_query);
        $video_stmt->bind_param('i', $course_id);
        $video_stmt->execute();
        $video_result = $video_stmt->get_result();
        while ($video = $video_result->fetch_assoc()) {
            if (!empty($video['video_url']) && file_exists($video['video_url'])) {
                unlink($video['video_url']);
            }
        }
        
        // Then, delete video records from the database
        $delete_videos_query = "DELETE FROM course_videos WHERE course_id = ?";
        $delete_videos_stmt = $con->prepare($delete_videos_query);
        $delete_videos_stmt->bind_param('i', $course_id);
        $delete_videos_stmt->execute();

        // Finally, delete the course record
        $delete_course_query = "DELETE FROM courses WHERE id = ?";
        $delete_course_stmt = $con->prepare($delete_course_query);
        $delete_course_stmt->bind_param('i', $course_id);
        $delete_course_stmt->execute();
        
        setMessage("Course and all its content have been deleted successfully.", "success");
    } else {
        // Course not found or doesn't belong to the tutor
        setMessage("Error: You are not authorized to delete this course.", "error");
    }
}

header("Location: tutor_courses.php");
exit();
?>


