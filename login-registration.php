<?php
  include 'includes/init.php';
  include 'includes/otp_functions.php';
  include 'includes/email_sender.php';
  include 'includes/connect.php';

  // Prevent logged-in users from accessing the login page
  if (isLoggedIn()) {
      $user = getCurrentUser();
      if ($user['role'] === 'tutor') {
          header("Location: tutor/tutor_page.php");
      } elseif ($user['role'] === 'admin') {
          header("Location: admin/admin-panel.php");
      } else {
          header("Location: home.php");
      }
      exit();
  }

  // AJAX Handling Logic
  if ($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = [];

    // --- REGISTRATION OTP VERIFICATION ---
    if (isset($_POST['otp']) && !empty($_POST['otp'])) {
        $user_otp = trim($_POST['otp']);
        if (!isset($_SESSION['reg_data'])) {
            echo json_encode(['success' => false, 'error' => 'Registration session lost. Please try again.']);
            exit();
        }

        $otp_check_result = otp_checker($user_otp);
        if ($otp_check_result['isValid']) {
            $username = $_SESSION['reg_data']['username'];
            $email = $_SESSION['reg_data']['email'];
            $role = $_SESSION['reg_data']['role'];
            $password = $_SESSION['reg_data']['password_hash'];

            $query = "INSERT INTO users_tbl (username, email, role, password) VALUES(?,?,?,?)";
            $stmt = $con->prepare($query);
            if ($stmt) {
                $stmt->bind_param('ssss', $username, $email, $role, $password);
                if ($stmt->execute()) {
                    unset($_SESSION['reg_data']);
                    $response = ['success' => true, 'redirect' => 'home.php?msg=Registration+Successful'];
                } else {
                    $response = ['success' => false, 'error' => 'Registration failed: ' . $stmt->error];
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'error' => 'Database error.'];
            }
        } else {
            $response = ['success' => false, 'error' => 'Invalid OTP.'];
        }
    }
    // --- LOGIN OTP VERIFICATION ---
    elseif (isset($_POST['login_otp']) && !empty($_POST['login_otp'])) {
        $submitted_otp = trim($_POST['login_otp']);
        if (!isset($_SESSION['login_user_data'])) {
            echo json_encode(['success' => false, 'error' => 'Login session lost. Please try again.']);
            exit();
        }

        $otp_time = $_SESSION['login_otp_time'];
        if (time() - $otp_time > 300) {
            unset($_SESSION['login_user_data'], $_SESSION['login_otp_time']);
            $response = ['success' => false, 'error' => 'OTP has expired.'];
        } else {
            $otp_check_result = otp_checker($submitted_otp, 'login_otp_hash');
            if ($otp_check_result['isValid']) {
                $user = $_SESSION['login_user_data'];
                $user_session_data = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'profile_picture' => $user['image'] ?? null
                ];
                unset($_SESSION['login_user_data'], $_SESSION['login_otp_time'], $_SESSION['login_otp_hash']);
                
                if (initUserSession($user_session_data)) {
                    regenerateSessionId();
                    setMessage('Welcome back!', 'success');
                    
                    // Check if a specific redirect was requested
                    $redirect = $_GET['redirect'] ?? 'home.php';
                    
                    // Default role-based redirects if no specific redirect is set
                    if (!isset($_GET['redirect'])) {
                        if ($user_session_data['role'] === 'tutor') $redirect = 'tutor/tutor_page.php';
                        elseif ($user_session_data['role'] === 'admin') $redirect = 'admin/admin-panel.php';
                    }
                    
                    $response = ['success' => true, 'redirect' => $redirect];
                } else {
                    $response = ['success' => false, 'error' => 'Session initialization failed.'];
                }
            } else {
                $response = ['success' => false, 'error' => 'Invalid OTP.'];
            }
        }
    }
    // --- INITIAL REGISTRATION SUBMISSION ---
    elseif (isset($_POST['createbtn'])) {
        $username = trim($_POST['new_name'] ?? '');
        $email = trim($_POST['new_email'] ?? '');
        $rawPassword = $_POST['new_password'] ?? '';
        $cnfpassword = $_POST['new_cnfpassword'] ?? '';
        $terms = $_POST['terms'] ?? '';

        if (empty($username) || empty($email) || empty($rawPassword)) {
            $response = ['success' => false, 'error' => 'All fields are required.'];
        } else if ($rawPassword !== $cnfpassword) {
            $response = ['success' => false, 'error' => 'Passwords do not match.'];
        } else if ($terms !== 'on') {
            $response = ['success' => false, 'error' => 'You must agree to terms.'];
        } else {
            $check_stmt = $con->prepare("SELECT email FROM users_tbl WHERE email=?");
            $check_stmt->bind_param('s', $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $response = ['success' => false, 'error' => 'Email already registered.'];
            } else {
                list($otp, $hash) = otp_Generator();
                if (Email_sender($otp, $email)) {
                    $_SESSION['reg_data'] = [
                        'username' => $username,
                        'email' => $email,
                        'role' => 'user',
                        'password_hash' => password_hash($rawPassword, PASSWORD_DEFAULT)
                    ];
                    $response = ['success' => true, 'message' => 'OTP Sent.'];
                } else {
                    $response = ['success' => false, 'error' => 'Failed to send OTP.'];
                }
            }
        }
    }
    // --- INITIAL LOGIN SUBMISSION ---
    elseif (isset($_POST['loginbtn'])) {
        $email = trim($_POST['login_email'] ?? '');
        $password = $_POST['login_password'] ?? '';

        $stmt = $con->prepare("SELECT * FROM users_tbl WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows == 1) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                list($otp, $hash) = otp_Generator();
                $_SESSION['login_otp_hash'] = $hash;
                $_SESSION['login_user_data'] = $user;
                $_SESSION['login_otp_time'] = time();
                if (Email_sender($otp, $email, "Login OTP - YogaMart")) {
                            $response = ['success' => true, 'message' => 'Login OTP Sent.'];
                        } else {
                            $response = ['success' => false, 'error' => 'Failed to send OTP.'];
                        }
                    } else {
                        $response = ['success' => false, 'error' => 'Invalid password.'];
                    }
                } else {
                    $response = ['success' => false, 'error' => 'User not found.'];
                }
                }
                // --- FORGOT PASSWORD EMAIL ---
                elseif (isset($_POST['forgot_email'])) {
                $email = trim($_POST['forgot_email']);
                if (empty($email)) {
                    $response = ['success' => false, 'error' => 'Email is required.'];
                } else {
                    $stmt = $con->prepare("SELECT id, username FROM users_tbl WHERE email=?");
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res->num_rows == 1) {
                        $user = $res->fetch_assoc();
                        list($otp, $hash) = otp_Generator();
                        $_SESSION['forgot_otp_hash'] = $hash;
                        $_SESSION['forgot_user_email'] = $email;
                        $_SESSION['forgot_otp_time'] = time();

                        if (Email_sender($otp, $email, "Password Reset OTP - YogaMart")) {
                            $response = ['success' => true, 'message' => 'OTP Sent.'];
                        } else {
                            $response = ['success' => false, 'error' => 'Failed to send OTP.'];
                        }
                    } else {
                        // To prevent email enumeration, you might want to return success anyway, 
                        // but usually for internal tools it's fine to show an error.
                        $response = ['success' => false, 'error' => 'Email not found in our records.'];
                    }
                }
                }
                // --- FORGOT PASSWORD OTP VERIFICATION ---
                elseif (isset($_POST['forgot_otp'])) {
                $submitted_otp = trim($_POST['forgot_otp']);
                if (!isset($_SESSION['forgot_user_email'])) {
                    echo json_encode(['success' => false, 'error' => 'Session lost. Please try again.']);
                    exit();
                }

                $otp_time = $_SESSION['forgot_otp_time'];
                if (time() - $otp_time > 300) {
                    unset($_SESSION['forgot_user_email'], $_SESSION['forgot_otp_time'], $_SESSION['forgot_otp_hash']);
                    $response = ['success' => false, 'error' => 'OTP has expired.'];
                } else {
                    $otp_check_result = otp_checker($submitted_otp, 'forgot_otp_hash');
                    if ($otp_check_result['isValid']) {
                        $_SESSION['can_reset_password'] = $_SESSION['forgot_user_email'];
                        unset($_SESSION['forgot_otp_hash'], $_SESSION['forgot_otp_time']);
                        $response = ['success' => true, 'show_reset_modal' => true];
                    } else {
                        $response = ['success' => false, 'error' => 'Invalid OTP.'];
                    }
                }
                }
                // --- RESET PASSWORD FINAL SUBMISSION ---
                elseif (isset($_POST['reset_password_btn'])) {
                $new_pass = $_POST['new_password'] ?? '';
                $cnf_pass = $_POST['cnf_password'] ?? '';
                $email = $_SESSION['can_reset_password'] ?? '';

                if (!$email) {
                    $response = ['success' => false, 'error' => 'Unauthorized or session expired.'];
                } elseif (empty($new_pass) || strlen($new_pass) < 6) {
                    $response = ['success' => false, 'error' => 'Password must be at least 6 characters.'];
                } elseif ($new_pass !== $cnf_pass) {
                    $response = ['success' => false, 'error' => 'Passwords do not match.'];
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $con->prepare("UPDATE users_tbl SET password=? WHERE email=?");
                    $stmt->bind_param('ss', $hashed, $email);
                    if ($stmt->execute()) {
                        unset($_SESSION['can_reset_password'], $_SESSION['forgot_user_email']);
                        $response = ['success' => true, 'message' => 'Password reset successful! You can now login.'];
                    } else {
                        $response = ['success' => false, 'error' => 'Database update failed.'];
                    }
                }
                }

                echo json_encode($response);
                exit();
                }

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>YogaMart| Login</title>

  <!-- Fonts & Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Open+Sans:wght@300;400;500&display=swap"
    rel="stylesheet">

  <script src="assets/js/login-registration-validation.js"></script>
  <link rel="stylesheet" href="assets/css/login-registration.css?v=<?php echo time(); ?>" />
  <?php include 'includes/toast_notification.php'; ?>
  <script>
    // Global state
    window.isLoginOtpContext = false;
    window.isForgotOtpContext = false;

    // Restore last active form on page load
    window.addEventListener('DOMContentLoaded', function () {
      const lastForm = localStorage.getItem('activeForm');
      if (lastForm === 'register') {
        showRegister();
      } else {
        showLogin();
      }

      // Re-open Reset Password Modal if the session allows it (persists across refresh)
      <?php if (isset($_SESSION['can_reset_password'])): ?>
      document.getElementById('resetPasswordModal').style.display = 'flex';
      <?php endif; ?>
    });

    // Toggle Form visibility
    function showRegister() {
      document.getElementById('appContainer').classList.add('register-active');
      localStorage.setItem('activeForm', 'register');
    }
    function showLogin() {
      document.getElementById('appContainer').classList.remove('register-active');
      localStorage.setItem('activeForm', 'login');
    }

    // Main AJAX Form Handler
    document.addEventListener('DOMContentLoaded', function() {
      // Login Form Handler
      const loginForm = document.querySelector('#loginContainer form');
      if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
          e.preventDefault();
          clearErrorMessages();
          
          const email = document.getElementById('login-email').value.trim();
          const password = document.getElementById('login-password').value;

          let hasError = false;
          if (!email) { showError('login-email', 'Email is required.'); hasError = true; }
          if (!password) { showError('login-password', 'Password is required.'); hasError = true; }
          
          if (hasError) return;

          const btn = loginForm.querySelector('button[type="submit"]');
          btn.disabled = true;
          btn.textContent = 'Signing In...';

          const formData = new FormData(loginForm);
          formData.append('loginbtn', '1');

          fetch('login-registration.php', {
              method: 'POST',
              headers: { 'X-Requested-With': 'XMLHttpRequest' },
              body: formData
          })
          .then(res => res.json())
          .then(data => {
              if (data.success) {
                  window.isLoginOtpContext = true;
                  showPopup("OTP Sent", "Please check your email for the Login OTP.");
              } else {
                  showGlobalToast(data.error || 'Login failed', 'error');
              }
          })
          .catch(err => showGlobalToast('Network error. Please try again.', 'error'))
          .finally(() => {
              btn.disabled = false;
              btn.textContent = 'Sign In';
          });
        });
      }

      // Registration Form Handler
      const registerForm = document.querySelector('#registerContainer form');
      if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
          e.preventDefault();
          clearErrorMessages();
          
          const name = document.getElementById('register-name').value.trim();
          const email = document.getElementById('register-email').value.trim();
          const password = document.getElementById('register-password').value;
          const cnf = document.getElementById('confirm-password').value;
          const terms = document.getElementById('terms').checked;

          let hasError = false;
          if (!name) { showError('register-name', 'Full Name is required.'); hasError = true; }
          if (!email) { showError('register-email', 'Email address is required.'); hasError = true; }
          else if (!isValidEmail(email)) { showError('register-email', 'Please enter a valid email.'); hasError = true; }
          
          if (!password) { showError('register-password', 'Password is required.'); hasError = true; }
          else if (password.length < 6) { showError('register-password', 'Password must be at least 6 characters.'); hasError = true; }
          
          if (password !== cnf) { showError('confirm-password', 'Passwords do not match.'); hasError = true; }
          if (!terms) { showError('terms', 'You must agree to the terms.'); hasError = true; }

          if (hasError) return;

          const btn = registerForm.querySelector('button[type="submit"]');
          btn.disabled = true;
          btn.textContent = 'Processing...';

          const formData = new FormData(registerForm);
          formData.append('createbtn', '1');

          fetch('login-registration.php', {
              method: 'POST',
              headers: { 'X-Requested-With': 'XMLHttpRequest' },
              body: formData
          })
          .then(res => res.json())
          .then(data => {
              if (data.success) {
                  window.isLoginOtpContext = false;
                  showPopup("Account Created", "An OTP has been sent to " + email + ". Please verify to complete registration.");
              } else {
                  showGlobalToast(data.error || 'Registration failed', 'error');
              }
          })
          .catch(err => showGlobalToast('Network error. Please try again.', 'error'))
          .finally(() => {
              btn.disabled = false;
              btn.textContent = 'Create Account';
          });
        });
      }
    });

    // Handle OTP Verification Submission
    function handleOtpSubmit() {
        const otpInput = document.getElementById('otp-input');
        const otpError = document.getElementById('otp-error');
        const otpValue = otpInput.value.trim();
        const btn = document.getElementById('otp-verify-btn');

        if (!/^\d{6}$/.test(otpValue)) {
            otpError.textContent = 'Enter a valid 6-digit OTP.';
            otpError.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Verifying...';

        const formData = new FormData();
        if (window.isLoginOtpContext) {
            formData.append('login_otp', otpValue);
        } else if (window.isForgotOtpContext) {
            formData.append('forgot_otp', otpValue);
        } else {
            formData.append('otp', otpValue);
        }

        fetch('login-registration.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.show_reset_modal) {
                    closeOtp();
                    document.getElementById('resetPasswordModal').style.display = 'flex';
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = 'home.php';
                }
            } else {
                otpError.textContent = data.error || 'Invalid OTP';
                otpError.style.display = 'block';
            }
        })
        .catch(err => showGlobalToast('An error occurred during verification.', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Verify OTP';
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

        fetch('login-registration.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showGlobalToast(data.message, 'success');
                window.location.reload();
            } else {
                errorEl.textContent = data.error || 'Failed to reset password.';
                errorEl.style.display = 'block';
            }
        })
        .catch(err => showGlobalToast('Network error.', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Reset Password';
        });
    }

    // Modal Control Helpers
    function openForgot() { document.getElementById('forgotModal').style.display = 'flex'; }
    function closeForgot() { document.getElementById('forgotModal').style.display = 'none'; }
    
    function closeOtp() { 
        document.getElementById('otpModal').style.display = 'none'; 
        document.getElementById('otp-error').style.display = 'none';
    }
  </script>
</head>

<body>

  <div class="container" id="appContainer">
    <div class="brand-side">
      <div class="logo">YogaMart</div>
      <div class="yoga-icon">🧘</div>
      <p class="brand-text">Quick morning energizers, cozy stretch breaks, and calming breath—designed to make you
        smile.</p>

      <div class="trial-info">
        <h3>Start 7-day free trial</h3>
        <p>Browse classes</p>
        <div class="features">
          <div class="feature">Beginner-friendly</div>
          <div class="feature">No props needed</div>
        </div>
        <p style="margin-top:10px; color:#566e5f">10–40 min</p>
      </div>

      <div class="tutor-link-box" style="margin-top: 20px; text-align: center;">
        <p style="margin-bottom: 10px; font-weight: bold; color: #3d4b42;">Are you a Tutor?</p>
        <a href="tutor/tutor_login.php" class="tutor-btn" style="display: inline-block; padding: 10px 20px; background-color: #7a9c8f; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background-color 0.3s ease;">
          Tutor Login / Register
        </a>
      </div>

      <div class="leaf leaf-1">🍃</div>
      <div class="leaf leaf-2">🍃</div>
    </div>

    <div class="forms-side">
      <div class="forms-container">

        <!-- Login Form -->
        <div class="form login-container" id="loginContainer">
          <h2 class="form-title">Welcome Back</h2>
          <p class="subheading">Sign in to continue your journey</p>
          <form action="" method="post">

            <div class="input-group">
              <div class="box">
                <i class="fas fa-user"></i>
                <input type="text" id="login-email" name="login_email" placeholder="Email">
              </div>
            </div>

            <div class="input-group">
              <div class="box">
                <i class="fas fa-lock"></i>
                <input type="password" id="login-password" name="login_password" placeholder="Password">
                <span class="password-toggle" onclick="togglePassword('login-password', this)">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>

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
          <div class="form-footer">New to YogaMart? <a onclick="showRegister()">Create Account</a></div>
        </div>

        <!-- Registration Form -->
        <div class="form register-container" id="registerContainer">
          <h2 class="form-title">Begin Your Journey</h2>
          <p class="subheading">Create an account to get started</p>
          <form method="post">

            <div class="input-group">
              <div class="box">
                <i class="fas fa-user"></i>
                <input type="text" id="register-name" name="new_name" placeholder="Full Name">
              </div>
            </div>

            <div class="input-group">
              <div class="box">
                <i class="fas fa-envelope"></i>
                <input type="email" id="register-email" name="new_email" placeholder="Email Address">
                </div>
            </div>

            <div class="input-group">
              <div class="box">
                <i class="fas fa-lock"></i>
                <input type="password" id="register-password" name="new_password" placeholder="Password">
                <span class="password-toggle" onclick="togglePassword('register-password', this)"><i
                class="fas fa-eye"></i></span>
              </div>
            </div>

            <div class="input-group">
              <div class="box">
                <i class="fas fa-lock"></i>
                <input type="password" id="confirm-password" name="new_cnfpassword" placeholder="Confirm Password">
                <span class="password-toggle" onclick="togglePassword('confirm-password', this)"><i
                class="fas fa-eye"></i></span>
              </div>
            </div>

            <div class="terms">
              <input type="checkbox" id="terms" name="terms"> <label for="terms">I agree to the <a>Terms &amp; Conditions</a></label>
            </div>

            <button type="submit" class="btn" name="createbtn" >Create Account</button>
          </form>
          <div class="form-footer">Already have an account? <a onclick="showLogin()">Sign In</a></div>
        </div>

      </div>
    </div>
  </div>

  <!-- Forgot Password Modal (hidden by default) -->
  <div class="modal-backdrop" id="forgotModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
      <h3 id="forgotTitle">Reset your password</h3>
      <p>Enter the email associated with your account and we'll send a reset link.</p>

      <div style="margin:12px 0 18px">
        <input id="forgot-email" type="email" placeholder="Your email address"
          style="width:100%; padding:12px 14px; border-radius:10px; border:1px solid #e6efe7; background:#fbfdfb">
      </div>

      <div style="display:flex;gap:10px">
        <button class="btn" style="flex:1" onclick="submitForgot(this)">Send Reset Link</button>
        <button onclick="closeForgot()"
          style="flex:0 0 100px; border-radius:10px; border:none; background:#f2f4f2; color:#3d4b42; cursor:pointer">Cancel</button>
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

      <div style="display:flex;gap:10px">
        <button class="btn" style="flex:1" id="otp-verify-btn" onclick="handleOtpSubmit()">Verify OTP</button>
        <button onclick="closeOtp()"
          style="flex:0 0 100px; border-radius:10px; border:none; background:#f2f4f2; color:#3d4b42; cursor:pointer">Cancel</button>
      </div>
      <div id="otp-error" class="error-message" style="display:none; margin-top:10px;"></div>
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
            style="width:100%; padding:12px 55px 12px 46px; border-radius:10px; border:1px solid #e6efe7; background:#fbfdfb; box-sizing: border-box; ">
          <span class="password-toggle" style="right: 8px;" onclick="togglePassword('reset-new-password', this)">
            <i class="fas fa-eye"></i>
          </span>
        </div>
      </div>
      <div class="input-group" style="margin-bottom: 30px; display: flex; justify-content: center;">
        <div class="box" style="width: 320px; max-width: 100%; position: relative;">
          <i class="fas fa-lock"></i>
          <input id="reset-cnf-password" type="password" placeholder="Confirm New Password"
            style="width:100%; padding:12px 55px 12px 46px; border-radius:10px; border:1px solid #e6efe7; background:#fbfdfb; box-sizing: border-box;">
          <span class="password-toggle" style="right: 8px;" onclick="togglePassword('reset-cnf-password', this)">
            <i class="fas fa-eye"></i>
          </span>
        </div>
      </div>

      <div style="display:flex;gap:10px">
        <button class="btn" style="flex:1" id="reset-btn" onclick="submitResetPassword(this)">Reset Password</button>
        <button onclick="document.getElementById('resetPasswordModal').style.display='none'"
          style="flex:0 0 100px; border-radius:10px; border:none; background:#f2f4f2; color:#3d4b42; cursor:pointer">Cancel</button>
      </div>
      <div id="reset-error" class="error-message" style="display:none; margin-top:10px; color:red;"></div>
    </div>
  </div>

  <!-- Popup -->
  <div id="popup" class="popup-overlay" style="display:none;">
    <div class="popup-box">
      <h2></h2>
      <p></p>
      <button onclick="closePopup()">OK</button>
    </div>
  </div>

  <script>
    // These functions should match the ones called in login-registration-validation.js
    function closePopup() {
        document.getElementById("popup").style.display = "none";
        document.getElementById("otpModal").style.display = "flex";
    }
  </script>


</body>

</html>


