<?php
// session_check.php - Enhanced session management and authentication

// Configure session settings - only if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        ini_set('session.gc_maxlifetime', 7200); // 2 hours server-side cleanup
        ini_set('session.cookie_lifetime', 0); // Browser session cookie
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS only
        ini_set('session.use_strict_mode', 1);
    }
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    if (!session_start()) {
        error_log("Failed to start session in session_check.php");
    }
}

// Set session timeout (30 minutes)
if (!defined('SESSION_TIMEOUT')) { 
    define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
}

// Check if user is logged in with session validation
function isLoggedIn() {
    // Ensure session is started
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Check if required session variables exist
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || empty($_SESSION['user_id'])) {
        error_log("isLoggedIn: Missing required session variables");
        return false;
    }
    
    // Check explicit authentication flag
    if (!isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
        error_log("isLoggedIn: Not authenticated flag missing or false");
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            // Session expired, destroy it
            error_log("isLoggedIn: Session expired - timeout exceeded");
            destroyUserSession();
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    error_log("isLoggedIn: User is authenticated - user_id: " . $_SESSION['user_id']);
    return true;
}

// Initialize or update user session
function initUserSession($user_data) {
    // Ensure session is active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Clear any existing session data first
    session_unset();
    
    // Set basic session data
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['email'] = $user_data['email'] ?? '';
    $_SESSION['role'] = $user_data['role'] ?? 'learner';
    $_SESSION['status'] = $user_data['status'] ?? 'approved'; // Default to approved for legacy/learners
    $_SESSION['auth_source'] = $user_data['auth_source'] ?? 'users_tbl';
    $_SESSION['profile_picture'] = $user_data['profile_picture'] ?? null;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['session_token'] = bin2hex(random_bytes(32)); // Security token
    $_SESSION['is_authenticated'] = true; // Explicit authentication flag
    
    // Debug log before write
    error_log("initUserSession - Setting session data: " . print_r($_SESSION, true));
    
    // Force session write and close to ensure persistence
    session_write_close();
    
    // Restart session to make it available immediately
    session_start();
    
    // Debug log after restart
    error_log("initUserSession completed. Final session data: " . print_r($_SESSION, true));
    
    // Verify session was properly set
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        error_log("ERROR: Session was not properly initialized!");
        return false;
    }
    
    return true;
}

// Destroy user session completely
function destroyUserSession() {
    // Ensure session is started
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Clear authentication flag explicitly
    $_SESSION['is_authenticated'] = false;
    
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start a new clean session
    session_start();
    
    error_log("destroyUserSession: Session destroyed and new clean session started");
}

// Get current user information with enhanced validation
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'learner',
        'status' => $_SESSION['status'] ?? 'approved',
        'auth_source' => $_SESSION['auth_source'] ?? 'users_tbl',
        'login_time' => $_SESSION['login_time'] ?? time(),
        'last_activity' => $_SESSION['last_activity'] ?? time(),
        'session_token' => $_SESSION['session_token'] ?? '',
        'profile_pic' => $_SESSION['profile_picture'] ?? 'https://img.icons8.com/color/48/user-male-circle--v1.png'
    ];
}

// Require login - redirect to login page if not authenticated
function requireLogin($redirect_to_login = true) {
    if (!isLoggedIn()) {
        if ($redirect_to_login) {
            $project_root = realpath(dirname(__DIR__));
            $current_dir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
            $base_dir = ($project_root === $current_dir) ? '' : '../';
            header("Location: " . $base_dir . "login-registration.php");
            exit();
        }
        return false;
    }
    return true;
}

// Check if user has specific role
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? 'learner';
    
    // Admin has access to everything
    if ($user_role === 'admin') {
        return true;
    }
    
    return $user_role === $required_role;
}

// Require specific role - redirect if not authorized
function requireRole($required_role, $redirect_to_login = true) {
    if (!hasRole($required_role)) {
        if ($redirect_to_login) {
            $project_root = realpath(dirname(__DIR__));
            $current_dir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
            $base_dir = ($project_root === $current_dir) ? '' : '../';
            header("Location: " . $base_dir . "login-registration.php");
            exit();
        }
        return false;
    }
    return true;
}

// Get user's display name
function getUserDisplayName() {
    $user = getCurrentUser();
    if (!$user) return 'Guest';
    return $user['username'] ?: 'User';
}

// Check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Set success message
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// Get and clear message
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = [
            'text' => $_SESSION['message'],
            'type' => $_SESSION['message_type'] ?? 'info'
        ];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return $message;
    }
    return null;
}

// Optional session check - doesn't redirect, just checks status
function checkSession() {
    // Just ensure session is started, don't require login
    if (!isset($_SESSION)) {
        session_start();
    }
    return isLoggedIn();
}

// Get user info without requiring login
function getUserInfo() {
    if (!isLoggedIn()) {
        return [
            'id' => null,
            'username' => 'Guest',
            'email' => '',
            'role' => 'guest',
            'login_time' => null,
            'last_activity' => null,
            'session_token' => '',
            'profile_pic' => 'https://img.icons8.com/color/48/user-male-circle--v1.png'
        ];
    }
    return getCurrentUser();
}

// Check session health and security
function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check for session hijacking attempts
    if (!isset($_SESSION['session_token']) || empty($_SESSION['session_token'])) {
        destroyUserSession();
        return false;
    }
    
    // Check if session is too old (beyond maximum lifetime)
    if (isset($_SESSION['login_time'])) {
        if ((time() - $_SESSION['login_time']) > (SESSION_TIMEOUT * 8)) { // 4 hours max
            destroyUserSession();
            return false;
        }
    }
    
    return true;
}

// Get session status information
function getSessionStatus() {
    if (!isLoggedIn()) {
        return [
            'logged_in' => false,
            'time_remaining' => 0,
            'expires_at' => null
        ];
    }
    
    $last_activity = $_SESSION['last_activity'] ?? time();
    $time_remaining = SESSION_TIMEOUT - (time() - $last_activity);
    
    return [
        'logged_in' => true,
        'time_remaining' => max(0, $time_remaining),
        'expires_at' => $last_activity + SESSION_TIMEOUT,
        'session_age' => time() - ($_SESSION['login_time'] ?? time())
    ];
}

// Regenerate session ID for security
function regenerateSessionId() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
    }
}

// Require approved tutor - redirect if not approved
function requireApprovedTutor() {
    if (!requireRole('tutor')) return false;
    
    if (isset($_SESSION['status']) && $_SESSION['status'] !== 'approved') {
        header("Location: tutor_dashboard.php");
        exit();
    }
    return true;
}

/**
 * Check if a user is a premium member of a specific tutor.
 * 
 * @param mysqli $con Database connection
 * @param int $user_id User ID
 * @param int $tutor_id Tutor ID
 * @return bool True if the user is a premium member and the membership hasn't expired
 */
function isPremiumMember($con, $user_id, $tutor_id) {
    if (!$user_id || !$tutor_id) return false;
    
    // Admins are treated as premium members for everyone
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    
    $query = "SELECT end_date FROM premium_members WHERE user_id = ? AND tutor_id = ? ORDER BY end_date DESC LIMIT 1";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $user_id, $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $end_date = strtotime($row['end_date']);
        if ($end_date > time()) {
            return true;
        }
    }
    
    return false;
}
?>
