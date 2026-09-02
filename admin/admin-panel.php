<?php
    
    
    
    // Include session check for authentication
    require_once '../includes/init.php';
    require_once '../includes/email_sender.php';
    // Require admin role to access admin panel
    requireRole('admin');
    $current_user = getCurrentUser();
    
    include '../includes/connect.php';
    include '../includes/admin_functions.php';
    include '../includes/shop_admin_functions.php';
    
    // Check if database connection is successful
    if (!$con) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    
    // Fetch users (assuming you have a users table - adjust table name as needed)
    $users = getUsers($con);
    $total_users = getTotalUsers($con);

    // Fetch videos from course_videos table (corrected table name)
    $videos = getVideos($con);
    $total_videos = getTotalVideos($con);

    // Check if courses table exists, if not set empty arrays
    $courses = getCourses($con);
    $total_courses = getTotalCourses($con);

    // Fetch tutorial videos
    $tutorial_videos = getTutorialVideos($con);
    $total_tutorial_videos = getTotalTutorialVideos($con);

    // Fetch tutors
    $tutors = getTutors($con);
    $total_tutors = getTotalTutors($con);

    // Fetch Shop Stats
    $total_products = getTotalProducts($con);
    $total_orders = getTotalOrders($con);
    $total_sales = getTotalSales($con);
    $categories = getCategories($con);
    $all_products = getProducts($con);
    $all_orders = getOrders($con);

    // Fetch Contact Submissions
    $contact_submissions = getContactSubmissions($con);
    $total_contact_submissions = getTotalContactSubmissions($con);
    $unread_contact_count = count(array_filter($contact_submissions, function($s) { return $s['is_read'] == 0; }));

    // Handle form submissions
    $message = '';

    // Handle admin deletion
    if (isset($_POST['delete_admin'])) {
        $admin_id = $_POST['delete_admin'];
        
        if (deleteAdmin($con, $admin_id)) {
            $message = "Admin account deleted successfully!";
            $users = getUsers($con);
        } else {
            $message = "Error: Could not delete admin account.";
        }
    }

    // Handle admin update
    if (isset($_POST['edit_admin'])) {
        $id = $_POST['edit_admin_id'];
        $name = mysqli_real_escape_string($con, $_POST['edit_admin_name']);
        $email = mysqli_real_escape_string($con, $_POST['edit_admin_email']);
        $password = !empty($_POST['edit_admin_password']) ? $_POST['edit_admin_password'] : null;
        $role = $_POST['edit_admin_role'] ?? 'admin';
        
        $image_path = null;
        if (isset($_FILES['edit_admin_image']) && $_FILES['edit_admin_image']['error'] == 0) {
            $upload_result = handleFileUpload('edit_admin_image', '../content/users/profile_pictures/', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if (!isset($upload_result['error'])) {
                $image_path = basename($upload_result['path']);
            }
        }
        
        if (updateUser($con, $id, $name, $email, $role, $password, $image_path)) {
            $message = "Admin account updated successfully!";
            $users = getUsers($con);
        } else {
            $message = "Error: Could not update admin account.";
        }
    }

    // Handle admin creation
    if (isset($_POST['add_admin'])) {
        $name = mysqli_real_escape_string($con, $_POST['admin_name']);
        $email = mysqli_real_escape_string($con, $_POST['admin_email']);
        $password = $_POST['admin_password'];
        
        $image_path = null;
        if (isset($_FILES['admin_image']) && $_FILES['admin_image']['error'] == 0) {
            $upload_result = handleFileUpload('admin_image', '../content/users/profile_pictures/', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if (!isset($upload_result['error'])) {
                $image_path = basename($upload_result['path']);
            }
        }
        
        if (addAdmin($con, $name, $email, $password, $image_path)) {
            $message = "New admin account created successfully!";
            $users = getUsers($con);
        } else {
            $message = "Error: Could not create admin account. Email might already exist.";
        }
    }

    // Handle user update
    if (isset($_POST['edit_user'])) {
        $id = $_POST['edit_user_id'];
        $name = mysqli_real_escape_string($con, $_POST['edit_user_name']);
        $email = mysqli_real_escape_string($con, $_POST['edit_user_email']);
        $password = !empty($_POST['edit_user_password']) ? $_POST['edit_user_password'] : null;
        $role = $_POST['edit_user_role'] ?? 'user';
        
        $image_path = null;
        if (isset($_FILES['edit_user_image']) && $_FILES['edit_user_image']['error'] == 0) {
            $upload_result = handleFileUpload('edit_user_image', '../content/users/profile_pictures/', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if (!isset($upload_result['error'])) {
                $image_path = basename($upload_result['path']);
            }
        }
        
        if (updateUser($con, $id, $name, $email, $role, $password, $image_path)) {
            $message = "User account updated successfully!";
            $users = getUsers($con);
        } else {
            $message = "Error: Could not update user account.";
        }
    }

    // Handle user creation
    if (isset($_POST['add_user'])) {
        $name = mysqli_real_escape_string($con, $_POST['user_name']);
        $email = mysqli_real_escape_string($con, $_POST['user_email']);
        $password = $_POST['user_password'];
        $role = $_POST['user_role'] ?? 'user';
        
        $image_path = null;
        if (isset($_FILES['user_image']) && $_FILES['user_image']['error'] == 0) {
            $upload_result = handleFileUpload('user_image', '../content/users/profile_pictures/', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if (!isset($upload_result['error'])) {
                $image_path = basename($upload_result['path']);
            }
        }
        
        if (addUser($con, $name, $email, $password, $role, $image_path)) {
            $message = "New user account created successfully!";
            $users = getUsers($con);
        } else {
            $message = "Error: Could not create user account. Email might already exist.";
        }
    }

    // Handle user deletion
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['delete_user_id'];
        
        if (deleteUser($con, $user_id)) {
            $message = "User deleted successfully!";
            $users = getUsers($con);
        } else {
            $message = "Error: Could not delete user.";
        }
    }

    // Handle tutorial video deletion
    if (isset($_POST['delete_tutorial_video'])) {
        $video_id = $_POST['delete_tutorial_video_id'];
        
        if (deleteTutorialVideo($con, $video_id)) {
            $message = "Tutorial video deleted successfully!";
            $tutorial_videos = getTutorialVideos($con);
        } else {
            $message = "Error: " . mysqli_error($con);
        }
    }

    // Handle course deletion
    if (isset($_POST['delete_course'])) {
        $course_id = $_POST['delete_course'];
        
        if (deleteCourse($con, $course_id)) {
            $message = "Course deleted successfully!";
            $courses = getCourses($con);
        } else {
            $message = "Error: " . mysqli_error($con);
        }
    }

    // Handle video deletion
    if (isset($_POST['delete_video'])) {
        $video_id = $_POST['delete_video'];
        
        if (deleteVideo($con, $video_id)) {
            $message = "Video deleted successfully!";
            $videos = getVideos($con);
        } else {
            $message = "Error: " . mysqli_error($con);
        }
    }

    // Handle shop category creation
    if (isset($_POST['add_shop_category'])) {
        $name = $_POST['cat_name'];
        if (addCategory($con, $name)) {
            $message = "Category added successfully!";
            $categories = getCategories($con);
        } else {
            $message = "Error adding category.";
        }
    }

    // Handle shop category update
    if (isset($_POST['update_shop_category'])) {
        $id = $_POST['edit_cat_id'];
        $name = $_POST['edit_cat_name'];
        if (updateCategory($con, $id, $name)) {
            $message = "Category updated successfully!";
            $categories = getCategories($con);
        } else {
            $message = "Error updating category.";
        }
    }

    // Handle shop product creation
    if (isset($_POST['add_shop_product'])) {
        $name = $_POST['p_name'];
        $description = $_POST['p_desc'];
        $price = $_POST['p_price'];
        $stock = $_POST['p_stock'];
        $category_id = $_POST['p_category_id'];

        $image_path = '';
        $max_size = 2 * 1024 * 1024; // 2MB Limit
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (isset($_FILES['p_image']) && $_FILES['p_image']['name'] != '') {
            $upload_result = handleFileUpload('p_image', '../content/products/', $allowed_exts, $max_size);
            if (isset($upload_result['error'])) {
                $message = "Main Image Error: " . $upload_result['error'];
            } else {
                $image_path = 'content/products/' . basename($upload_result['path']);
            }
        }

        // Handle multiple images
        $multiple_images_json = null;
        if (strpos($message, 'Error:') === false && isset($_FILES['p_multiple_images']) && !empty($_FILES['p_multiple_images']['name'][0])) {
            $uploaded_paths = [];
            $files = $_FILES['p_multiple_images'];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] == 0) {
                    if ($files['size'][$i] > $max_size) {
                        $message = "Gallery Image Error: File size exceeds 2MB.";
                        break;
                    }

                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_exts)) {
                        $new_name = time() . '_' . uniqid() . '.' . $ext;
                        $target_dir = '../content/products/';
                        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                        $target_path = $target_dir . $new_name;
                        if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                            $uploaded_paths[] = 'content/products/' . $new_name;
                        }
                    } else {
                        $message = "Gallery Image Error: Invalid file type ($ext).";
                        break;
                    }
                }
            }
            if (!empty($uploaded_paths) && strpos($message, 'Error:') === false) {
                $multiple_images_json = json_encode($uploaded_paths);
            }
        }

        if (strpos($message, 'Error:') === false) {
            if (addProduct($con, $name, $description, $price, $image_path, $stock, $category_id, $multiple_images_json)) {
                $message = "Product added successfully!";
                $all_products = getProducts($con);
                $total_products = getTotalProducts($con);
            } else {
                $message = "Error: " . mysqli_error($con);
            }
        }
    }

    // Handle shop product update
    if (isset($_POST['update_shop_product'])) {
        $id = $_POST['edit_p_id'];
        $name = $_POST['edit_p_name'];
        $description = $_POST['edit_p_desc'];
        $price = $_POST['edit_p_price'];
        $stock = $_POST['edit_p_stock'];
        $category_id = $_POST['edit_p_category_id'];

        $image_path = null;
        $max_size = 2 * 1024 * 1024; // 2MB Limit
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (isset($_FILES['edit_p_image']) && $_FILES['edit_p_image']['name'] != '') {
            $upload_result = handleFileUpload('edit_p_image', '../content/products/', $allowed_exts, $max_size);
            if (isset($upload_result['error'])) {
                $message = "Update Image Error: " . $upload_result['error'];
            } else {
                $image_path = 'content/products/' . basename($upload_result['path']);
            }
        }

        // Handle multiple images update
        $multiple_images_json = null;
        if (strpos($message, 'Error:') === false && isset($_FILES['edit_p_multiple_images']) && !empty($_FILES['edit_p_multiple_images']['name'][0])) {
            $uploaded_paths = [];
            $files = $_FILES['edit_p_multiple_images'];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] == 0) {
                    if ($files['size'][$i] > $max_size) {
                        $message = "Gallery Image Error: File size exceeds 2MB.";
                        break;
                    }

                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed_exts)) {
                        $new_name = time() . '_' . uniqid() . '.' . $ext;
                        $target_dir = '../content/products/';
                        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                        $target_path = $target_dir . $new_name;
                        if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                            $uploaded_paths[] = 'content/products/' . $new_name;
                        }
                    } else {
                        $message = "Gallery Image Error: Invalid file type ($ext).";
                        break;
                    }
                }
            }
            if (!empty($uploaded_paths) && strpos($message, 'Error:') === false) {
                $multiple_images_json = json_encode($uploaded_paths);
            }
        }

        if (strpos($message, 'Error:') === false) {
            if (updateProduct($con, $id, $name, $description, $price, $stock, $category_id, $image_path, $multiple_images_json)) {
                $message = "Product updated successfully!";
                $all_products = getProducts($con);
            } else {
                $message = "Error: " . mysqli_error($con);
            }
        }
    }
    // Handle shop deletions (Product or Category)
    if (isset($_POST['confirm_shop_delete'])) {
        $id = $_POST['delete_item_id'];
        $type = $_POST['delete_item_type'];
        
        if ($type == 'product') {
            if (deleteProduct($con, $id)) {
                $message = "Product deleted successfully!";
                $all_products = getProducts($con);
                $total_products = getTotalProducts($con);
            } else {
                $message = "Error deleting product.";
            }
        } elseif ($type == 'category') {
            if (deleteCategory($con, $id)) {
                $message = "Category deleted successfully!";
                $categories = getCategories($con);
            } else {
                $message = "Error deleting category. It might have products assigned to it.";
            }
        }
    }

    // Handle tutor deletion
    if (isset($_POST['delete_tutor'])) {
        $tutor_id = $_POST['delete_tutor_id'];
        
        if (deleteTutor($con, $tutor_id)) {
            $message = "Tutor deleted successfully!";
            $tutors = getTutors($con);
        } else {
            $message = "Error: " . mysqli_error($con);
        }
    }

    // Handle tutor approval
    if (isset($_POST['approve_tutor'])) {
        $tutor_id = $_POST['tutor_id'];
        
        // Fetch tutor details for email notification
        $tutor_info_stmt = $con->prepare("SELECT username, email FROM tutors WHERE id = ?");
        $tutor_info_stmt->bind_param('i', $tutor_id);
        $tutor_info_stmt->execute();
        $tutor_info = $tutor_info_stmt->get_result()->fetch_assoc();
        $tutor_info_stmt->close();

        $stmt = $con->prepare("UPDATE tutors SET status='approved' WHERE id=?");
        $stmt->bind_param('i', $tutor_id);
        if ($stmt->execute()) {
            $message = "Tutor approved successfully!";
            
            // Send Approval Email
            if ($tutor_info) {
                $subject = "Your Tutor Application has been Approved!";
                $otp_for_email = ""; // Email_sender expects OTP as first param, but we can reuse it for approval msg
                
                // We'll customize the Email_sender or just use its PHPMailer logic
                // For now, using it as is but pass a placeholder or slightly modify if needed.
                // Since Email_sender is hardcoded for OTP, I will add a custom mailer for approval.
                
                $mail_body = "<h2>Congratulations, " . htmlspecialchars($tutor_info['username']) . "!</h2>
                              <p>Your application to become a certified tutor on YogaMart has been <b>approved</b>.</p>
                              <p>You now have full access to your dashboard, including the ability to upload courses, videos, and tutorials.</p>
                              <br><p>Best regards,<br>YogaMart Admin Team</p>";
                
                // Temporary workaround to use existing Email_sender structure or similar
                // Better to create a more generic mailer, but for now I'll use PHPMailer directly here 
                // since Email_sender is very specific to OTP formatting.
                
                require_once '../vendor/autoload.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = getenv('SMTP_USERNAME');
                    $mail->Password   = getenv('SMTP_PASSWORD');
                    $mail->SMTPSecure = getenv('SMTP_SECURE') ?: 'tls';
                    $mail->Port       = getenv('SMTP_PORT') ?: 587;
                    $mail->setFrom(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME'));
                    $mail->addAddress($tutor_info['email']);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $mail_body;
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Failed to send approval email: " . $mail->ErrorInfo);
                }
            }
            
            $tutors = getTutors($con);
        } else {
            $message = "Error approving tutor.";
        }
        $stmt->close();
    }

    // Handle tutor rejection
    if (isset($_POST['reject_tutor'])) {
        $tutor_id = $_POST['tutor_id'];
        $stmt = $con->prepare("UPDATE tutors SET status='rejected' WHERE id=?");
        $stmt->bind_param('i', $tutor_id);
        if ($stmt->execute()) {
            $message = "Tutor rejected.";
            $tutors = getTutors($con);
        } else {
            $message = "Error rejecting tutor.";
        }
        $stmt->close();
    }

    // Handle Contact Submission actions
    if (isset($_POST['mark_contact_read'])) {
        $contact_id = $_POST['contact_id'];
        if (markContactSubmissionRead($con, $contact_id)) {
            $message = "Message marked as read.";
            $contact_submissions = getContactSubmissions($con);
            $unread_contact_count = count(array_filter($contact_submissions, function($s) { return $s['is_read'] == 0; }));
        }
    }

    if (isset($_POST['delete_contact_submission'])) {
        $contact_id = $_POST['contact_id'];
        if (deleteContactSubmission($con, $contact_id)) {
            $message = "Message deleted successfully.";
            $contact_submissions = getContactSubmissions($con);
            $total_contact_submissions = getTotalContactSubmissions($con);
            $unread_contact_count = count(array_filter($contact_submissions, function($s) { return $s['is_read'] == 0; }));
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YogaMart | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Open+Sans:wght@300;400;500&display=swap" rel="stylesheet?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-panel.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <?php
    // Include session status for admin debugging
    include '../includes/session_status.php';
    echo getSessionStatusCSS();
    ?>
    
    <div class="admin-panel">
        <!-- Mobile Toggle Button (Floating) -->
        <button class="sidebar-toggle-mobile" id="mobileSidebarToggle" onclick="document.getElementById('sidebarToggle').click()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sticky Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-menu-container">
                <ul class="admin-menu">
                    <li class="menu-label">Core Management</li>
                    <li><a href="#" class="nav-link active" data-page="dashboard"><i class="fas fa-home fa-fw"></i> Dashboard</a></li>
                    <li><a href="#" class="nav-link" data-page="users"><i class="fas fa-users fa-fw"></i> Users</a></li>
                    <li><a href="#" class="nav-link" data-page="admins"><i class="fas fa-user-shield fa-fw"></i> Admins</a></li>
                    <li><a href="#" class="nav-link" data-page="courses"><i class="fas fa-book-open fa-fw"></i> Courses</a></li>
                    <li><a href="#" class="nav-link" data-page="videos"><i class="fas fa-video fa-fw"></i> Course Videos</a></li>
                    <li><a href="#" class="nav-link" data-page="tutorial-videos"><i class="fas fa-play-circle fa-fw"></i> Tutorial Videos</a></li>
                    <li><a href="#" class="nav-link" data-page="tutors">
                        <i class="fas fa-chalkboard-teacher fa-fw"></i> Tutors
                        <?php 
                        $pending_count = count(array_filter($tutors, function($t) { return isset($t['status']) && $t['status'] === 'pending'; }));
                        if ($pending_count > 0): ?>
                            <span class="badge" style="background: #f39c12; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 10px; margin-left: 5px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    
                    <li class="menu-label">Shop Management</li>
                    <li><a href="#" class="nav-link" data-page="shop-products"><i class="fas fa-shopping-bag fa-fw"></i> Shop Products</a></li>
                    <li><a href="#" class="nav-link" data-page="shop-categories"><i class="fas fa-tags fa-fw"></i> Shop Categories</a></li>
                    <li><a href="#" class="nav-link" data-page="shop-orders"><i class="fas fa-shopping-cart fa-fw"></i> Shop Orders</a></li>
                    
                    <li class="menu-label">Customer Requests</li>
                    <li><a href="#" class="nav-link" data-page="contact-submissions">
                        <i class="fas fa-envelope fa-fw"></i> Contact Submissions
                        <?php if ($unread_contact_count > 0): ?>
                            <span class="badge" style="background: #e74c3c; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 10px; margin-left: 5px; font-weight: bold;"><?php echo $unread_contact_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    
                    <li class="menu-label">System</li>
                    <li><a href="#" class="nav-link" data-page="settings"><i class="fas fa-cog fa-fw"></i> Settings</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content" id="dashboardMain">
            <!-- Dashboard Page -->
            <div class="page-content active" id="dashboard">
                <!-- ===== Vibrant Dashboard Hero Header ===== -->
                <div class="dashboard-hero-header">
                    <div class="dashboard-hero-left">
                        <div class="dashboard-greeting">
                            👋 Welcome back,
                            <span class="admin-name"><?php echo htmlspecialchars($current_user['username']); ?></span>!
                        </div>
                        <h1 class="dashboard-hero-title">Admin Dashboard</h1>
                        <p class="dashboard-hero-sub">Here's what's happening across YogaMart today.</p>
                        <div class="dashboard-meta-chips">
                            <span class="meta-chip chip-purple"><i class="fas fa-calendar-alt"></i> <?php echo date('D, d M Y'); ?></span>
                            <span class="meta-chip chip-green"><i class="fas fa-circle"></i> System Online</span>
                            <span class="meta-chip chip-orange"><i class="fas fa-user-shield"></i> Admin Access</span>
                        </div>
                    </div>
                    <div class="dashboard-hero-right">
                        <?php
                        $raw_pic = $current_user['profile_pic'] ?? '';
                        $pic_src = 'https://img.icons8.com/color/96/user-male-circle--v1.png';
                        if ($raw_pic && filter_var($raw_pic, FILTER_VALIDATE_URL)) { $pic_src = $raw_pic; }
                        elseif ($raw_pic) { $pic_src = '../content/users/profile_pictures/' . basename($raw_pic); }
                        ?>
                        <div class="admin-hero-avatar">
                            <img src="<?php echo htmlspecialchars($pic_src); ?>" alt="Admin Avatar"
                                 onerror="this.src='https://img.icons8.com/color/96/user-male-circle--v1.png';">
                        </div>
                        <div class="admin-hero-badge">
                            <i class="fas fa-shield-alt"></i> Super Admin
                        </div>
                    </div>
                </div>
                <?php displaySessionStatus(true); ?>

            <!-- Success/Error Message -->
            <?php 
    
    
    if (!empty($message)): ?>
            <div class="alert <?php 
    
    
    echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>" id="messageAlert">
                <?php 
    
    
    echo $message; ?>
                <button class="close-alert" onclick="closeAlert()">&times;</button>
            </div>
            <?php 
    
    
    endif; ?>
            
            <!-- Stats Overview -->
            <div class="stats-container">
                <a href="#" class="stat-card nav-link" data-page="users" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon users-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
    
    
    echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                </a>
                
                <a href="#" class="stat-card nav-link" data-page="videos" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon videos-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
    
    
    echo $total_videos; ?></h3>
                        <p>Course Videos</p>
                    </div>
                </a>

                <a href="#" class="stat-card nav-link" data-page="courses" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon courses-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
    
    
    echo $total_courses; ?></h3>
                        <p>Total Courses</p>
                    </div>
                </a>

                <a href="#" class="stat-card nav-link" data-page="tutorial-videos" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
    
    
    echo $total_tutorial_videos; ?></h3>
                        <p>Tutorial Videos</p>
                    </div>
                </a>
                <a href="#" class="stat-card nav-link" data-page="tutors" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_tutors; ?></h3>
                        <p>Total Tutors</p>
                    </div>
                </a>

                <a href="#" class="stat-card nav-link" data-page="shop-products" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_products; ?></h3>
                        <p>Total Products</p>
                    </div>
                </a>

                <a href="#" class="stat-card nav-link" data-page="shop-orders" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f1c40f 0%, #f39c12 100%);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </a>

                <a href="#" class="stat-card nav-link" data-page="contact-submissions" style="text-decoration: none; color: inherit;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_contact_submissions; ?></h3>
                        <p>Contact Inquiries</p>
                    </div>
                </a>

                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);">
                        <i class="fas fa-indian-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($total_sales, 2); ?></h3>
                        <p>Total Sales</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Users</h2>
                    
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
    
    
    if (empty($users)): ?>
                        <tr>
                            <td colspan="5">No users found. Please check if the users_tbl table exists and contains data.</td>
                        </tr>
                        <?php 
    
    
    else: ?>
                            <?php 
    
    
    foreach ($users as $user): ?>
                            <tr>
                                <td><?php 
    
    
    echo htmlspecialchars($user['id'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="user-cell">
                                        <?php
                                        $user_img = $user['image'] ?? '';
                                        $check_path = '';
                                        if (!empty($user_img)) {
                                            $check_path = '../content/users/profile_pictures/' . basename($user_img);
                                        }
                                        
                                        if (!empty($check_path) && file_exists($check_path)): ?>
                                            <img src="<?php echo htmlspecialchars($check_path); ?>" alt="Profile" class="profile-pic-table">
                                        <?php else: ?>
                                            <div class="profile-pic-placeholder">
                                                <?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="user-info-cell">
                                            <span class="user-name"><?php echo htmlspecialchars(!empty($user['username']) ? $user['username'] : 'N/A'); ?></span>
                                            <span class="user-role"><?php echo ucfirst($user['role'] ?? 'user'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php 
    
    
    echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars(date('M d, Y', strtotime($user['created_at'] ?? 'now'))); ?></td>
                                <td class="action-buttons">
                                    <button class="delete-btn" onclick="deleteUser(<?php 
    
    
    echo $user['id']; ?>, '<?php 
    
    
    echo addslashes($user['username'] ?? ''); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php 
    
    
    endforeach; ?>
                        <?php 
    
    
    endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Course Videos Section inside dashboard -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Course Videos Overview</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($videos)): ?>
                            <tr><td colspan="4">No videos found.</td></tr>
                        <?php else: 
                            $limited_videos = array_slice($videos, 0, 5);
                            foreach ($limited_videos as $video): ?>
                            <tr>
                                <td><?php echo $video['id']; ?></td>
                                <td><?php echo htmlspecialchars($video['title']); ?></td>
                                <td><?php echo htmlspecialchars($video['duration']); ?></td>
                                <td><button class="delete-btn" onclick="deleteVideo(<?php echo $video['id']; ?>, '<?php echo addslashes($video['title']); ?>')">Delete</button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div><!-- end dashboard page-content -->

        <!-- Users Page -->
        <div class="page-content" id="users">
            <div class="header">
                <h1 class="page-title">User Management</h1>
                <div class="user-info">
                    <?php
                    $profile_pic = $current_user['profile_picture'] ?? $current_user['image'] ?? '';
                    $display_name = $current_user['username'] ?? 'Admin';
                    $img_src = '';
                    
                    if (!empty($profile_pic)) {
                        $check_path = '../content/users/profile_pictures/' . basename($profile_pic);
                        if (file_exists($check_path)) {
                            $img_src = $check_path;
                        }
                    }
                    
                    if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" style="width: 40px; height: 40px; font-size: 14px; margin-right: 10px;">
                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($display_name); ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Users</h2>
                    <button class="btn" onclick="openAddUserModal()" style="background: linear-gradient(135deg, #a8d5ba 0%, #2a5948 100%);"><i class="fas fa-plus"></i> Add New User</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        
    
    
    // Fetch all users for the users page
                        $all_users_result = mysqli_query($con, "SELECT * FROM users_tbl ORDER BY id DESC");
                        $all_users = $all_users_result ? mysqli_fetch_all($all_users_result, MYSQLI_ASSOC) : [];
                        
                        if (empty($all_users)): ?>
                        <tr>
                            <td colspan="5">No users found. Please check if the users_tbl table exists and contains data.</td>
                        </tr>
                        <?php 
    
    
    else: ?>
                            <?php 
    
    
    foreach ($all_users as $user): ?>
                            <tr>
                                <td><?php 
    
    
    echo htmlspecialchars($user['id'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="user-cell">
                                        <?php
                                        $user_img = $user['image'] ?? '';
                                        $check_path = '';
                                        if (!empty($user_img)) {
                                            $check_path = '../content/users/profile_pictures/' . basename($user_img);
                                        }
                                        
                                        if (!empty($check_path) && file_exists($check_path)): ?>
                                            <img src="<?php echo htmlspecialchars($check_path); ?>" alt="Profile" class="profile-pic-table">
                                        <?php else: ?>
                                            <div class="profile-pic-placeholder">
                                                <?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="user-info-cell">
                                            <span class="user-name"><?php echo htmlspecialchars(!empty($user['username']) ? $user['username'] : 'N/A'); ?></span>
                                            <span class="user-role"><?php echo ucfirst($user['role'] ?? 'user'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php 
    
    
    echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars(date('M d, Y', strtotime($user['created_at'] ?? 'now'))); ?></td>
                                <td class="action-buttons">
                                    <button class="edit-btn" onclick='openEditUserModal(<?php echo json_encode($user); ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <button class="delete-btn" onclick="deleteUser(<?php 
    
    
    echo $user['id']; ?>, '<?php 
    
    
    echo addslashes($user['username'] ?? ''); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php 
    
    
    endforeach; ?>
                        <?php 
    
    
    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Admins Page -->
        <div class="page-content" id="admins">
            <div class="header">
                <h1 class="page-title">Admin Management</h1>
                <div class="user-info">
                    <?php
                    $profile_pic = $current_user['profile_picture'] ?? $current_user['image'] ?? '';
                    $display_name = $current_user['username'] ?? 'Admin';
                    $img_src = '';
                    
                    if (!empty($profile_pic)) {
                        $check_path = '../content/users/profile_pictures/' . basename($profile_pic);
                        if (file_exists($check_path)) {
                            $img_src = $check_path;
                        }
                    }
                    
                    if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" style="width: 40px; height: 40px; font-size: 14px; margin-right: 10px;">
                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($display_name); ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Admin Accounts</h2>
                    <button class="btn" onclick="openAddAdminModal()" style="background: linear-gradient(135deg, #1d4033 0%, #2a5948 100%);"><i class="fas fa-plus"></i> Add New Admin</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        
    
    
    // Fetch only admin users
                        $admin_users_result = mysqli_query($con, "SELECT * FROM users_tbl WHERE LOWER(role) = 'admin' ORDER BY id DESC");
                        $admin_users = $admin_users_result ? mysqli_fetch_all($admin_users_result, MYSQLI_ASSOC) : [];
                        
                        if (empty($admin_users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #999; padding: 2rem;">No admin accounts found. Create the first admin account!</td>
                        </tr>
                        <?php 
    
    
    else: ?>
                            <?php 
    
    
    foreach ($admin_users as $admin): ?>
                            <tr>
                                <td><?php 
    
    
    echo htmlspecialchars($admin['id']); ?></td>
                                <td>
                                    <div class="user-cell">
                                        <?php
                                        $original_path = '../content/users/profile_pictures/' . $admin['image'];
                                        $check_path = $original_path;
                                        if (!file_exists($check_path) && file_exists('../' . $check_path)) {
                                            $check_path = '../' . $check_path;
                                        }
                                        if (!empty($admin['image']) && file_exists($check_path)): ?>
                                            <img src="<?php echo htmlspecialchars($check_path); ?>" alt="Profile" class="profile-pic-table">
                                        <?php else: ?>
                                            <div class="profile-pic-placeholder" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                                <?php echo strtoupper(substr($admin['username'] ?? 'A', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="user-info-cell">
                                            <span class="user-name"><?php echo htmlspecialchars(!empty($admin['username']) ? $admin['username'] : 'N/A'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="user-role" style="color: #667eea; font-weight: bold;"><?php echo htmlspecialchars(ucfirst($admin['role'])); ?></span></td>
                                <td><?php 
    
    
    echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars(date('M d, Y', strtotime($admin['created_at'] ?? 'now'))); ?></td>
                                <td class="action-buttons">
                                    <button class="edit-btn" onclick='openEditAdminModal(<?php echo json_encode($admin); ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <button class="delete-btn" onclick="deleteAdmin(<?php 
    
    
    echo $admin['id']; ?>, '<?php 
    
    
    echo addslashes($admin['username'] ?? ''); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php 
    
    
    endforeach; ?>
                        <?php 
    
    
    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Course Videos Page -->
        <div class="page-content" id="videos">
            <div class="header">
                <h1 class="page-title">Course Videos</h1>
                <div class="user-info">
                    <?php
                    $profile_pic = $current_user['profile_picture'] ?? $current_user['image'] ?? '';
                    $display_name = $current_user['username'] ?? 'Admin';
                    $img_src = '';
                    
                    if (!empty($profile_pic)) {
                        $check_path = '../content/users/profile_pictures/' . basename($profile_pic);
                        if (file_exists($check_path)) {
                            $img_src = $check_path;
                        }
                    }
                    
                    if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" style="width: 40px; height: 40px; font-size: 14px; margin-right: 10px;">
                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($display_name); ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Course Videos</h2>
                    
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Course ID</th>
                            <th>Duration</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        
    
    
    // Fetch all videos for the videos page
                        $all_videos_result = mysqli_query($con, "SELECT * FROM course_videos ORDER BY id DESC");
                        $all_videos = $all_videos_result ? mysqli_fetch_all($all_videos_result, MYSQLI_ASSOC) : [];
                        
                        if (empty($all_videos)): ?>
                        <tr>
                            <td colspan="6">No videos found.</td>
                        </tr>
                        <?php 
    
    
    else: ?>
                            <?php 
    
    
    foreach ($all_videos as $video): ?>
                            <tr>
                                <td><?php 
    
    
    echo htmlspecialchars($video['id'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars($video['title'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars($video['course_id'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars($video['duration'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars($video['created_at'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    
                                    <button class="delete-btn" onclick="deleteVideo(<?php echo $video['id']; ?>, '<?php echo addslashes($video['title'] ?? ''); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php 
    
    
    endforeach; ?>
                        <?php 
    
    
    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Courses Page -->
        <div class="page-content" id="courses">
            <div class="header">
                <h1 class="page-title">Course Management</h1>
                <div class="user-info">
                    <?php
                    $profile_pic = $current_user['profile_picture'] ?? $current_user['image'] ?? '';
                    $display_name = $current_user['username'] ?? 'Admin';
                    $img_src = '';
                    
                    if (!empty($profile_pic)) {
                        $check_path = '../content/users/profile_pictures/' . basename($profile_pic);
                        if (file_exists($check_path)) {
                            $img_src = $check_path;
                        }
                    }
                    
                    if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" style="width: 40px; height: 40px; font-size: 14px; margin-right: 10px;">
                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($display_name); ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Courses</h2>
                    
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course Title</th>
                            <th>Description</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        
    
    
    // Fetch all courses for the courses page
                        $all_courses_result = mysqli_query($con, "SELECT * FROM courses ORDER BY id DESC");
                        $all_courses = $all_courses_result ? mysqli_fetch_all($all_courses_result, MYSQLI_ASSOC) : [];
                        
                        if (empty($all_courses)): ?>
                        <tr>
                            <td colspan="5">No courses found.</td>
                        </tr>
                        <?php 
    
    
    else: ?>
                            <?php 
    
    
    foreach ($all_courses as $course):?>
                            <tr>
                                <td><?php
    
    
    echo htmlspecialchars($course['id'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars($course['title'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars(substr($course['description'] ?? 'N/A', 0, 100)) . '...'; ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars($course['created_at'] ?? 'N/A'); ?></td>
                                <td class="action-buttons">
                                    
                                    <button class="delete-btn" onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo addslashes($course['title'] ?? ''); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php 
    
    
    endforeach; ?>
                        <?php 
    
    
    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tutorial Videos Page -->
        <div class="page-content" id="tutorial-videos">
            <div class="header">
                <h1 class="page-title">Tutorial Videos Management</h1>
                <div class="user-info">
                    <?php
                    $profile_pic = $current_user['profile_picture'] ?? $current_user['image'] ?? '';
                    $display_name = $current_user['username'] ?? 'Admin';
                    $img_src = '';
                    
                    if (!empty($profile_pic)) {
                        $check_path = '../content/users/profile_pictures/' . basename($profile_pic);
                        if (file_exists($check_path)) {
                            $img_src = $check_path;
                        }
                    }
                    
                    if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" style="width: 40px; height: 40px; font-size: 14px; margin-right: 10px;">
                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($display_name); ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Tutorial Videos</h2>
                    
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Thumbnail</th>
                            <th>Description</th>
                            <th>Video URL</th>
                            <th>Duration</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        
    
    
    // Fetch all tutorial videos for the page
                        $all_tutorial_videos_result = @mysqli_query($con, "SELECT * FROM tutorial_videos ORDER BY id DESC");
                        $all_tutorial_videos = $all_tutorial_videos_result ? mysqli_fetch_all($all_tutorial_videos_result, MYSQLI_ASSOC) : [];
                        
                        if (empty($all_tutorial_videos)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: #999;">
                                <i class="fas fa-video" style="font-size: 48px; color: #ddd; margin-bottom: 1rem;"></i><br>
                                No tutorial videos found. Add your first tutorial video!
                            </td>
                        </tr>
                        <?php 
    
    
    else: ?>
                            <?php 
    
    
    foreach ($all_tutorial_videos as $video):?>
                            <tr>
                                <td><?php 
    
    
    echo htmlspecialchars($video['id'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars($video['title'] ?? 'N/A'); ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                    $original_thumbnail = $video['thumbnail'] ?? '';
                                    $check_thumbnail = $original_thumbnail;
                                    if (!empty($check_thumbnail) && !file_exists($check_thumbnail) && file_exists('../' . $check_thumbnail)) {
                                        $check_thumbnail = '../' . $check_thumbnail;
                                    }
                                    
                                    if (!empty($original_thumbnail) && file_exists($check_thumbnail)): ?>
                                        <img src="<?php echo htmlspecialchars($check_thumbnail); ?>" alt="Thumbnail" style="width: 60px; height: 40px; object-fit: cover; border-radius: 5px; border: 2px solid #e1e5e9;">
                                    <?php 
                                    else: ?>
                                        <div style="width: 60px; height: 40px; background: linear-gradient(135deg, #ff6b6b, #ee5a24); border-radius: 5px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; margin: 0 auto;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php 
    
    
    endif; ?>
                                </td>
                                <td><?php 
    
    
    echo htmlspecialchars(substr($video['description'] ?? 'N/A', 0, 40)) . (strlen($video['description']) > 40 ? '...' : ''); ?></td>
                                <td>
                                    <?php 
    
    
    if (!empty($video['url'])): ?>
                                        <a href="<?php 
    
    
    echo htmlspecialchars($video['url']); ?>" target="_blank" style="color: #007bff; text-decoration: none;" title="<?php 
    
    
    echo htmlspecialchars($video['url']); ?>">
                                            <i class="fas fa-play-circle"></i> <?php 
    
    
    echo substr(basename($video['url']), 0, 15) . '...'; ?>
                                        </a>
                                    <?php 
    
    
    else: ?>
                                        <span style="color: #999; font-style: italic;">No video</span>
                                    <?php 
    
    
    endif; ?>
                                </td>
                                <td><?php 
    
    
    echo htmlspecialchars($video['duration'] ?? 'N/A'); ?></td>
                                <td><?php 
    
    
    echo htmlspecialchars(date('M d, Y', strtotime($video['created_at'] ?? 'now'))); ?></td>
                                <td class="action-buttons">
                                    
                                    <button class="delete-btn" onclick="deleteTutorialVideo(<?php 
    
    
    echo $video['id']; ?>, '<?php 
    
    
    echo addslashes($video['title'] ?? ''); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php 
    
    
    endforeach; ?>
                        <?php 
    
    
    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Tutors Page -->
        <div class="page-content" id="tutors">
            <div class="header">
                <h1 class="page-title">Tutor Management</h1>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Pending Approvals</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Document</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pending_tutors = array_filter($tutors, function($t) { return isset($t['status']) && $t['status'] === 'pending'; });
                        if (empty($pending_tutors)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999; padding: 20px;">No pending tutor applications.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($pending_tutors as $tutor):?>
                            <tr>
                                <td><?php echo htmlspecialchars($tutor['id']); ?></td>
                                <td><?php echo htmlspecialchars($tutor['username']); ?></td>
                                <td><?php echo htmlspecialchars($tutor['email']); ?></td>
                                <td>
                                    <?php if (!empty($tutor['document_proof'])): ?>
                                        <a href="../content/tutor_documents/<?php echo htmlspecialchars($tutor['document_proof']); ?>" target="_blank" class="btn" style="background: #3498db; padding: 5px 10px; font-size: 12px;"><i class="fas fa-file-alt"></i> View Doc</a>
                                    <?php else: ?>
                                        <span style="color: #999;">No Doc</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="tutor_id" value="<?php echo $tutor['id']; ?>">
                                        <button type="submit" name="approve_tutor" class="btn" style="background: #2ecc71;"><i class="fas fa-check"></i> Approve</button>
                                        <button type="submit" name="reject_tutor" class="btn" style="background: #e74c3c;"><i class="fas fa-times"></i> Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-section" style="margin-top: 40px;">
                <div class="section-header">
                    <h2 class="section-title">Approved Tutors</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $approved_tutors = array_filter($tutors, function($t) { return !isset($t['status']) || $t['status'] === 'approved'; });
                        if (empty($approved_tutors)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999; padding: 20px;">No approved tutors found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($approved_tutors as $tutor):?>
                            <tr>
                                <td><?php echo htmlspecialchars($tutor['id']); ?></td>
                                <td><?php echo htmlspecialchars($tutor['username']); ?></td>
                                <td><?php echo htmlspecialchars($tutor['email']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($tutor['created_at']))); ?></td>
                                <td class="action-buttons">
                                    <a href="admin_view_tutor_content.php?tutor_id=<?php echo $tutor['id']; ?>" class="btn">View Content</a>
                                    <button class="delete-btn" onclick="deleteTutor(<?php echo $tutor['id']; ?>, '<?php echo addslashes($tutor['username']); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Shop Products Page -->
        <div class="page-content" id="shop-products">
            <div class="header">
                <h1 class="page-title">Shop Product Management</h1>
                <div class="user-info">
                    <?php
                    $profile_pic = $current_user['profile_picture'] ?? $current_user['image'] ?? '';
                    $display_name = $current_user['username'] ?? 'Admin';
                    $img_src = '';
                    
                    if (!empty($profile_pic)) {
                        $check_path = '../content/users/profile_pictures/' . basename($profile_pic);
                        if (file_exists($check_path)) {
                            $img_src = $check_path;
                        }
                    }
                    
                    if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" style="width: 40px; height: 40px; font-size: 14px; margin-right: 10px;">
                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($display_name); ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Products</h2>
                    <button class="btn" id="addProductBtn" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);"><i class="fas fa-plus"></i> Add New Product</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_products)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">No products found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($all_products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <div class="product-cell">
                                        <div class="product-img-container">
                                            <?php 
                                            $imgPath = $product['image'];
                                            if (empty($imgPath) || !file_exists($imgPath)) {
                                                if (!empty($imgPath) && file_exists('../' . $imgPath)) {
                                                    $imgPath = '../' . $imgPath;
                                                } else {
                                                    $imgPath = '../assets/images/img.avif'; // fallback
                                                }
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Product" class="product-img-table">
                                        </div>
                                        <div class="user-info-cell">
                                            <span class="user-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?: 'N/A'); ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button class="edit-btn" onclick='editProduct(<?php echo json_encode($product); ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <button class="delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shop Categories Page -->
        <div class="page-content" id="shop-categories">
            <div class="header">
                <h1 class="page-title">Product Category Management</h1>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Categories</h2>
                    <button class="btn" id="addCategoryBtn" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);"><i class="fas fa-plus"></i> Add New Category</button>
                </div>
                
                <table style="max-width: 600px;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 2rem;">No categories found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="action-buttons">
                                    <button class="edit-btn" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="delete-btn" onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')"><i class="fas fa-trash"></i> Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shop Orders Page -->
        <div class="page-content" id="shop-orders">
            <div class="header">
                <h1 class="page-title">Order Monitoring</h1>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Customer Orders</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_orders)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">No orders found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($all_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge" style="background: <?php 
                                        echo $order['payment_status'] == 'completed' ? '#2ecc71' : ($order['payment_status'] == 'pending' ? '#f1c40f' : '#e74c3c'); 
                                    ?>; color: white; border: none;">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-ghost" onclick="viewOrder(<?php echo $order['id']; ?>)">View Details</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Contact Submissions Page -->
        <div class="page-content" id="contact-submissions">
            <div class="header">
                <h1 class="page-title">Contact Inquiries</h1>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">All Submissions</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Message Preview</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contact_submissions)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">No contact submissions found.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($contact_submissions as $sub): ?>
                            <tr style="<?php echo $sub['is_read'] == 0 ? 'background: #fdf2e9; font-weight: 500;' : ''; ?>">
                                <td style="vertical-align: middle;"><?php echo $sub['id']; ?></td>
                                <td style="vertical-align: middle;">
                                    <strong><?php echo htmlspecialchars($sub['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($sub['email']); ?></small><br>
                                    <small><?php echo htmlspecialchars($sub['phone']); ?></small>
                                </td>
                                <td style="vertical-align: middle;"><?php echo htmlspecialchars($sub['subject']); ?></td>
                                <td style="vertical-align: middle;" title="<?php echo htmlspecialchars($sub['message']); ?>">
                                    <?php echo htmlspecialchars(substr($sub['message'], 0, 50)) . (strlen($sub['message']) > 50 ? '...' : ''); ?>
                                </td>
                                <td style="vertical-align: middle;"><?php echo date('M d, Y H:i', strtotime($sub['created_at'])); ?></td>
                                <td style="vertical-align: middle;">
                                    <?php if ($sub['is_read'] == 0): ?>
                                        <span class="badge" style="background: #e67e22; color: white;">Unread</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #2ecc71; color: white;">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div style="display: flex; gap: 8px; align-items: center; justify-content: flex-start;">
                                        <button class="btn" style="padding: 6px 10px; font-size: 12px; background: #2ecc71; height: 32px; min-width: 36px;" onclick="viewContactMessage(<?php echo htmlspecialchars(json_encode($sub)); ?>)" title="View Full Message"><i class="fas fa-eye"></i></button>
                                        <?php if ($sub['is_read'] == 0): ?>
                                        <form method="POST" style="margin:0; display: flex;">
                                            <input type="hidden" name="contact_id" value="<?php echo $sub['id']; ?>">
                                            <button type="submit" name="mark_contact_read" class="btn" style="padding: 6px 10px; font-size: 12px; background: #3498db; height: 32px; min-width: 36px;" title="Mark as Read"><i class="fas fa-check"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <button class="delete-btn" style="padding: 6px 10px; font-size: 12px; height: 32px; min-width: 36px; border: 1px solid #ddd; background: white; margin: 0;" onclick="deleteContactSubmission(<?php echo $sub['id']; ?>, '<?php echo addslashes($sub['name']); ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Settings Page -->
        <div class="page-content" id="settings">
            <div class="header">
                <h1 class="page-title">Settings</h1>
                <div class="user-info">
                    <?php
                    $profile_pic = $current_user['profile_picture'] ?? $current_user['image'] ?? '';
                    $display_name = $current_user['username'] ?? 'Admin';
                    $img_src = '';
                    
                    if (!empty($profile_pic)) {
                        $check_path = '../content/users/profile_pictures/' . basename($profile_pic);
                        if (file_exists($check_path)) {
                            $img_src = $check_path;
                        }
                    }
                    
                    if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" style="width: 40px; height: 40px; font-size: 14px; margin-right: 10px;">
                            <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($display_name); ?></span>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">General Settings</h2>
                </div>
                
                <form>
                    <div class="form-group">
                        <label for="siteName">Site Name</label>
                        <input type="text" id="siteName" value="YogaMart">
                    </div>
                    
                    <div class="form-group">
                        <label for="siteDescription">Site Description</label>
                        <textarea id="siteDescription" rows="3">Quick morning energizers, cozy stretch breaks, and calming breathGÇödesigned to make you smile.</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="adminEmail">Admin Email</label>
                        <input type="email" id="adminEmail" value="admin@yogamart.com">
                    </div>
                    
                    <button type="submit" class="btn">Save Settings</button>
                </form>
            </div>
        </div>
    
    
    
    <!-- Delete User Modal -->
    <div class="modal" id="deleteUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete User</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p style="color: #e74c3c;">This action cannot be undone.</p>
            </div>
            <form method="POST">
                <input type="hidden" id="deleteUserId" name="delete_user_id">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeDeleteModal()" style="background-color: #6c757d; flex: 1;">Cancel</button>
                    <button type="submit" name="delete_user" class="btn" style="background-color: #e74c3c; flex: 1;">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal" id="addAdminModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 class="modal-title">Create Admin Account</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="adminName">Full Name</label>
                    <input type="text" id="adminName" name="admin_name" placeholder="Enter admin name" required>
                </div>
                <div class="form-group">
                    <label for="adminEmail">Email Address</label>
                    <input type="email" id="adminEmail" name="admin_email" placeholder="Enter email address" required>
                </div>
                <div class="form-group">
                    <label for="adminPassword">Initial Password</label>
                    <input type="password" id="adminPassword" name="admin_password" placeholder="Set a secure password" required>
                </div>
                <div class="form-group">
                    <label for="adminImage">Profile Picture (Optional)</label>
                    <input type="file" id="adminImage" name="admin_image" accept="image/*">
                </div>
                <button type="submit" name="add_admin" class="btn" style="width: 100%; margin-top: 15px; background: #2a5948; border: none;">Create Admin</button>
            </form>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 class="modal-title">Create User Account</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="userName">Full Name</label>
                    <input type="text" id="userName" name="user_name" placeholder="Enter user's name" required>
                </div>
                <div class="form-group">
                    <label for="userEmail">Email Address</label>
                    <input type="email" id="userEmail" name="user_email" placeholder="Enter email address" required>
                </div>
                <div class="form-group">
                    <label for="userPassword">Initial Password</label>
                    <input type="password" id="userPassword" name="user_password" placeholder="Set a secure password" required>
                </div>
                <div class="form-group">
                    <label for="userRole">Account Role</label>
                    <select id="userRole" name="user_role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="tutor">Tutor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="userImage">Profile Picture (Optional)</label>
                    <input type="file" id="userImage" name="user_image" accept="image/*">
                </div>
                <button type="submit" name="add_user" class="btn" style="width: 100%; margin-top: 15px; background: #a8d5ba; color: #2a5948; border: none;">Create User</button>
            </form>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal" id="editAdminModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 class="modal-title">Edit Admin Account</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editAdminId" name="edit_admin_id">
                <div class="form-group">
                    <label for="editAdminName">Full Name</label>
                    <input type="text" id="editAdminName" name="edit_admin_name" required>
                </div>
                <div class="form-group">
                    <label for="editAdminEmail">Email Address</label>
                    <input type="email" id="editAdminEmail" name="edit_admin_email" required>
                </div>
                <div class="form-group">
                    <label for="editAdminRole">Account Role</label>
                    <select id="editAdminRole" name="edit_admin_role" required>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editAdminPassword">Change Password (leave blank to keep current)</label>
                    <input type="password" id="editAdminPassword" name="edit_admin_password" placeholder="New password">
                </div>
                <div class="form-group">
                    <label for="editAdminImage">Update Profile Picture (Optional)</label>
                    <input type="file" id="editAdminImage" name="edit_admin_image" accept="image/*">
                </div>
                <button type="submit" name="edit_admin" class="btn" style="width: 100%; margin-top: 15px; background: #2a5948; border: none;">Update Admin</button>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2 class="modal-title">Edit User Account</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editUserId" name="edit_user_id">
                <div class="form-group">
                    <label for="editUserName">Full Name</label>
                    <input type="text" id="editUserName" name="edit_user_name" required>
                </div>
                <div class="form-group">
                    <label for="editUserEmail">Email Address</label>
                    <input type="email" id="editUserEmail" name="edit_user_email" required>
                </div>
                <div class="form-group">
                    <label for="editUserRole">Account Role</label>
                    <select id="editUserRole" name="edit_user_role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editUserPassword">Change Password (leave blank to keep current)</label>
                    <input type="password" id="editUserPassword" name="edit_user_password" placeholder="New password">
                </div>
                <div class="form-group">
                    <label for="editUserImage">Update Profile Picture (Optional)</label>
                    <input type="file" id="editUserImage" name="edit_user_image" accept="image/*">
                </div>
                <button type="submit" name="edit_user" class="btn" style="width: 100%; margin-top: 15px; background: #a8d5ba; color: #2a5948; border: none;">Update User</button>
            </form>
        </div>
    </div>
    
    <!-- Delete Tutor Modal -->
    <div class="modal" id="deleteTutorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Tutor</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteTutorName"></strong>?</p>
                <p style="color: #e74c3c;">This action cannot be undone and will delete all courses and videos by this tutor.</p>
            </div>
            <form method="POST">
                <input type="hidden" id="deleteTutorId" name="delete_tutor_id">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeDeleteTutorModal()" style="background-color: #6c757d; flex: 1;">Cancel</button>
                    <button type="submit" name="delete_tutor" class="btn" style="background-color: #e74c3c; flex: 1;">Delete Tutor</button>
                </div>
            </form>
        </div>
    </div>

    



    <!-- Add Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Shop Product</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="pName">Product Name</label>
                    <input type="text" id="pName" name="p_name" placeholder="Enter product name" required>
                </div>
                <div class="form-group">
                    <label for="pDesc">Description</label>
                    <textarea id="pDesc" name="p_desc" rows="3" placeholder="Enter product description" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="pPrice">Price (₹)</label>
                        <input type="number" id="pPrice" name="p_price" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="pStock">Stock Quantity</label>
                        <input type="number" id="pStock" name="p_stock" placeholder="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="pCategory">Category</label>
                    <select id="pCategory" name="p_category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pImage">Product Main Image</label>
                    <input type="file" id="pImage" name="p_image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="pMultipleImages">Additional Product Images (Optional)</label>
                    <input type="file" id="pMultipleImages" name="p_multiple_images[]" accept="image/*" multiple>
                    <small style="color: #666; display: block; margin-top: 5px;">You can select multiple files at once.</small>
                </div>
                <button type="submit" name="add_shop_product" class="btn" style="width: 100%; margin-top: 15px; background: #2ecc71; border: none;">Save Product</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal" id="editProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Shop Product</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editPId" name="edit_p_id">
                <div class="form-group">
                    <label for="editPName">Product Name</label>
                    <input type="text" id="editPName" name="edit_p_name" required>
                </div>
                <div class="form-group">
                    <label for="editPDesc">Description</label>
                    <textarea id="editPDesc" name="edit_p_desc" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editPPrice">Price (₹)</label>
                        <input type="number" id="editPPrice" name="edit_p_price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="editPStock">Stock Quantity</label>
                        <input type="number" id="editPStock" name="edit_p_stock" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editPCategory">Category</label>
                    <select id="editPCategory" name="edit_p_category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editPImage">Update Main Image (Optional)</label>
                    <input type="file" id="editPImage" name="edit_p_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="editPMultipleImages">Update Additional Images (Optional - replaces old ones)</label>
                    <input type="file" id="editPMultipleImages" name="edit_p_multiple_images[]" accept="image/*" multiple>
                </div>
                <button type="submit" name="update_shop_product" class="btn" style="width: 100%; margin-top: 15px;">Update Product</button>
            </form>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal" id="categoryModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Add Category</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="catName">Category Name</label>
                    <input type="text" id="catName" name="cat_name" required>
                </div>
                <button type="submit" name="add_shop_category" class="btn" style="width: 100%; margin-top: 15px; background: #3498db; border: none;">Add Category</button>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal" id="editCategoryModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Edit Category</h2>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="editCatId" name="edit_cat_id">
                <div class="form-group">
                    <label for="editCatName">Category Name</label>
                    <input type="text" id="editCatName" name="edit_cat_name" required>
                </div>
                <button type="submit" name="update_shop_category" class="btn" style="width: 100%; margin-top: 15px;">Update Category</button>
            </form>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal" id="orderDetailsModal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 class="modal-title">Order Details #<span id="displayOrderId"></span></h2>
                <button class="close-btn">&times;</button>
            </div>
            <div id="orderDetailsContent" style="padding: 1rem 25px;">
                <!-- Content loaded via JS/PHP -->
            </div>
        </div>
    </div>

    <!-- Delete Modal (Shared) -->
    <div class="modal" id="shopDeleteModal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Deletion</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                <p style="color: #e74c3c;">This action cannot be undone.</p>
            </div>
            <form method="POST">
                <input type="hidden" id="deleteItemId" name="delete_item_id">
                <input type="hidden" id="deleteItemType" name="delete_item_type">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeShopDeleteModal()" style="background-color: #6c757d; flex: 1;">Cancel</button>
                    <button type="submit" name="confirm_shop_delete" class="btn" style="background-color: #e74c3c; flex: 1;">Confirm Delete</button>
                </div>
            </form>
        </div>
    </div>

    
    
    <!-- Delete Tutorial Video Modal -->
    <div class="modal" id="deleteTutorialVideoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Delete Tutorial Video</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteTutorialVideoName"></strong>?</p>
                <p style="color: #e74c3c;">This action cannot be undone.</p>
            </div>
            <form method="POST">
                <input type="hidden" id="deleteTutorialVideoId" name="delete_tutorial_video_id">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeDeleteTutorialVideoModal()" style="background-color: #6c757d; flex: 1;">Cancel</button>
                    <button type="submit" name="delete_tutorial_video" class="btn" style="background-color: #e74c3c; flex: 1;">Delete Video</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contact Message View Modal -->
    <div class="modal" id="viewContactModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 class="modal-title">Contact Inquiry Details</h2>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body" id="contactViewContent" style="padding: 20px;">
                <!-- Content injected via JS -->
            </div>
            <div style="padding: 0 20px 20px 20px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn" onclick="document.getElementById('viewContactModal').classList.remove('show')" style="background-color: #6c757d;">Close</button>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const body = document.body;
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const deleteUserModal = document.getElementById('deleteUserModal');
        const deleteTutorModal = document.getElementById('deleteTutorModal');
        const deleteTutorialVideoModal = document.getElementById('deleteTutorialVideoModal');
        const closeBtns = document.querySelectorAll('.close-btn');
        const navLinks = document.querySelectorAll('.nav-link');
        const pageContents = document.querySelectorAll('.page-content');
        
        // Toggle Sidebar on Mobile
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            body.classList.toggle('sidebar-active');
            
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Header scroll behavior
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                body.classList.add('header-hidden');
            } else {
                body.classList.remove('header-hidden');
            }
        });

        // Initial state for larger screens
        if (window.innerWidth > 900) {
            sidebar.classList.add('active');
            body.classList.add('sidebar-active');
        }

        // Adjust on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 900) {
                sidebar.classList.add('active');
                body.classList.add('sidebar-active');
            } else {
                sidebar.classList.remove('active');
                body.classList.remove('sidebar-active');
            }
        });
        
        // E-Commerce Modal Openers
        const addProductBtn = document.getElementById('addProductBtn');
        const productModal = document.getElementById('productModal');
        if (addProductBtn) {
            addProductBtn.addEventListener('click', () => {
                productModal.classList.add('show');
            });
        }

        const addCategoryBtn = document.getElementById('addCategoryBtn');
        const categoryModal = document.getElementById('categoryModal');
        if (addCategoryBtn) {
            addCategoryBtn.addEventListener('click', () => {
                categoryModal.classList.add('show');
            });
        }

        // Functions for Edit/Delete Shop Items
        window.editCategory = function(id, name) {
            document.getElementById('editCatId').value = id;
            document.getElementById('editCatName').value = name;
            document.getElementById('editCategoryModal').classList.add('show');
        };

        window.deleteCategory = function(id, name) {
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteItemType').value = 'category';
            document.getElementById('shopDeleteModal').classList.add('show');
        };

        window.editProduct = function(product) {
            document.getElementById('editPId').value = product.id;
            document.getElementById('editPName').value = product.name;
            document.getElementById('editPDesc').value = product.description;
            document.getElementById('editPPrice').value = product.price;
            document.getElementById('editPStock').value = product.stock;
            document.getElementById('editPCategory').value = product.category_id;
            document.getElementById('editProductModal').classList.add('show');
        };

        window.deleteProduct = function(id, name) {
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteItemType').value = 'product';
            document.getElementById('shopDeleteModal').classList.add('show');
        };

        window.closeShopDeleteModal = function() {
            document.getElementById('shopDeleteModal').classList.remove('show');
        };

        window.viewOrder = function(orderId) {
            console.log('Viewing order:', orderId);
            document.getElementById('displayOrderId').textContent = orderId;
            const contentDiv = document.getElementById('orderDetailsContent');
            contentDiv.innerHTML = '<div style="text-align:center; padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary-color);"></i><p style="margin-top:10px;">Fetching secure order details...</p></div>';
            document.getElementById('orderDetailsModal').classList.add('show');
            
            fetch(`../shop/shop_get_order_details.php?id=${orderId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    // Check if response looks like JSON (starts with { or [)
                    const trimmed = html.trim();
                    if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
                        try {
                            const data = JSON.parse(trimmed);
                            console.log('Received JSON instead of HTML:', data);
                            contentDiv.innerHTML = '<div style="padding:20px; color:red;">Error: Received data in unexpected format. Please refresh.</div>';
                        } catch(e) {
                            contentDiv.innerHTML = html;
                        }
                    } else {
                        contentDiv.innerHTML = html;
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    contentDiv.innerHTML = '<div style="padding:20px; color:red;"><i class="fas fa-exclamation-triangle"></i> Failed to load details. Please check connection.</div>';
                });
        };

        window.openAddAdminModal = function() {
            document.getElementById('addAdminModal').classList.add('show');
        };

        window.openEditAdminModal = function(admin) {
            document.getElementById('editAdminId').value = admin.id;
            document.getElementById('editAdminName').value = admin.username;
            document.getElementById('editAdminEmail').value = admin.email;
            document.getElementById('editAdminRole').value = admin.role;
            document.getElementById('editAdminModal').classList.add('show');
        };

        window.openAddUserModal = function() {
            document.getElementById('addUserModal').classList.add('show');
        };

        window.openEditUserModal = function(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUserName').value = user.username;
            document.getElementById('editUserEmail').value = user.email;
            document.getElementById('editUserRole').value = user.role || 'user';
            document.getElementById('editUserModal').classList.add('show');
        };
        
        // Close Modals
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Remove show class from all modals
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
        
        // User Management Functions
        function deleteUser(id, name) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = name;
            deleteUserModal.classList.add('show');
        }
        
        function closeDeleteModal() {
            deleteUserModal.classList.remove('show');
        }

        function deleteTutor(id, name) {
            document.getElementById('deleteTutorId').value = id;
            document.getElementById('deleteTutorName').innerText = name;
            document.getElementById('deleteTutorModal').classList.add('show');
        }

        function closeDeleteTutorModal() {
            deleteTutorModal.classList.remove('show');
        }
        
        function deleteAdmin(id, name) {
            if (confirm('⚠️ WARNING: Are you sure you want to delete admin account "' + name + '"?\n\nThis action cannot be undone and will remove all admin privileges from this account.')) {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_admin" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Tutorial Video Management Functions
        function deleteTutorialVideo(id, title) {
            document.getElementById('deleteTutorialVideoId').value = id;
            document.getElementById('deleteTutorialVideoName').textContent = title;
            deleteTutorialVideoModal.classList.add('show');
        }
        
        function closeDeleteTutorialVideoModal() {
            deleteTutorialVideoModal.classList.remove('show');
        }
        
        // Course Management Functions
        function deleteCourse(id, title) {
            if (confirm('GÜ WARNING: Are you sure you want to delete course "' + title + '"?\n\nThis action cannot be undone and will remove the course and all its associated data.')) {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_course" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Video Management Functions
        function deleteVideo(id, title) {
            if (confirm('GÜ WARNING: Are you sure you want to delete video "' + title + '"?\n\nThis action cannot be undone and will remove the video file and all its associated data.')) {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_video" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Product Image Validation
        const validateImage = (input) => {
            const files = input.files;
            const maxSize = 2 * 1024 * 1024; // 2MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (!allowedTypes.includes(file.type)) {
                    if (typeof showToast === 'function') {
                        showToast(`Invalid file type: ${file.name}. Only JPG, PNG, GIF, and WEBP are allowed.`, 'error');
                    }
                    input.value = '';
                    return false;
                }
                if (file.size > maxSize) {
                    if (typeof showToast === 'function') {
                        showToast(`File too large: ${file.name}. Maximum size allowed is 2MB.`, 'error');
                    }
                    input.value = '';
                    return false;
                }
            }
            return true;
        };

        const pImageInput = document.getElementById('pImage');
        const pMultipleImagesInput = document.getElementById('pMultipleImages');
        const editPImageInput = document.getElementById('editPImage');
        const editPMultipleImagesInput = document.getElementById('editPMultipleImages');

        if (pImageInput) pImageInput.onchange = function() { validateImage(this); };
        if (pMultipleImagesInput) pMultipleImagesInput.onchange = function() { validateImage(this); };
        if (editPImageInput) editPImageInput.onchange = function() { validateImage(this); };
        if (editPMultipleImagesInput) editPMultipleImagesInput.onchange = function() { validateImage(this); };
        
        function viewContactMessage(data) {
            const content = document.getElementById('contactViewContent');
            content.innerHTML = `
                <div style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                    <p><strong>From:</strong> ${data.name} (${data.email})</p>
                    <p><strong>Phone:</strong> ${data.phone || 'N/A'}</p>
                    <p><strong>Subject:</strong> ${data.subject}</p>
                    <p><strong>Date:</strong> ${new Date(data.created_at).toLocaleString()}</p>
                    <p><strong>Newsletter:</strong> ${data.newsletter == 1 ? 'Yes' : 'No'}</p>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; line-height: 1.6; white-space: pre-wrap;">
                    ${data.message}
                </div>
                ${data.is_read == 0 ? `
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="contact_id" value="${data.id}">
                        <button type="submit" name="mark_contact_read" class="btn" style="width: 100%; background: #3498db;">Mark as Read & Close</button>
                    </form>
                ` : ''}
            `;
            document.getElementById('viewContactModal').classList.add('show');
        }

        function deleteContactSubmission(id, name) {
            if (confirm('Are you sure you want to delete the message from "' + name + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_contact_submission" value="1"><input type="hidden" name="contact_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Make functions globally available
        window.deleteUser = deleteUser;
        window.closeDeleteModal = closeDeleteModal;
        window.deleteAdmin = deleteAdmin;
        window.deleteTutorialVideo = deleteTutorialVideo;
        window.closeDeleteTutorialVideoModal = closeDeleteTutorialVideoModal;
        window.deleteCourse = deleteCourse;
        window.deleteVideo = deleteVideo;
        window.deleteTutor = deleteTutor;
        window.closeDeleteTutorModal = closeDeleteTutorModal;
        
        // Navigation between pages
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active class from all links
                navLinks.forEach(navLink => {
                    navLink.classList.remove('active');
                });
                
                // Add active class to clicked link
                link.classList.add('active');
                
                // Hide all page contents
                pageContents.forEach(page => {
                    page.classList.remove('active');
                });
                
                // Show the selected page
                const pageId = link.getAttribute('data-page');
                document.getElementById(pageId).classList.add('active');
                
                // Close sidebar on mobile after selection
                if (window.innerWidth <= 900) {
                    sidebar.classList.remove('active');
                    body.classList.remove('sidebar-active');
                }
            });
        });
        
        // Close alert messages
        function closeAlert() {
            const alert = document.getElementById('messageAlert');
            if (alert) {
                alert.style.display = 'none';
            }
        }
        
        // Auto-hide alert messages after 5 seconds
        const messageAlert = document.getElementById('messageAlert');
        if (messageAlert) {
            setTimeout(() => {
                messageAlert.style.display = 'none';
            }, 5000);
        }
        
        console.log('Admin panel loaded successfully');
    </script>

        <?php include '../includes/footer.php'; ?>
        </div> <!-- end main-content -->
    </div> <!-- end admin-panel -->
</body>
</html>


