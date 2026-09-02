<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];
$tutorial_id = $_GET['id'] ?? null;

if (!$tutorial_id) {
    header("Location: tutor_tutorials.php");
    exit();
}

// Fetch tutorial data to get file paths before deleting from DB
$query = "SELECT url, thumbnail FROM tutorial_videos WHERE id = ? AND tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('ii', $tutorial_id, $tutor_id);
$stmt->execute();
$result = $stmt->get_result();
$tutorial = $result->fetch_assoc();

if (!$tutorial) {
    // Tutorial not found or doesn't belong to the tutor
    header("Location: tutor_tutorials.php");
    exit();
}

// Delete files from server
if (!empty($tutorial['url']) && file_exists($tutorial['url'])) {
    unlink($tutorial['url']);
}
if (!empty($tutorial['thumbnail']) && file_exists($tutorial['thumbnail'])) {
    unlink($tutorial['thumbnail']);
}

// Delete from database
$delete_query = "DELETE FROM tutorial_videos WHERE id = ? AND tutor_id = ?";
$delete_stmt = $con->prepare($delete_query);
$delete_stmt->bind_param('ii', $tutorial_id, $tutor_id);

if ($delete_stmt->execute()) {
    // Redirect back to tutorials list with success message
    header("Location: tutor_tutorials.php?message=Tutorial deleted successfully!");
    exit();
} else {
    // Redirect back with error message
    header("Location: tutor_tutorials.php?error=Error deleting tutorial: " . $delete_stmt->error);
    exit();
}
?>
