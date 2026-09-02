<?php
// Include session check for authentication
require_once 'includes/init.php';
include 'includes/connect.php';
include 'includes/admin_functions.php';
include 'includes/otp_functions.php';
include 'includes/email_sender.php';

// Profile page requires login - redirect if not logged in
if (!checkSession()) {
    header("Location: login-registration.php?redirect=Profile.php&msg=Please+login+to+access+your+profile");
    exit();
}
$session_user = getCurrentUser();
$is_tutor = ($session_user['role'] === 'tutor');

// Fetch fresh user data from database
if ($is_tutor) {
    $user_query = mysqli_query($con, "SELECT * FROM tutors WHERE id = '" . $session_user['id'] . "'");
} else {
    $user_query = mysqli_query($con, "SELECT * FROM users_tbl WHERE id = '" . $session_user['id'] . "'");
}

if (!$user_query || mysqli_num_rows($user_query) == 0) {
    header("Location: login-registration.php?msg=User+not+found");
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
    $new_username = mysqli_real_escape_string($con, trim($_POST['username']));
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $update_fields = [];
    $update_params = [];
    $types = "";

    // Username update
    if (!empty($new_username)) {
        $update_fields[] = "username = ?";
        $update_params[] = $new_username;
        $types .= "s";
    }

    // Profile Picture update
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = 'content/users/profile_pictures/';
        $upload_result = handleFileUpload('profile_picture', $upload_dir, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1024 * 1024);
        
        if (isset($upload_result['path'])) {
            $pic_col = $is_tutor ? "profile_picture" : "image";
            $update_fields[] = "$pic_col = ?";
            $update_params[] = $upload_result['path'];
            $types .= "s";
        } else {
            $message = "<div class='error-msg'>Upload Error: " . $upload_result['error'] . "</div>";
        }
    }

    // Password update
    if (empty($message) && !empty($new_password)) {
        $otp_submitted = $_POST['otp_code'] ?? '';
        $use_otp = !empty($otp_submitted);
        $password_verified = false;

        if ($use_otp) {
            $otp_result = otp_checker($otp_submitted);
            if ($otp_result['isValid']) {
                $password_verified = true;
                // Unset OTP from session after successful use
                unset($_SESSION['otp_hash']);
                unset($_SESSION['plaintext_otp']);
            } else {
                $message = "<div class='error-msg'>Invalid or expired OTP. Please try again.</div>";
            }
        } else {
            // Verify current password first
            $db_pass = $is_tutor ? ($user['password_hash'] ?? $user['password']) : $user['password'];
            if (password_verify($current_password, $db_pass)) {
                $password_verified = true;
            } else {
                $message = "<div class='error-msg'>Incorrect current password.</div>";
            }
        }

        if ($password_verified) {
            if ($new_password === $confirm_password) {
                $hashed_pass = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_col = ($is_tutor && isset($user['password_hash'])) ? "password_hash" : "password";
                $update_fields[] = "$pass_col = ?";
                $update_params[] = $hashed_pass;
                $types .= "s";
            } else {
                $message = "<div class='error-msg'>New passwords do not match.</div>";
            }
        }
    }

    if (empty($message) && !empty($update_fields)) {
        $sql = "UPDATE " . ($is_tutor ? "tutors" : "users_tbl") . " SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_params[] = $session_user['id'];
        $types .= "i";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$update_params);
        
        if ($stmt->execute()) {
            $message = "<div class='success-msg'>Profile updated successfully!</div>";
            // Refresh local user data
            if ($is_tutor) {
                $user_query = mysqli_query($con, "SELECT * FROM tutors WHERE id = '" . $session_user['id'] . "'");
            } else {
                $user_query = mysqli_query($con, "SELECT * FROM users_tbl WHERE id = '" . $session_user['id'] . "'");
            }
            $user = mysqli_fetch_assoc($user_query);
            
            // Update session data
            $_SESSION['username'] = $user['username'];
            if (!$is_tutor) $_SESSION['profile_picture'] = $user['image'] ?? null;
            else $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
            
            // For initUserSession support
            $_SESSION['user_data']['username'] = $user['username'];
            $_SESSION['user_data']['profile_picture'] = $_SESSION['profile_picture'];

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully!', 'username' => $user['username']]);
                exit();
            }
        } else {
            $err_msg = "Database error: " . $stmt->error;
            $message = "<div class='error-msg'>$err_msg</div>";
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err_msg]);
                exit();
            }
        }
    } else if (!empty($message) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        // Strip HTML tags for JSON response if possible or just send the first error
        echo json_encode(['success' => false, 'error' => strip_tags($message)]);
        exit();
    }
}

$is_tutor = ($session_user['role'] === 'tutor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - YogaMart</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <?php if ($is_tutor || (isset($session_user['role']) && $session_user['role'] === 'admin')): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="admin-panel.css?v=<?php echo time(); ?>">
    <?php endif; ?>
    <style>
        .success-msg {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
            margin-bottom: 1.5rem;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <div class="container profile-page" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="card" style="padding: 2rem; max-width: 800px; margin: 0 auto;">
            <?php include 'profile_content.php'; ?>
        </div>

        <!-- Edit Profile Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Profile</h2>
                    <button class="close-btn" onclick="closeEditModal()">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        <div class="form-note">Upload a new profile picture (JPG, PNG, GIF, WEBP - Max 5MB). Leave empty to keep current picture.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? $user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_display">Email</label>
                        <input type="email" id="email_display" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <div class="form-note">Email cannot be changed</div>
                    </div>
                    
                    <div class="password-section">
                        <h3>Change Password (Optional)</h3>
                        <div class="form-note">Leave password fields empty if you don't want to change your password</div>
                        
                        <div id="current-password-group" class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                            <div style="margin-top: 5px; text-align: right;">
                                <a href="javascript:void(0)" onclick="toggleOtpMethod(true)" style="font-size: 0.85rem; color: var(--primary-color);">Forgot current password? Get OTP</a>
                            </div>
                        </div>

                        <div id="otp-group" class="form-group" style="display: none;">
                            <label for="otp_code">Enter OTP sent to your email</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="otp_code" name="otp_code" placeholder="6-digit code" maxlength="6">
                                <button type="button" id="send-otp-btn" onclick="sendProfileOtp()" style="padding: 0 15px; border-radius: 10px; background: #eee; border: 1px solid #ddd; cursor: pointer;">Send OTP</button>
                            </div>
                            <div style="margin-top: 5px; text-align: right;">
                                <a href="javascript:void(0)" onclick="toggleOtpMethod(false)" style="font-size: 0.85rem; color: var(--primary-color);">I know my current password</a>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min 6 characters)" minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password">
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="save-btn">💾 Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Profile Edit Functions
        window.openEditModal = function() {
            document.body.classList.add('modal-open');
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.style.display = 'block';
                modal.classList.add('show');
            } else {
                console.error('editModal not found');
                // Fallback: try to find any modal with edit-profile-form
                const altModal = document.querySelector('.modal');
                if (altModal) {
                    altModal.style.display = 'block';
                    altModal.classList.add('show');
                }
            }
        };

        window.closeEditModal = function() {
            document.body.classList.remove('modal-open');
            const modal = document.getElementById('editModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            } else {
                const altModal = document.querySelector('.modal');
                if (altModal) {
                    altModal.style.display = 'none';
                    altModal.classList.remove('show');
                }
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
                passInput.value = ''; // clear password
                passInput.removeAttribute('required');
                otpInput.setAttribute('required', 'true');
            } else {
                passGroup.style.display = 'block';
                otpGroup.style.display = 'none';
                otpInput.value = ''; // clear otp
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

            fetch('Profile.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    
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
                    showToast(data.error, 'error');
                    btn.disabled = false;
                    btn.innerText = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Failed to connect to server.', 'error');
                btn.disabled = false;
                btn.innerText = originalText;
            });
        };

        (function() {
            const profileForm = document.querySelector('#editModal form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    const username = document.getElementById('username').value.trim();
                    if (username === '') {
                        showToast('Username cannot be empty', 'error');
                        return;
                    }

                    const submitBtn = profileForm.querySelector('.save-btn');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

                    const formData = new FormData(profileForm);
                    formData.append('update_profile', '1');

                    fetch('Profile.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            
                            // Update dynamic elements
                            const profileHeaderName = document.querySelector('.section__head h1');
                            if (profileHeaderName) profileHeaderName.textContent = `Welcome back, ${data.username}! 👋`;
                            
                            const profileCardName = document.querySelector('.profile-card h2');
                            if (profileCardName) profileCardName.textContent = data.username;

                            const welcomeText = document.querySelector('.welcome-text');
                            if (welcomeText) welcomeText.textContent = data.username;

                            closeEditModal();
                            // Optional: reload after a delay to show new profile pic if changed
                            if (formData.get('profile_picture') && formData.get('profile_picture').size > 0) {
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        } else {
                            showToast(data.error || 'Update failed', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        showToast('An unexpected error occurred.', 'error');
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });
            }
        })();
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

