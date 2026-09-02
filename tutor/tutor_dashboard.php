<?php
// Use the centralized session management and role check
require_once '../includes/init.php';
requireRole('tutor');
include '../includes/connect.php';

// Get current user's data
$tutor = getCurrentUser();
$tutor_id = $tutor['id'];
$tutor_name = $tutor['username'];

// Fetch fresh status from DB to ensure UI is always accurate
$status_check = mysqli_query($con, "SELECT status, document_proof FROM tutors WHERE id = $tutor_id");
$tutor_db_data = mysqli_fetch_assoc($status_check);
$current_status = $tutor_db_data['status'] ?? 'pending';
$has_document = !empty($tutor_db_data['document_proof']);

// Image path fix: Check multiple sources and ensure correct relative path
$raw_pic = $tutor['profile_pic'] ?? '';
$profile_pic_url = '';

if ($raw_pic && !filter_var($raw_pic, FILTER_VALIDATE_URL)) {
    // If it's a local path, ensure it starts from the root
    $clean_path = ltrim($raw_pic, './');
    // From tutor/ directory, we need to go up one level
    if (file_exists('../' . $clean_path)) {
        $profile_pic_url = '../' . $clean_path;
    }
} elseif ($raw_pic) {
    $profile_pic_url = $raw_pic; // It's a full URL
}

// Fetch stats
// 1. Active Subscribers
$active_sub_query = "SELECT COUNT(*) as active_count FROM premium_members WHERE tutor_id = ? AND end_date > NOW()";
$stmt = $con->prepare($active_sub_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$active_subs = $stmt->get_result()->fetch_assoc()['active_count'];

// 2. Total Earnings from subscriptions
$earnings_query = "SELECT SUM(amount) as total_earned FROM premium_members WHERE tutor_id = ?";
$stmt = $con->prepare($earnings_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$total_earned = $stmt->get_result()->fetch_assoc()['total_earned'] ?? 0;

// 3. Total Courses
$course_query = "SELECT COUNT(*) as course_count FROM courses WHERE tutor_id = ?";
$stmt = $con->prepare($course_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$total_courses = $stmt->get_result()->fetch_assoc()['course_count'];

// Handle Document Upload/Re-upload or Re-approval Request
if ((isset($_POST['reupload_document']) && isset($_FILES['document_proof'])) || isset($_POST['request_reapproval'])) {
    
    if (isset($_POST['request_reapproval'])) {
        // Just requesting re-approval with existing document
        $update_query = "UPDATE tutors SET status = 'pending' WHERE id = ?";
        $stmt = $con->prepare($update_query);
        $stmt->bind_param('i', $tutor_id);
        if ($stmt->execute()) {
            $current_status = 'pending';
            $success_msg = "Re-approval request sent successfully. Your status is now pending.";
        } else {
            $error_msg = "Failed to send re-approval request.";
        }
    } else if ($_FILES['document_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document_proof'];
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $upload_dir = '../content/tutor_documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $file_name;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $update_query = "UPDATE tutors SET document_proof = ?, status = 'pending' WHERE id = ?";
                $stmt = $con->prepare($update_query);
                $stmt->bind_param('si', $file_name, $tutor_id);
                if ($stmt->execute()) {
                    $current_status = 'pending';
                    $success_msg = "Your application has been submitted successfully and is now pending admin approval.";
                } else {
                    $error_msg = "Database error. Please try again.";
                }
            } else {
                $error_msg = "Failed to move uploaded file.";
            }
        } else {
            $error_msg = "Invalid file type. Allowed: PDF, JPG, PNG.";
        }
    } else {
        $error_msg = "Please select a document to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-panel.css?v=<?php echo time(); ?>">
    <style>
        /* Localized Dashboard Enhancements */
        .dashboard-header-flex {
            display: flex;
            gap: 25px;
            align-items: stretch;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .profile-summary-card {
            flex: 1;
            min-width: 320px;
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .profile-summary-card:hover { transform: translateY(-5px); }

        .profile-details h3 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-info-wrapper {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .profile-pic-large {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--secondary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .profile-pic-placeholder-large {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 2.5rem;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .welcome-message-box {
            flex: 1.5;
            min-width: 320px;
            background: linear-gradient(135deg, #fdfcf0 0%, #f5f9f7 100%);
            padding: 35px;
            border-radius: 16px;
            border: 1px solid #e0eadd;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .welcome-message-box h2 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .stat-card {
            background: white !important;
            padding: 40px !important;
            border-radius: 20px !important;
            display: flex;
            flex-direction: column !important;
            align-items: center !important;
            text-align: center;
            gap: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05) !important;
        }

        .stat-icon {
            width: 90px !important;
            height: 90px !important;
            font-size: 36px !important;
            margin-right: 0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .btn-wide {
            width: 100%;
            justify-content: center;
            padding: 14px 30px !important;
            font-weight: 700 !important;
            border-radius: 50px !important;
        }
        
        .mini-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .mini-stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid var(--border-color);
        }
        
        .mini-stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .mini-stat-info h4 { margin: 0; color: #666; font-size: 0.85rem; text-transform: uppercase; }
        .mini-stat-info p { margin: 0; font-size: 1.2rem; font-weight: 700; color: var(--primary-color); }
    </style>
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
            <main>
                <header class="header">
                    <h1 class="page-title">Instructor Overview</h1>
                </header>
                
                <div class="content">
                    <?php if (isset($success_msg)): ?>
                        <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-error" style="margin-bottom: 20px;"><?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <?php if ($current_status === 'new' || (!$has_document && $current_status !== 'approved')): ?>
                    <div class="alert-box" style="background: #e1f5fe; color: #01579b; padding: 25px; border-radius: 12px; border: 1px solid #b3e5fc; margin-bottom: 30px;">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <i class="fas fa-info-circle fa-2x"></i>
                            <h4 style="margin: 0; font-weight: 700;">Complete Your Application</h4>
                        </div>
                        <p style="margin-bottom: 20px;">Welcome to YogaMart! To start uploading courses and videos, you must first submit your certification document for verification. Once submitted, our admin team will review it.</p>
                        
                        <form method="POST" enctype="multipart/form-data" style="background: white; padding: 20px; border-radius: 10px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
                            <div style="flex: 1; min-width: 250px;">
                                <label style="display: block; font-size: 0.9rem; color: #666; margin-bottom: 5px;">Upload Certification Proof (PDF, JPG, PNG)</label>
                                <input type="file" name="document_proof" accept=".pdf,.jpg,.jpeg,.png" required style="width: 100%;">
                            </div>
                            <button type="submit" name="reupload_document" class="btn" style="background: var(--primary-color); color: white; border: none; padding: 12px 25px;">
                                <i class="fas fa-paper-plane"></i> Submit Application
                            </button>
                        </form>
                    </div>
                    <?php elseif ($current_status === 'pending'): ?>
                    <div class="alert-box" style="background: #fff3cd; color: #856404; padding: 25px; border-radius: 12px; border: 1px solid #ffeeba; margin-bottom: 30px;">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <i class="fas fa-clock fa-2x"></i>
                            <div>
                                <h4 style="margin: 0; font-weight: 700;">Account Pending Approval</h4>
                                <p style="margin: 5px 0 0;">Your certification documents are currently being reviewed by our admin team. You will have full access once approved.</p>
                            </div>
                        </div>
                        
                        <div style="background: rgba(255,255,255,0.5); padding: 15px; border-radius: 10px; margin-top: 10px;">
                            <p style="font-size: 0.9rem; margin-bottom: 15px;">Need to update your documents? You can upload a new document below:</p>
                            <form method="POST" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
                                <div style="flex: 1; min-width: 250px;">
                                    <input type="file" name="document_proof" accept=".pdf,.jpg,.jpeg,.png" required style="width: 100%;">
                                </div>
                                <button type="submit" name="reupload_document" class="btn" style="background: #856404; color: white; border: none; padding: 10px 20px;">
                                    <i class="fas fa-sync-alt"></i> Update Document
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($current_status === 'rejected'): ?>
                    <div class="alert-box" style="background: #f8d7da; color: #721c24; padding: 25px; border-radius: 12px; border: 1px solid #f5c6cb; margin-bottom: 30px;">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <i class="fas fa-exclamation-circle fa-2x"></i>
                            <h4 style="margin: 0; font-weight: 700;">Account Application Rejected</h4>
                        </div>
                        <p style="margin-bottom: 20px;">We're sorry, but your application as a tutor has been rejected. This could be due to insufficient or unclear certification documents. You can re-submit your application below.</p>
                        
                        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                            <!-- Option 1: Re-request with existing doc -->
                            <?php if ($has_document): ?>
                            <div style="flex: 1; min-width: 280px; background: rgba(255,255,255,0.7); padding: 20px; border-radius: 10px;">
                                <h5 style="margin-top: 0;">Re-request Approval</h5>
                                <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">If you believe your current document is valid, you can ask for a re-review.</p>
                                <form method="POST">
                                    <button type="submit" name="request_reapproval" class="btn" style="background: #555; color: white; border: none; width: 100%;">
                                        <i class="fas fa-redo"></i> Request Re-review
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- Option 2: Upload new doc -->
                            <div style="flex: 1.5; min-width: 300px; background: white; padding: 20px; border-radius: 10px;">
                                <h5 style="margin-top: 0;">Upload New Document</h5>
                                <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 10px;">
                                    <input type="file" name="document_proof" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button type="submit" name="reupload_document" class="btn" style="background: #721c24; color: white; border: none;">
                                        <i class="fas fa-upload"></i> Upload & Re-submit
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="dashboard-header-flex">
                        <!-- Profile Card -->
                        <div class="profile-summary-card">
                            <div class="profile-details">
                                <h3><i class="fas fa-id-card"></i> Your Profile</h3>
                                <div class="profile-info-wrapper">
                                    <?php if ($profile_pic_url): ?>
                                        <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" alt="Profile" class="profile-pic-large">
                                    <?php else: ?>
                                        <div class="profile-pic-placeholder-large"><?php echo strtoupper(substr($tutor_name, 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <div class="profile-text-info">
                                        <p style="font-size: 1.1rem; font-weight: 700; color: var(--primary-color);">
                                            <?php echo htmlspecialchars($tutor_name); ?>
                                        </p>
                                        <p style="color: #666; margin-bottom: 10px;"><?php echo htmlspecialchars($tutor['email']); ?></p>
                                        <a href="tutor_profile.php" class="btn" style="font-size: 0.8rem; padding: 8px 18px;">
                                            <i class="fas fa-user-edit"></i> Edit Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Welcome Box -->
                        <div class="welcome-message-box">
                            <h2>Namaste, <?php echo htmlspecialchars($tutor_name); ?>! 👋</h2>
                            <p style="font-size: 1.1rem; color: #4a5b53;">Welcome back to your workspace. You have <strong><?php echo $active_subs; ?></strong> active subscribers today!</p>
                        </div>
                    </div>

                    <!-- Mini Stats Grid -->
                    <div class="mini-stats-grid">
                        <div class="mini-stat-card">
                            <div class="mini-stat-icon" style="background: rgba(42, 89, 72, 0.1); color: var(--primary-color);">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="mini-stat-info">
                                <h4>Active Subs</h4>
                                <p><?php echo $active_subs; ?></p>
                            </div>
                        </div>
                        <div class="mini-stat-card">
                            <div class="mini-stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="mini-stat-info">
                                <h4>Total Earnings</h4>
                                <p>₹<?php echo number_format($total_earned, 2); ?></p>
                            </div>
                        </div>
                        <div class="mini-stat-card">
                            <div class="mini-stat-icon" style="background: rgba(102, 126, 234, 0.1); color: #667eea;">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="mini-stat-info">
                                <h4>My Courses</h4>
                                <p><?php echo $total_courses; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Widgets -->
                    <div class="dashboard-widgets">
                        <div class="stats-container">
                            <div class="stat-card">
                                <div class="stat-icon" style="background: rgba(42, 89, 72, 0.1); color: var(--primary-color);">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 style="margin-bottom: 10px;">My Courses</h3>
                                    <p style="color: #666; margin-bottom: 20px;">Manage, edit, and update your professional yoga programs.</p>
                                    <a href="tutor_courses.php" class="btn btn-wide">Open Course Manager</a>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--brand);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 style="margin-bottom: 10px;">Subscribers</h3>
                                    <p style="color: #666; margin-bottom: 20px;">View and manage students who have joined your premium community.</p>
                                    <a href="tutor_subscribers.php" class="btn btn-wide btn-primary">View My Students</a>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                    <i class="fas fa-gem"></i>
                                </div>
                                <div class="stat-info">
                                    <h3 style="margin-bottom: 10px;">Membership Plans</h3>
                                    <p style="color: #666; margin-bottom: 20px;">Create and manage subscription plans for your students.</p>
                                    <a href="tutor_plans.php" class="btn btn-wide" style="background: #ffc107; color: #000;">Manage Plans</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../assets/js/admin-sidebar.js"></script>
</body>
</html>
