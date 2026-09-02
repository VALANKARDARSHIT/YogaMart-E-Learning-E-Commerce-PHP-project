<?php
    // init.php handles session configuration and starts the session.
    // It must be included before any session-related functions are used.
    include '../includes/init.php'; 
    include '../includes/otp_functions.php';
    include '../includes/email_sender.php';
    include '../includes/connect.php';
    
    // AJAX Handling Logic
    if ($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        $response = [];

        // Check if an OTP is already in the session, implying this is an OTP verification attempt
        if (isset($_SESSION['otp_hash']) && !empty($_POST['login_otp'])) {
            $submitted_otp = trim($_POST['login_otp']);
            $otp_time = $_SESSION['otp_time'] ?? time();

            $otp_check_result = otp_checker($submitted_otp);
            if ($otp_check_result['isValid']) {
                $user = $_SESSION['login_user_data'];
                
                $user_session_data = [
                    'id' => $user['id'] ?? $user['tutor_id'] ?? null,
                    'username' => $user['username'] ?? $user['name'] ?? $user['full_name'] ?? '',
                    'email' => $user['email'] ?? '',
                    'role' => 'tutor',
                    'status' => $user['status'] ?? 'pending',
                    'auth_source' => 'tutors',
                    'profile_picture' => $user['profile_picture'] ?? null
                ];

                // Clear all temporary OTP and login session data
                unset($_SESSION['login_user_data']);

                if (initUserSession($user_session_data)) {
                    regenerateSessionId();
                    setMessage('Welcome back! You have been successfully logged in.', 'success');
                    $response = ['success' => true, 'redirect' => 'tutor_dashboard.php'];
                } else {
                    $response = ['success' => false, 'error' => 'Login failed. Could not initialize session.'];
                }
            } else {
                $response = ['success' => false, 'error' => $otp_check_result['error'] ?? 'Invalid OTP. Please try again.'];
            }
        }

        // Initial login attempt
        else if (isset($_POST['loginbtn'])) {  
            $login_email = trim($_POST['login_email']);
            $login_password = trim($_POST['login_password']);

            if (empty($login_email) || empty($login_password)) {
                $response = ['success' => false, 'error' => 'Email and password are required.'];
            } elseif (!filter_var($login_email, FILTER_VALIDATE_EMAIL)) {
                $response = ['success' => false, 'error' => 'Invalid email format.'];
            } elseif ($con) {
                $login_query = "SELECT * FROM tutors WHERE email=?";
                $login_stmt = $con->prepare($login_query);
                $login_stmt->bind_param('s', $login_email);
                $login_stmt->execute();
                $result = $login_stmt->get_result();

                if ($result && $result->num_rows == 1) {
                    $tutor = $result->fetch_assoc();
                    if (password_verify($login_password, $tutor['password_hash'])) {
                        // Generate OTP and store hash and plaintext in session
                        list($otp, $hash) = otp_Generator(); 
                        
                        // Set temporary session data for the login attempt
                        $_SESSION['login_user_data'] = $tutor;
                        $_SESSION['otp_time'] = time();
                        $_SESSION['otp_hash'] = $hash;
                        
                        // Send the plaintext OTP via email
                        $otp_sent = Email_sender($otp, $login_email, "Tutor Login OTP - YogaMart");
                        
                        if ($otp_sent) {
                            $response = ['success' => true, 'message' => 'OTP Sent. Please check your email.'];
                        } else {
                             if (getenv('SMTP_USERNAME') === 'your_email@gmail.com') {
                                $response = ['success' => false, 'error' => 'The application is not configured to send emails.'];
                            } else {
                                $response = ['success' => false, 'error' => 'Failed to send OTP.'];
                            }
                        }
                    } else {
                        $response = ['success' => false, 'error' => 'Incorrect password.'];
                    }
                } else {
                    $response = ['success' => false, 'error' => 'Tutor not found.'];
                }
                $login_stmt->close();
            } else {
                $response = ['success' => false, 'error' => 'Database connection error.'];
            }
        }
        // --- FORGOT PASSWORD EMAIL (TUTOR) ---
        elseif (isset($_POST['forgot_email'])) {
            $email = trim($_POST['forgot_email']);
            if (empty($email)) {
                $response = ['success' => false, 'error' => 'Email is required.'];
            } else {
                $stmt = $con->prepare("SELECT id FROM tutors WHERE email=?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows == 1) {
                    list($otp, $hash) = otp_Generator();
                    $_SESSION['forgot_otp_hash'] = $hash;
                    $_SESSION['forgot_user_email'] = $email;
                    $_SESSION['forgot_otp_time'] = time();
                    $_SESSION['is_tutor_context'] = true;
                    
                    if (Email_sender($otp, $email, "Tutor Password Reset OTP - YogaMart")) {
                        $response = ['success' => true, 'message' => 'OTP Sent.'];
                    } else {
                        $response = ['success' => false, 'error' => 'Failed to send OTP.'];
                    }
                } else {
                    $response = ['success' => false, 'error' => 'Tutor email not found.'];
                }
            }
        }
        // --- FORGOT PASSWORD OTP VERIFICATION (TUTOR) ---
        elseif (isset($_POST['forgot_otp'])) {
            $submitted_otp = trim($_POST['forgot_otp']);
            if (!isset($_SESSION['forgot_user_email']) || !isset($_SESSION['is_tutor_context'])) {
                $response = ['success' => false, 'error' => 'Session lost. Please try again.'];
            } else {
                $otp_time = $_SESSION['forgot_otp_time'];
                if (time() - $otp_time > 300) {
                    unset($_SESSION['forgot_user_email'], $_SESSION['forgot_otp_time'], $_SESSION['forgot_otp_hash'], $_SESSION['is_tutor_context']);
                    $response = ['success' => false, 'error' => 'OTP has expired.'];
                } else {
                    $otp_check_result = otp_checker($submitted_otp, 'forgot_otp_hash');
                    if ($otp_check_result['isValid']) {
                        $_SESSION['can_reset_password_tutor'] = $_SESSION['forgot_user_email'];
                        unset($_SESSION['forgot_otp_hash'], $_SESSION['forgot_otp_time']);
                        $response = ['success' => true, 'show_reset_modal' => true];
                    } else {
                        $response = ['success' => false, 'error' => 'Invalid OTP.'];
                    }
                }
            }
        }
        // --- RESET PASSWORD FINAL SUBMISSION (TUTOR) ---
        elseif (isset($_POST['reset_password_btn'])) {
            $new_pass = $_POST['new_password'] ?? '';
            $cnf_pass = $_POST['cnf_password'] ?? '';
            $email = $_SESSION['can_reset_password_tutor'] ?? '';

            if (!$email) {
                $response = ['success' => false, 'error' => 'Unauthorized or session expired.'];
            } elseif (empty($new_pass) || strlen($new_pass) < 6) {
                $response = ['success' => false, 'error' => 'Password must be at least 6 characters.'];
            } elseif ($new_pass !== $cnf_pass) {
                $response = ['success' => false, 'error' => 'Passwords do not match.'];
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $con->prepare("UPDATE tutors SET password_hash=? WHERE email=?");
                $stmt->bind_param('ss', $hashed, $email);
                if ($stmt->execute()) {
                    unset($_SESSION['can_reset_password_tutor'], $_SESSION['forgot_user_email'], $_SESSION['is_tutor_context']);
                    $response = ['success' => true, 'message' => 'Password reset successful! You can now login.'];
                } else {
                    $response = ['success' => false, 'error' => 'Database update failed.'];
                }
            }
        }
        echo json_encode($response);
        exit();
    }
    // Fallback for non-AJAX request or initial page load
    $loginError = '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>YogaMart| Tutor Login</title>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Open+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/login-registration.css" />
    <?php include '../includes/toast_notification.php'; ?>
    <style>
        /* Override styles for single-form page */
        .login-container {
            position: relative !important;
            opacity: 1 !important;
            transform: none !important;
            pointer-events: all !important;
            top: 0 !important;
        }
        .error-message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: none; /* Hidden by default */
        }
    </style>
</head>

<body>

    <div class="container" id="appContainer">
        <div class="brand-side">
            <div class="logo">YogaMart</div>
            <div class="yoga-icon">🧘</div>
            <p class="brand-text">Share your knowledge and passion for yoga with a vibrant community of learners.</p>

            <div class="trial-info">
                <h3>Become a Tutor</h3>
                <p>Create and manage your courses</p>
                <div class="features">
                    <div class="feature">Upload Videos</div>
                    <div class="feature">Track your progress</div>
                </div>
            </div>

            <div class="tutor-link-box" style="margin-top: 20px; text-align: center;">
                <p style="margin-bottom: 10px; font-weight: bold; color: #3d4b42;">Are you a User?</p>
                <a href="../login-registration.php" class="tutor-btn"
                    style="display: inline-block; padding: 10px 20px; background-color: #7a9c8f; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background-color 0.3s ease;">
                    User Login / Register
                </a>
            </div>

            <div class="leaf leaf-1">🍃</div>
            <div class="leaf leaf-2">🍃</div>
        </div>

        <div class="forms-side">
            <div class="forms-container">

                <!-- Login Form -->
                <div class="form login-container" id="loginContainer">
                    <h2 class="form-title">Tutor Login</h2>
                    <p class="subheading">Sign in to manage your courses</p>
                    <div id="login-error-message" class="error-message"></div>
                    <form id="login-form" action="tutor_login.php" method="post">

                        <div class="input-group">
                            <div class="box">
                                <i class="fas fa-user"></i>
                                <input type="text" id="login-email" name="login_email" placeholder="Email" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <div class="box">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="login-password" name="login_password" placeholder="Password" required>
                                <span class="password-toggle" onclick="togglePassword('login-password', this)">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <input type="hidden" name="login_otp" id="login-otp-hidden">

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                            <label style="display:flex;align-items:center;gap:8px;color:#5b7164;font-size:14px">
                                <input type="checkbox" id="login-remember"> Remember
                            </label>
                            <div class="forgot-link">
                                <a onclick="openForgot()">Forgot your password?</a>
                            </div>
                        </div>

                        <button type="submit" class="btn" name="loginbtn">Sign In</button>
                    </form>
                    <div class="form-footer">Don't have an account? <a href="tutor_registration.php">Register here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Modal (hidden by default) -->
    <div class="modal-backdrop" id="otpModal" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="otpTitle">
            <h3 id="otpTitle">Enter OTP</h3>
            <p>Please enter the One-Time Password sent to your email.</p>

            <div style="margin:12px 0 18px">
                <input id="otp-input" type="text" maxlength="6" placeholder="Enter OTP"
                    style="width:100%; padding:12px 14px; border-radius:10px; border:1px solid #e6efe7; background:#fbfdfb; font-size:18px; letter-spacing:8px; text-align:center;">
            </div>
            <div id="otp-error" class="error-message" style="margin-top:10px;"></div>

            <div style="display:flex;gap:10px">
                <button class="btn" style="flex:1" id="otp-verify-btn" onclick="handleOtpSubmit()">Verify OTP</button>
                <button type="button" onclick="closeOtp()" style="flex:0 0 100px; border-radius:10px; border:none; background:#f2f4f2; color:#3d4b42; cursor:pointer">Cancel</button>
            </div>
        </div>
    </div>
    
    <!-- Forgot Password Modal (hidden by default) -->
    <div class="modal-backdrop" id="forgotModal" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
            <h3 id="forgotTitle">Reset your password</h3>
            <p>Enter your tutor email and we'll send a reset link.</p>

            <div style="margin:12px 0 18px">
                <input id="forgot-email" type="email" placeholder="Your tutor email"
                    style="width:100%; padding:12px 14px; border-radius:10px; border:1px solid #e6efe7; background:#fbfdfb">
            </div>

            <div style="display:flex;gap:10px">
                <button class="btn" style="flex:1" onclick="submitForgot(this)">Send Reset Link</button>
                <button type="button" onclick="closeForgot()" style="flex:0 0 100px; border-radius:10px; border:none; background:#f2f4f2; color:#3d4b42; cursor:pointer">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal (hidden by default) -->
    <div class="modal-backdrop" id="resetPasswordModal" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="resetTitle" style="width: 450px; padding: 30px; box-sizing: border-box;">
            <h3 id="resetTitle">Set New Password</h3>
            <p>Please enter your new password below.</p>

            <div class="input-group" style="margin-bottom: 25px; display: flex; justify-content: center;">
                <div class="box" style="width: 320px; max-width: 100%; position: relative;">
                    <i class="fas fa-lock"></i>
                    <input id="reset-new-password" type="password" placeholder="New Password"
                        style="width:100%; padding:12px 55px 12px 46px; border-radius:10px; border:1px solid #e6efe7; background:#fbfdfb; box-sizing: border-box;">
                    <span class="password-toggle" style="right: 8px;" onclick="togglePassword('reset-new-password', this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            <div class="input-group" style="margin-bottom: 30px; display: flex; justify-content: center;">
                <div class="box" style="width: 320px; max-width: 100%; position: relative;">
                    <i class="fas faswsqawsqwwwwwwwwwwwwwwwwwww-lock"></i>
                    <input id="reset-cnf-paswsword" type="password" placeholder="Confirm New Password"
                        style="width:100%; padding:12px 55px 12px 46px; border-radius:10px; border:1px solid #e6efe7; background:#fbfdfb; box-sizing: border-box;">
                    <span class="password-toggle" style="right: 8px;" onclick="togglePassword('reset-cnf-password', this)">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <div style="display:flex;gap:10px">
                <button class="btn" style="flex:1" id="reset-btn" onclick="submitResetPassword(this)">Reset Password</button>
                <button type="button" onclick="document.getElementById('resetPasswordModal').style.display='none'"
                    style="flex:0 0 100px; border-radius:10px; border:none; background:#f2f4f2; color:#3d4b42; cursor:pointer">Cancel</button>
            </div>
            <div id="reset-error" class="error-message" style="display:none; margin-top:10px; color:red;"></div>
        </div>
    </div>

    <!-- Popup -->
    <div id="popup" class="popup-overlay" style="display:none;">
        <div class="popup-box">
            <h2 id="popup-title"></h2>
            <p id="popup-body"></p>
            <button onclick="closePopup()">OK</button>
        </div>
    </div>

    <script>
    window.isForgotOtpContext = false;

    document.addEventListener('DOMContentLoaded', function() {
        // Persist Reset Modal on refresh if session allows
        <?php if (isset($_SESSION['can_reset_password_tutor'])): ?>
        document.getElementById('resetPasswordModal').style.display = 'flex';
        <?php endif; ?>

        const loginForm = document.getElementById('login-form');
        const loginError = document.getElementById('login-error-message');

        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            window.isForgotOtpContext = false; // Reset context
            const submitButton = loginForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Signing In...';

            const formData = new FormData(loginForm);
            formData.append('loginbtn', '1');

            fetch('tutor_login.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error || 'Server error');
                    }).catch(() => {
                        throw new Error('Network response was not ok, and could not parse error.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    loginError.style.display = 'none';
                    // On successful credential check, open OTP modal
                    openOtp();
                } else {
                    loginError.textContent = data.error;
                    loginError.style.display = 'block';
                }
            })
            .catch(error => {
                loginError.textContent = error.message || 'An unexpected error occurred. Please try again.';
                loginError.style.display = 'block';
                console.error('Login Error:', error);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Sign In';
            });
        });
    });

    function handleOtpSubmit() {
        const otpInput = document.getElementById('otp-input');
        const otpError = document.getElementById('otp-error');
        const otpValue = otpInput.value.trim();
        const verifyButton = document.getElementById('otp-verify-btn');

        if (!/^\d{6}$/.test(otpValue)) {
            otpError.textContent = 'Please enter a valid 6-digit OTP.';
            otpError.style.display = 'block';
            return;
        }
        
        verifyButton.disabled = true;
        verifyButton.textContent = 'Verifying...';
        otpError.style.display = 'none';

        const formData = new FormData();
        if (window.isForgotOtpContext) {
            formData.append('forgot_otp', otpValue);
        } else {
            formData.append('login_otp', otpValue);
            formData.append('login_email', document.getElementById('login-email').value);
        }

        fetch('tutor_login.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.show_reset_modal) {
                    closeOtp();
                    document.getElementById('resetPasswordModal').style.display = 'flex';
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                otpError.textContent = data.error;
                otpError.style.display = 'block';
            }
        })
        .catch(error => {
            otpError.textContent = 'An unexpected error occurred during OTP verification.';
            otpError.style.display = 'block';
        })
        .finally(() => {
            verifyButton.disabled = false;
            verifyButton.textContent = 'Verify OTP';
        });
    }

    function openOtp() {
        document.getElementById('otpModal').style.display = 'flex';
        document.getElementById('otp-input').value = '';
        document.getElementById('otp-error').style.display = 'none';
        document.getElementById('otp-input').focus();
    }

    function closeOtp() {
        document.getElementById('otpModal').style.display = 'none';
    }

    function openForgot() {
        document.getElementById('forgotModal').style.display = 'flex';
    }

    function closeForgot() {
        document.getElementById('forgotModal').style.display = 'none';
    }

    function submitForgot(btn) {
        const email = document.getElementById('forgot-email').value.trim();
        if (!email) { showToast('Please enter your email.', 'error'); return; }
        
        btn.disabled = true;
        btn.textContent = 'Sending...';

        const formData = new FormData();
        formData.append('forgot_email', email);

        fetch('tutor_login.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeForgot();
                showPopup("OTP Sent", "A password reset OTP has been sent to your tutor email.");
            } else {
                showToast(data.error || 'Failed to send OTP.', 'error');
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Send Reset Link';
        });
    }

    function submitResetPassword(btn) {
        const newPass = document.getElementById('reset-new-password').value;
        const cnfPass = document.getElementById('reset-cnf-password').value;
        const errorEl = document.getElementById('reset-error');

        if (newPass.length < 6) {
            errorEl.textContent = 'Password must be at least 6 characters.';
            errorEl.style.display = 'block';
            return;
        }
        if (newPass !== cnfPass) {
            errorEl.textContent = 'Passwords do not match.';
            errorEl.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Resetting...';

        const formData = new FormData();
        formData.append('reset_password_btn', '1');
        formData.append('new_password', newPass);
        formData.append('cnf_password', cnfPass);

        fetch('tutor_login.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                window.location.reload();
            } else {
                errorEl.textContent = data.error || 'Failed to reset password.';
                errorEl.style.display = 'block';
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Reset Password';
        });
    }

    function showPopup(title, body) {
        document.getElementById('popup-title').textContent = title;
        document.getElementById('popup-body').textContent = body;
        document.getElementById('popup').style.display = 'flex';
    }

    function closePopup() {
        document.getElementById('popup').style.display = 'none';
        window.isForgotOtpContext = true;
        openOtp();
    }
    
    // Utility functions (can be kept as is)
    function togglePassword(id, el) {
        const input = document.getElementById(id);
        const icon = el.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>

</body>

</html>


