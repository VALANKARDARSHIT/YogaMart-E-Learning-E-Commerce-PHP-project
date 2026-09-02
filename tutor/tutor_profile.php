<?php
// Include session check for authentication
require_once '../includes/init.php';
requireRole('tutor');
include '../includes/connect.php';
include '../includes/admin_functions.php';
include '../includes/otp_functions.php';
include '../includes/email_sender.php';

$session_user = getCurrentUser();
$is_tutor = true;

// Fetch fresh user data from database
$user_query = mysqli_query($con, "SELECT * FROM tutors WHERE id = '" . $session_user['id'] . "'");

if (!$user_query || mysqli_num_rows($user_query) == 0) {
    header("Location: ../login-registration.php?msg=User+not+found");
    exit();
}
$user = mysqli_fetch_assoc($user_query);

// Handle OTP sending via AJAX
if (isset($_POST['send_otp_profile'])) {
    header('Content-Type: application/json');
    try {
        list($otp, $hash) = otp_Generator();
        if (Email_sender($otp, $user['email'], 'YogaMart - Password Reset OTP')) {
            echo json_encode(['success' => true, 'message' => 'OTP sent to your email ' . $user['email']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send OTP. Please try again later.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle profile update form submission
$message = '';
if (isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username'] ?? '');
    $new_bio = trim($_POST['bio'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $update_fields = [];
    $update_params = [];
    $types = "";

    // Username update
    if (!empty($new_username)) {
        $update_fields[] = "username = ?";
        $update_params[] = $new_username;
        $types .= "s";
    }

    // Bio update
    $update_fields[] = "bio = ?";
    $update_params[] = $new_bio;
    $types .= "s";

    // Profile Picture update
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = 'content/users/profile_pictures/';
        $upload_result = handleFileUpload('profile_picture', '../' . $upload_dir, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1024 * 1024);
        
        if (isset($upload_result['path'])) {
            $db_path = str_replace('../', '', $upload_result['path']);
            $update_fields[] = "profile_picture = ?";
            $update_params[] = $db_path;
            $types .= "s";
        } else {
            $message = "Upload Error: " . $upload_result['error'];
        }
    }

    // Password update (Optional)
    if (empty($message) && !empty($new_password)) {
        $otp_submitted = $_POST['otp_code'] ?? '';
        $use_otp = !empty($otp_submitted);
        $password_verified = false;

        if ($use_otp) {
            $otp_result = otp_checker($otp_submitted);
            if ($otp_result['isValid']) {
                $password_verified = true;
                unset($_SESSION['otp_hash']);
                unset($_SESSION['plaintext_otp']);
            } else {
                $message = "Invalid or expired OTP. Please try again.";
            }
        } else {
            $db_pass = $user['password_hash'] ?? $user['password'];
            if (password_verify($current_password, $db_pass)) {
                $password_verified = true;
            } else {
                $message = "Incorrect current password. Password was not changed.";
            }
        }

        if ($password_verified) {
            if ($new_password === $confirm_password) {
                $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_col = 'password'; 
                $update_fields[] = "$pass_col = ?";
                $update_params[] = $hashed_pass;
                $types .= "s";
            } else {
                $message = "New passwords do not match.";
            }
        }
    }

    if (empty($message) && !empty($update_fields)) {
        $sql = "UPDATE tutors SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_params[] = $session_user['id'];
        $types .= "i";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$update_params);
        
        if ($stmt->execute()) {
            $success_text = "Profile updated successfully!";
            
            // Refresh local user data
            $user_query = mysqli_query($con, "SELECT * FROM tutors WHERE id = '" . $session_user['id'] . "'");
            $user = mysqli_fetch_assoc($user_query);
            
            // Update session data
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
            if (isset($_SESSION['user_data'])) {
                $_SESSION['user_data']['username'] = $user['username'];
                $_SESSION['user_data']['profile_picture'] = $user['profile_picture'] ?? null;
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $success_text, 'username' => $user['username']]);
                exit();
            }
            $message = $success_text;
        } else {
            $message = "Database error: " . $stmt->error;
        }
    }

    if (!empty($message) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $message]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Profile - YogaMart</title>
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
            <main>
                <div class="profile-page-card" style="max-width: 800px; margin: 0 auto;">
                    <div class="card">
                        <?php include '../profile_content.php'; ?>
                    </div>
                </div>

                <!-- Account Verification Status Section -->
                <div class="profile-page-card" style="max-width: 800px; margin: 20px auto;">
                    <div class="card" style="padding: 25px; border-radius: 15px; background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                        <h3 style="font-family: 'Playfair Display', serif; color: var(--primary-color); margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px;">
                            <i class="fas fa-user-check"></i> Account Verification
                        </h3>
                        
                        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px;">
                            <div style="font-size: 0.9rem; color: #666;">Current Status:</div>
                            <?php 
                            $status = $user['status'] ?? 'new';
                            $status_color = '#666';
                            $status_bg = '#f0f0f0';
                            $status_icon = 'fa-question-circle';
                            
                            if ($status === 'approved') {
                                $status_color = '#27ae60';
                                $status_bg = '#eafaf1';
                                $status_icon = 'fa-check-circle';
                            } elseif ($status === 'pending') {
                                $status_color = '#f39c12';
                                $status_bg = '#fef5e7';
                                $status_icon = 'fa-clock';
                            } elseif ($status === 'rejected') {
                                $status_color = '#e74c3c';
                                $status_bg = '#fdedec';
                                $status_icon = 'fa-times-circle';
                            }
                            ?>
                            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 15px; border-radius: 20px; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>; font-weight: 700; font-size: 0.9rem; text-transform: uppercase;">
                                <i class="fas <?php echo $status_icon; ?>"></i> <?php echo ucfirst($status); ?>
                            </div>
                        </div>

                        <?php if ($status !== 'approved'): ?>
                            <div style="background: #f9fbf8; border: 1px dashed var(--primary-color); padding: 20px; border-radius: 12px;">
                                <h4 style="margin-bottom: 10px; color: #333;">Submit Verification Request</h4>
                                <p style="font-size: 0.9rem; color: #666; margin-bottom: 20px;">
                                    <?php if ($status === 'rejected'): ?>
                                        Your previous application was not approved. Please upload a clear, valid certification document to request re-approval.
                                    <?php elseif ($status === 'pending'): ?>
                                        You have already submitted a request. However, you can update your document if needed. This will restart the review process.
                                    <?php else: ?>
                                        To start offering courses, please upload your professional yoga certification or proof of expertise.
                                    <?php endif; ?>
                                </p>
                                
                                <form action="tutor_dashboard.php" method="POST" enctype="multipart/form-data">
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="font-size: 0.85rem; font-weight: 600;">Certificate Document (PDF, JPG, PNG)</label>
                                        <input type="file" name="document_proof" accept=".pdf,.jpg,.jpeg,.png" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
                                    </div>
                                    <button type="submit" name="reupload_document" class="btn" style="width: 100%; padding: 12px; background: var(--primary-color); border: none;">
                                        <i class="fas fa-paper-plane"></i> <?php echo ($status === 'new' ? 'Submit for Approval' : 'Re-submit for Approval'); ?>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div style="background: #eafaf1; border: 1px solid #27ae60; padding: 15px; border-radius: 10px; color: #27ae60;">
                                <i class="fas fa-shield-alt"></i> Your account is verified and you have full access to tutor features.
                            </div>
                            <?php if (!empty($user['document_proof'])): ?>
                                <div style="margin-top: 15px;">
                                    <a href="../content/tutor_documents/<?php echo htmlspecialchars($user['document_proof']); ?>" target="_blank" style="color: #3498db; font-size: 0.9rem; text-decoration: none;">
                                        <i class="fas fa-file-pdf"></i> View my submitted certification
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Edit Profile Modal -->
                <div id="editModal" class="modal">
                    <div class="modal-content" style="max-width: 600px;">
                        <div class="modal-header">
                            <h2>Edit My Professional Profile</h2>
                            <button class="close-btn" onclick="closeEditModal()">&times;</button>
                        </div>
                        <form method="POST" enctype="multipart/form-data" id="profileEditForm">
                            <div class="form-group">
                                <label for="profile_picture">Profile Picture</label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                <small style="color: #666;">Upload a professional photo (JPG, PNG - Max 5MB)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="username">Public Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="bio">Professional Bio</label>
                                <textarea id="bio" name="bio" rows="4" placeholder="Tell students about your expertise..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f0f0f0; cursor: not-allowed;">
                                <small style="color: #888;">Email cannot be changed for security reasons.</small>
                            </div>
                            
                            <div class="password-update-box" style="margin-top: 25px; padding: 20px; background: #f9fbf8; border-radius: 10px; border: 1px solid var(--border-color);">
                                <h3 style="font-size: 1rem; margin-bottom: 15px; color: var(--primary-color);">Update Password (Optional)</h3>
                                
                                <div id="current-password-group">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" placeholder="Verify your old password">
                                    </div>
                                    <div style="margin-top: -10px; margin-bottom: 15px; text-align: right;">
                                        <a href="javascript:void(0)" onclick="toggleOtpMethod(true)" style="font-size: 0.85rem; color: var(--primary-color);">Forgot current password? Get OTP</a>
                                    </div>
                                </div>

                                <div id="otp-group" style="display: none;">
                                    <div class="form-group">
                                        <label for="otp_code">Enter OTP sent to your email</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" id="otp_code" name="otp_code" placeholder="6-digit code" maxlength="6">
                                            <button type="button" id="send-otp-btn" onclick="sendProfileOtp()" style="padding: 0 15px; border-radius: 10px; background: #eee; border: 1px solid #ddd; cursor: pointer; white-space: nowrap;">Send OTP</button>
                                        </div>
                                    </div>
                                    <div style="margin-top: -10px; margin-bottom: 15px; text-align: right;">
                                        <a href="javascript:void(0)" onclick="toggleOtpMethod(false)" style="font-size: 0.85rem; color: var(--primary-color);">I know my current password</a>
                                    </div>
                                </div>
                                
                                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New</label>
                                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <button type="submit" name="update_profile" class="btn" style="width: 100%; padding: 15px;">
                                    <i class="fas fa-save"></i> Save Profile Changes
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
        window.openEditModal = function() {
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.style.display = 'block';
                modal.classList.add('show');
            }
        };

        window.closeEditModal = function() {
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        };

        window.toggleOtpMethod = function(useOtp) {
            const passGroup = document.getElementById('current-password-group');
            const otpGroup = document.getElementById('otp-group');
            const passInput = document.getElementById('current_password');
            const otpInput = document.getElementById('otp_code');

            if (useOtp) {
                passGroup.style.display = 'none';
                otpGroup.style.display = 'block';
                passInput.value = ''; 
                passInput.removeAttribute('required');
                otpInput.setAttribute('required', 'true');
            } else {
                passGroup.style.display = 'block';
                otpGroup.style.display = 'none';
                otpInput.value = '';
                otpInput.removeAttribute('required');
            }
        };

        window.sendProfileOtp = function() {
            const btn = document.getElementById('send-otp-btn');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Sending...';

            const formData = new FormData();
            formData.append('send_otp_profile', '1');

            fetch('tutor_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast(data.message, 'success');
                    }
                    
                    // Start countdown
                    let timeLeft = 60;
                    const timer = setInterval(() => {
                        btn.innerText = `Resend in ${timeLeft}s`;
                        timeLeft--;
                        if (timeLeft < 0) {
                            clearInterval(timer);
                            btn.disabled = false;
                            btn.innerText = 'Resend OTP';
                        }
                    }, 1000);
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.error, 'error');
                    }
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof showToast === 'function') {
                    showToast('Failed to connect to server.', 'error');
                }
                btn.disabled = false;
                btn.innerText = originalText;
            });
        };

        (function() {
            const form = document.getElementById('profileEditForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalContent = submitBtn.innerHTML;
                    
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                    const formData = new FormData(this);
                    formData.append('update_profile', '1');

                    fetch('tutor_profile.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (typeof showToast === 'function') {
                                showToast(data.message, 'success');
                            }
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            if (typeof showToast === 'function') {
                                showToast(data.error || 'Failed to update profile', 'error');
                            }
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalContent;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (typeof showToast === 'function') {
                            showToast('An error occurred while saving your changes.', 'error');
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalContent;
                    });
                });
            }
        })();
    </script>
</body>
</html>
