<?php
function getUsers($con, $limit = 5) {
    $sql = "SELECT * FROM users_tbl ORDER BY id DESC";
    if ($limit > 0) {
        $sql .= " LIMIT ?";
    }
    $stmt = $con->prepare($sql);
    if ($limit > 0) {
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotalUsers($con) {
    $sql = "SELECT COUNT(*) as count FROM users_tbl WHERE role = 'user'";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

function getVideos($con, $limit = 5) {
    $sql = "SELECT * FROM course_videos ORDER BY id DESC";
    if ($limit > 0) {
        $sql .= " LIMIT ?";
    }
    $stmt = $con->prepare($sql);
    if ($limit > 0) {
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotalVideos($con) {
    $sql = "SELECT COUNT(*) as count FROM course_videos";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

function getCourses($con, $limit = 5) {
    $sql = "SELECT * FROM courses ORDER BY id DESC";
    if ($limit > 0) {
        $sql .= " LIMIT ?";
    }
    $stmt = $con->prepare($sql);
    if ($limit > 0) {
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotalCourses($con) {
    $sql = "SELECT COUNT(*) as count FROM courses";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

function getTutorialVideos($con, $limit = 5) {
    $sql = "SELECT * FROM tutorial_videos ORDER BY id DESC";
    if ($limit > 0) {
        $sql .= " LIMIT ?";
    }
    $stmt = $con->prepare($sql);
    if ($limit > 0) {
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotalTutorialVideos($con) {
    $sql = "SELECT COUNT(*) as count FROM tutorial_videos";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

function getTutors($con, $limit = 0) {
    $sql = "SELECT * FROM tutors ORDER BY id DESC";
    if ($limit > 0) {
        $sql .= " LIMIT ?";
    }
    $stmt = $con->prepare($sql);
    if ($limit > 0) {
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotalTutors($con) {
    $sql = "SELECT COUNT(*) as count FROM tutors";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

function deleteTutor($con, $tutor_id) {
    // First, delete associated courses and videos
    $sql_courses = "SELECT id FROM courses WHERE tutor_id = ?";
    $stmt_courses = $con->prepare($sql_courses);
    $stmt_courses->bind_param('i', $tutor_id);
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();
    while ($course = $result_courses->fetch_assoc()) {
        deleteCourse($con, $course['id']);
    }
    
    // Then, delete the tutor
    $sql = "DELETE FROM tutors WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $tutor_id);
    
    return $stmt->execute();
}

function addAdmin($con, $name, $email, $password, $image = null) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $admin_role = 'admin';
    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO users_tbl (username, email, password, role, image, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('ssssss', $name, $email, $hashed_password, $admin_role, $image, $created_at);
    
    return $stmt->execute();
}

function deleteAdmin($con, $admin_id) {
    $sql = "DELETE FROM users_tbl WHERE id = ? AND role = 'admin'";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $admin_id);
    
    return $stmt->execute();
}

function addUser($con, $name, $email, $password, $role = 'user', $image = null) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO users_tbl (username, email, password, role, image, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('ssssss', $name, $email, $hashed_password, $role, $image, $created_at);
    
    return $stmt->execute();
}

function updateUser($con, $user_id, $name, $email, $role = null, $password = null, $image = null) {
    $sql = "UPDATE users_tbl SET username = ?, email = ?";
    $params = [$name, $email];
    $types = "ss";

    if ($role !== null) {
        $sql .= ", role = ?";
        $params[] = $role;
        $types .= "s";
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    if ($image !== null) {
        $sql .= ", image = ?";
        $params[] = $image;
        $types .= "s";
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);

    return $stmt->execute();
}
function deleteUser($con, $user_id) {
    $sql = "DELETE FROM users_tbl WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $user_id);
    
    return $stmt->execute();
}

function addVideo($con, $course_id, $title, $description, $video_url, $duration) {
    $created_at = date('Y-m-d H:i:s');
    $sql = "INSERT INTO course_videos (course_id, title, description, video_url, duration, created_at)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('isssss', $course_id, $title, $description, $video_url, $duration, $created_at);
    
    return $stmt->execute();
}

function handleFileUpload($file_input_name, $upload_dir, $allowed_types, $max_size) {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] !== 0) {
        return ['error' => 'No file uploaded or upload error.'];
    }
    
    $file = $_FILES[$file_input_name];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['error' => 'Invalid file type.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['error' => 'File size exceeds the limit.'];
    }
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = time() . '_' . uniqid() . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['path' => $target_path];
    } else {
        return ['error' => 'Failed to move uploaded file.'];
    }
}

function addCourse($con, $title, $description, $level, $thumbnail_path) {
    $created_at = date('Y-m-d H:i:s');
    $sql = "INSERT INTO courses (title, description, level, thumbnail, created_at)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('sssss', $title, $description, $level, $thumbnail_path, $created_at);
    
    return $stmt->execute();
}

function updateCourse($con, $course_id, $title, $description, $level, $thumbnail_path = null) {
    $sql = "UPDATE courses SET title = ?, description = ?, level = ?";
    $params = [$title, $description, $level];
    $types = 'sss';
    
    if ($thumbnail_path) {
        $sql .= ", thumbnail = ?";
        $params[] = $thumbnail_path;
        $types .= 's';
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $course_id;
    $types .= 'i';
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    return $stmt->execute();
}

function updateVideo($con, $video_id, $course_id, $title, $description, $duration, $video_url = null) {
    $sql = "UPDATE course_videos SET course_id = ?, title = ?, description = ?, duration = ?";
    $params = [$course_id, $title, $description, $duration];
    $types = 'isss';
    
    if ($video_url) {
        $sql .= ", video_url = ?";
        $params[] = $video_url;
        $types .= 's';
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $video_id;
    $types .= 'i';
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    return $stmt->execute();
}

function addTutorialVideo($con, $title, $description, $url, $thumbnail, $duration) {
    $created_at = date('Y-m-d H:i:s');
    $sql = "INSERT INTO tutorial_videos (title, description, url, thumbnail, duration, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('ssssss', $title, $description, $url, $thumbnail, $duration, $created_at);
    
    return $stmt->execute();
}

function updateTutorialVideo($con, $video_id, $title, $description, $duration, $url = null, $thumbnail = null) {
    $sql = "UPDATE tutorial_videos SET title = ?, description = ?, duration = ?";
    $params = [$title, $description, $duration];
    $types = 'sss';
    
    if ($url) {
        $sql .= ", url = ?";
        $params[] = $url;
        $types .= 's';
    }
    
    if ($thumbnail) {
        $sql .= ", thumbnail = ?";
        $params[] = $thumbnail;
        $types .= 's';
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $video_id;
    $types .= 'i';
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    return $stmt->execute();
}

function deleteTutorialVideo($con, $video_id) {
    $sql = "DELETE FROM tutorial_videos WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $video_id);
    
    return $stmt->execute();
}

function deleteCourse($con, $course_id) {
    $sql = "DELETE FROM courses WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $course_id);
    
    return $stmt->execute();
}

function deleteVideo($con, $video_id) {
    $sql = "DELETE FROM course_videos WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $video_id);
    
    return $stmt->execute();
}

function getContactSubmissions($con, $limit = 0) {
    $sql = "SELECT * FROM contact_submissions ORDER BY created_at DESC";
    if ($limit > 0) {
        $sql .= " LIMIT ?";
    }
    $stmt = $con->prepare($sql);
    if ($limit > 0) {
        $stmt->bind_param('i', $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotalContactSubmissions($con) {
    $sql = "SELECT COUNT(*) as count FROM contact_submissions";
    $stmt = $con->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

function markContactSubmissionRead($con, $id) {
    $sql = "UPDATE contact_submissions SET is_read = 1 WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

function deleteContactSubmission($con, $id) {
    $sql = "DELETE FROM contact_submissions WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}
?>