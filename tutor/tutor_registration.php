<?php
    include '../includes/init.php';
    include '../includes/otp_functions.php';
    include '../includes/email_sender.php';
    include '../includes/connect.php';

    // AJAX Handling Logic
    if ($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        $response = [];

        // Check if this is an OTP verification attempt
        if (isset($_POST['otp']) && !empty($_POST['otp'])) {
            $user_otp = trim($_POST['otp']);
            
            if (!isset($_SESSION['reg_data'])) {
                echo json_encode(['success' => false, 'error' => 'Registration session lost. Please fill the form again.']);
                exit();
            }

            $otp_check_result = otp_checker($user_otp);
            if ($otp_check_result['isValid']) {
                $username = $_SESSION['reg_data']['username'];
                $email = $_SESSION['reg_data']['email'];
                $password = $_SESSION['reg_data']['password_hash'];
                $bio = $_SESSION['reg_data']['bio'];
                $document_proof = $_SESSION['reg_data']['document_proof'] ?? '';

                // Check for duplicate username
                $check_username = $con->prepare("SELECT id FROM tutors WHERE username = ?");
                $check_username->bind_param('s', $username);
                $check_username->execute();
                if ($check_username->get_result()->num_rows > 0) {
                    $response = ['success' => false, 'error' => 'Username already taken. Please restart registration with a different name.'];
                } else {
                    $query = "INSERT INTO tutors (username, email, password_hash, bio, document_proof, status) VALUES(?,?,?,?,?, 'new')";
                    $stmt = $con->prepare($query);

                    if (!$stmt) {
                        $response = ['success' => false, 'error' => 'Database error. Please try again.'];
                    } else {
                        $stmt->bind_param('sssss', $username, $email, $password, $bio, $document_proof);
                        if ($stmt->execute()) {
                            unset($_SESSION['reg_data']);
                            $redirect_msg = $document_proof ? 'Registration Successful. Admin will review your documents.' : 'Registration Successful. Please login and upload your certification proof.';
                            $response = ['success' => true, 'redirect' => 'tutor_login.php?msg=' . urlencode($redirect_msg)];
                        } else {
                            $response = ['success' => false, 'error' => 'Registration failed: ' . $stmt->error];
                        }
                        $stmt->close();
                    }
                }
                $check_username->close();
            } else {
                $response = ['success' => false, 'error' => $otp_check_result['error'] ?? 'Invalid OTP. Please try again.'];
            }
        } 
        // Initial form submission
        else {
            $username = trim($_POST['new_name'] ?? '');
            $email = trim($_POST['new_email'] ?? '');
            $rawPassword = trim($_POST['new_password'] ?? '');
            $cnfpassword = trim($_POST['new_cnfpassword'] ?? '');
            $bio = trim($_POST['new_bio'] ?? '');
            $terms = $_POST['terms'] ?? '';

            if (empty($username) || empty($email) || empty($rawPassword) || empty($cnfpassword)) {
                $response = ['success' => false, 'error' => 'All fields are required.'];
            } else if ($rawPassword !== $cnfpassword) {
                $response = ['success' => false, 'error' => 'Passwords do not match.'];
            } else if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $rawPassword)) {
                $response = ['success' => false, 'error' => 'Password must be at least 8 chars, include upper, lower, number & special char.'];
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = ['success' => false, 'error' => 'Invalid email format.'];
            } else if ($terms !== 'on') {
                $response = ['success' => false, 'error' => 'You must agree to the Terms & Conditions.'];
            } else {
                // Check if email exists
                $check_query = "SELECT email FROM tutors WHERE email=?";
                $check_stmt = $con->prepare($check_query);
                $check_stmt->bind_param('s', $email);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $response = ['success' => false, 'error' => 'Email already registered.'];
                } else {
                    // Handle Optional Document Upload
                    $document_proof_name = '';
                    if (isset($_FILES['document_proof']) && $_FILES['document_proof']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['document_proof'];
                        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
                        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                        if (in_array($file_ext, $allowed_ext)) {
                            $upload_dir = '../content/tutor_documents/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            $document_proof_name = time() . '_' . uniqid() . '.' . $file_ext;
                            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $document_proof_name)) {
                                $document_proof_name = ''; // Reset if upload fails
                            }
                        }
                    }

                    // Send OTP
                    list($otp, $hash) = otp_Generator();
                    if (Email_sender($otp, $email)) {
                        $_SESSION['reg_data'] = [
                            'username' => $username,
                            'email' => $email,
                            'password_hash' => password_hash($rawPassword, PASSWORD_DEFAULT),
                            'bio' => $bio,
                            'document_proof' => $document_proof_name
                        ];
                        $response = ['success' => true, 'message' => 'OTP sent successfully.'];
                    } else {
                        $response = ['success' => false, 'error' => 'Failed to send OTP. Please check your email configuration.'];
                    }
                }
                $check_stmt->close();
            }
        }
        echo json_encode($response);
        exit();
    }
    
    $errorScripts = ''; // Initialize errorScripts to avoid undefined variable error
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>YogaMart| Tutor Registration</title>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Open+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">

    <script src="../assets/js/login-registration-validation.js"></script>
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="../assets/css/login-registration.css?v=<?php echo time(); ?>" />
    <?php include '../includes/toast_notification.php'; ?>
    <style>
        /* Override styles for single-form page */
        .register-container {
            position: relative !important;
            opacity: 1 !important;
            transform: none !important;
            pointer-events: all !important;
            top: 0 !important;
        }
        #register-bio {
            width: 100%;
            padding: 9px 16px 9px 46px;
            border-radius: 20px;
            border: 1px solid #e6efe7;
            background: var(--input-bg);
            font-size: 15px;
            color: #26322a;
            font-family: 'Open Sans', sans-serif;
            resize: vertical;
            height: 100px;
        }
        #register-bio:focus {
            border-color: #FFD69B;
            box-shadow: 0 6px 20px rgba(255, 166, 80, 0.08);
            background: #fff;
            color:#1a211dd9;
            outline: none;
        }
    </style>
    <script>
        // Attach event listeners to forms to prevent default submission and call validation functions
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.querySelector('#registerContainer form');
            if (registerForm) {
                registerForm.addEventListener('submit', function(event) {
                    if (!submitRegister(event)) {
                        event.preventDefault();
                    }
                });
            }
        });
    </script>
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

                <!-- Registration Form -->
                <div class="form register-container" id="registerContainer" style="display:block;">
                    <h2 class="form-title">Create Your Tutor Account</h2>
                    <p class="subheading">Join our community of instructors</p>
                    <form onsubmit="return submitRegister(event)" method="post">

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
                                <input type="password" id="register-password" name="new_password"
                                    placeholder="Password">
                                <span class="password-toggle" onclick="togglePassword('register-password', this)"><i
                                        class="fas fa-eye"></i></span>
                            </div>
                        </div>

                        <div class="input-group">
                            <div class="box">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm-password" name="new_cnfpassword"
                                    placeholder="Confirm Password">
                                <span class="password-toggle" onclick="togglePassword('confirm-password', this)"><i
                                        class="fas fa-eye"></i></span>
                            </div>
                        </div>

                        <div class="input-group">
                            <div class="box">
                                <i class="fas fa-info-circle"></i>
                                <textarea id="register-bio" name="new_bio" placeholder="Tell us about yourself"></textarea>
                            </div>
                            <div id="bio-char-count" style="text-align: right; font-size: 12px; color: #888;">0/500</div>
                        </div>

                        <div class="input-group">
                            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px; margin-left: 10px;">Certification / Identity Proof (Optional)</label>
                            <div class="box">
                                <i class="fas fa-file-upload"></i>
                                <input type="file" id="register-document" name="document_proof" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <small style="display: block; font-size: 11px; color: #888; margin-top: 5px; margin-left: 10px;">You can also upload this later from your dashboard.</small>
                        </div>

                        <div class="terms">
                            <input type="checkbox" id="terms" name="terms"> <label for="terms">I agree to the <a>Terms
                                    &amp; Conditions</a></label>
                        </div>

                        <input type="hidden" name="otp" id="hidden-otp">

                        <button type="submit" class="btn" name="createbtn">Create Account</button>
                    </form>
                    <div class="form-footer">Already have an account? <a href="tutor_login.php">Sign In</a></div>
                    <?php if(isset($message)){
                        echo '<div class="error-message error">'.$message.'</div>';
                    } ?>
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

            <div style="display:flex;gap:10px">
                <button class="btn" style="flex:1" name="otpbtn" id="otp-verify-btn" onclick="submitOtp()">Verify
                    OTP</button>
                <button onclick="closeOtp()"
                    style="flex:0 0 100px; border-radius:10px; border:none; background:#f2f4f2; color:#3d4b42; cursor:pointer">Cancel</button>
            </div>
            <div id="otp-error" class="error-message" style="display:none; margin-top:10px;"></div>
        </div>
    </div>

    <!-- Popup -->
    <div id="popup" class="popup-overlay">
        <div class="popup-box">
            <h2></h2>
            <p></p>
            <button onclick="closePopup()">OK</button>
        </div>
    </div>

    <!-- Global Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-text" id="loadingText">Processing...</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.querySelector('#registerContainer form');
            const registerError = document.createElement('div');
            registerError.className = 'error-message';
            registerError.style.display = 'none';
            registerForm.prepend(registerError);

            registerForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Client-side validation
                const name = document.getElementById('register-name').value.trim();
                const email = document.getElementById('register-email').value.trim();
                const password = document.getElementById('register-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                const termsChecked = document.getElementById('terms').checked;

                if (!name || !email || !password || !confirmPassword) {
                    showToast('All fields are required.', 'error');
                    return;
                }
                if (password !== confirmPassword) {
                    showToast('Passwords do not match.', 'error');
                    return;
                }
                if (!termsChecked) {
                    showToast('You must agree to the Terms & Conditions.', 'error');
                    return;
                }

                const submitButton = registerForm.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                showLoading('Sending OTP to your email...');

                const formData = new FormData(registerForm);

                fetch('tutor_registration.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showPopup("OTP Sent", "An OTP has been sent to " + email + ". Please check your inbox.");
                    } else {
                        showToast(data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Registration Error:', error);
                    showToast('An unexpected error occurred.', 'error');
                })
                .finally(() => {
                    submitButton.disabled = false;
                    hideLoading();
                });
            });
        });

        function submitOtp() {
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
            showLoading('Verifying OTP and creating account...');
            otpError.style.display = 'none';

            const formData = new FormData();
            formData.append('otp', otpValue);

            fetch('tutor_registration.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    otpError.textContent = data.error;
                    otpError.style.display = 'block';
                    hideLoading();
                }
            })
            .catch(error => {
                otpError.textContent = 'An unexpected error occurred during OTP verification.';
                otpError.style.display = 'block';
                console.error('OTP Error:', error);
                hideLoading();
            })
            .finally(() => {
                verifyButton.disabled = false;
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

        function showLoading(text = 'Processing...') {
            const overlay = document.getElementById('loadingOverlay');
            const textEl = document.getElementById('loadingText');
            if (overlay && textEl) {
                textEl.textContent = text;
                overlay.classList.add('show');
            }
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('show');
            }
        }

        function showPopup(msg, content) {
            const popup = document.getElementById("popup");
            popup.querySelector("h2").textContent = msg;
            popup.querySelector("p").textContent = content;
            popup.style.display = "flex";
        }

        function closePopup() {
            document.getElementById("popup").style.display = "none";
            openOtp();
        }

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

        const bioTextarea = document.getElementById('register-bio');
        const bioCharCount = document.getElementById('bio-char-count');
        const maxChars = 500;

        if (bioTextarea) {
            bioTextarea.addEventListener('input', () => {
                const currentChars = bioTextarea.value.length;
                bioCharCount.textContent = `${currentChars}/${maxChars}`;
                if (currentChars > maxChars) {
                    bioCharCount.style.color = 'red';
                } else {
                    bioCharCount.style.color = '#888';
                }
            });
        }
    </script>
    <?php echo $errorScripts; ?>

</body>

</html>

