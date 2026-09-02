<?php
// sidebar.php - Shared sidebar for tutor pages
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Toggle Button (Floating) -->
<button class="sidebar-toggle-mobile" id="mobileSidebarToggle" onclick="document.getElementById('sidebarToggle').click()">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar" id="tutorSidebar">
    <div class="sidebar-header">
        <h3>Tutor Panel</h3>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="sidebar-menu-container">
        <ul class="admin-menu">
            <li><a href="tutor_dashboard.php" <?php echo ($current_page == 'tutor_dashboard.php' || $current_page == 'tutor_page.php') ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'approved'): ?>
            <li><a href="tutor_courses.php" <?php echo ($current_page == 'tutor_courses.php') ? 'class="active"' : ''; ?>><i class="fas fa-chalkboard-teacher fa-fw"></i> My Courses</a></li>
            <li><a href="tutor_videos.php" <?php echo ($current_page == 'tutor_videos.php') ? 'class="active"' : ''; ?>><i class="fas fa-film fa-fw"></i> My Course Videos</a></li>
            <li><a href="tutor_tutorials.php" <?php echo ($current_page == 'tutor_tutorials.php') ? 'class="active"' : ''; ?>><i class="fas fa-chalkboard-teacher fa-fw"></i> My Tutorials</a></li>
            <li><a href="tutor_subscribers.php" <?php echo ($current_page == 'tutor_subscribers.php') ? 'class="active"' : ''; ?>><i class="fas fa-users fa-fw"></i> My Subscribers</a></li>
            <li><a href="tutor_plans.php" <?php echo ($current_page == 'tutor_plans.php') ? 'class="active"' : ''; ?>><i class="fas fa-gem fa-fw"></i> Subscription Plans</a></li>
            <?php endif; ?>
            <li><a href="tutor_profile.php" <?php echo ($current_page == 'tutor_profile.php') ? 'class="active"' : ''; ?>><i class="fas fa-user-circle fa-fw"></i> My Profile</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
        </ul>
    </div>
</aside>
