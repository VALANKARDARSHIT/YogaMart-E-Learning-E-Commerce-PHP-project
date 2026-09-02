<?php
// Include database connection and session check but don't require login for home page
include 'includes/connect.php';
require_once 'includes/session_check.php';
$isLoggedIn = checkSession(); // Optional check, no redirect
$user = getUserInfo(); // Get user info or guest info
$username = $user['username'];
$userRole = $user['role'];

// Fetch latest 6 tutorial videos
$featured_videos = [];
$videos_query = "SELECT id, title, description, url, thumbnail, duration, is_free FROM tutorial_videos ORDER BY created_at DESC LIMIT 6";
$videos_result = mysqli_query($con, $videos_query);
if ($videos_result) {
    while ($video = mysqli_fetch_assoc($videos_result)) {
        $featured_videos[] = $video;
    }
}

// Fetch free courses
$free_courses = [];
$free_courses_query = "SELECT id, title, description, level, thumbnail, created_at FROM courses WHERE is_free = 1 ORDER BY created_at DESC LIMIT 3";
$free_courses_result = mysqli_query($con, $free_courses_query);
if ($free_courses_result) {
    while ($course = mysqli_fetch_assoc($free_courses_result)) {
        $course_id = $course['id'];
        $video_count_query = "SELECT COUNT(*) as count FROM course_videos WHERE course_id = '$course_id'";
        $video_count_result = mysqli_query($con, $video_count_query);
        $course['video_count'] = $video_count_result ? mysqli_fetch_assoc($video_count_result)['count'] : 0;
        $free_courses[] = $course;
    }
}

// Fetch premium courses
$premium_courses = [];
$premium_courses_query = "SELECT id, title, description, level, thumbnail, created_at FROM courses WHERE is_free = 0 ORDER BY created_at DESC LIMIT 3";
$premium_courses_result = mysqli_query($con, $premium_courses_query);
if ($premium_courses_result) {
    while ($course = mysqli_fetch_assoc($premium_courses_result)) {
        $course_id = $course['id'];
        $video_count_query = "SELECT COUNT(*) as count FROM course_videos WHERE course_id = '$course_id'";
        $video_count_result = mysqli_query($con, $video_count_query);
        $course['video_count'] = $video_count_result ? mysqli_fetch_assoc($video_count_result)['count'] : 0;
        $premium_courses[] = $course;
    }
}

// Function to get level color
function getLevelColor($level) {
    switch(strtolower($level)) {
        case 'beginner': return '#28a745';
        case 'intermediate': return '#ffc107';  
        case 'advanced': return '#dc3545';
        default: return '#6c757d';
    }
}

// Function to get level emoji
function getLevelEmoji($level) {
    switch(strtolower($level)) {
        case 'beginner': return '🌱';
        case 'intermediate': return '🌿'; 
        case 'advanced': return '🌳';
        default: return '📚';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>YogaMart</title>
  <meta name="description" content="A bright, colorful yoga site: programs, teachers, and pricing. Pure HTML + CSS." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>" />
  <style>
    /* Login Required Popup Styles */
    .login-popup {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .login-popup-content {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      text-align: center;
      max-width: 400px;
      margin: 20px;
    }

    .login-popup h3 {
      color: #ff6b6b;
      margin-bottom: 1rem;
    }

    .login-popup p {
      color: #666;
      margin-bottom: 1.5rem;
    }

    .login-popup-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
    }

    .btn-login {
      background: #ff6b6b;
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 25px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: background 0.3s;
    }

    .btn-login:hover {
      background: #f8b400;
    }

    .btn-cancel {
      background: #f0f0f0;
      color: #666;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 25px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .btn-cancel:hover {
      background: #e0e0e0;
    }

    /* Disabled content styles */
    .content-disabled {
      opacity: 0.6;
      pointer-events: none;
      position: relative;
    }

    .content-disabled::after {
      content: "🔒 Login required to access this content";
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(255, 255, 255, 0.95);
      padding: 1rem 2rem;
      border-radius: 25px;
      border: 2px solid #ff6b6b;
      color: #ff6b6b;
      font-weight: 600;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      z-index: 10;
    }

    /* Program click cursor */
    .program {
      cursor: pointer;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .program:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    /* Welcome message for logged in users */
    .welcome-message {
      background: linear-gradient(135deg, #4a7c59, #6ba777);
      color: white;
      padding: 1rem 2rem;
      border-radius: 15px;
      margin-bottom: 2rem;
      text-align: center;
    }

    .welcome-message h3 {
      margin: 0 0 0.5rem 0;
    }

    .welcome-message p {
      margin: 0;
      opacity: 0.9;
    }

    /* Course Meta Styles */
    .course-meta {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.5rem;
    }
    
    .course-level-badge {
      display: inline-block;
      padding: 0.25rem 0.5rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 500;
      color: white;
    }
    
    .video-count {
      color: #666;
      font-size: 0.85rem;
      background: #f0f0f0;
      padding: 0.2rem 0.4rem;
      border-radius: 8px;
    }
    
    /* Horizontal Videos Scroll */
    .videos-horizontal-scroll {
      display: flex;
      gap: 1.5rem;
      overflow-x: auto;
      padding: 1rem 0;
      scroll-behavior: smooth;
      -webkit-overflow-scrolling: touch;
    }
    
    .videos-horizontal-scroll::-webkit-scrollbar {
      height: 8px;
    }
    
    .videos-horizontal-scroll::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    
        .videos-horizontal-scroll::-webkit-scrollbar-thumb {
          background: #ff6b6b;
          border-radius: 10px;
        }
    
        .videos-horizontal-scroll::-webkit-scrollbar-thumb:hover {
          background: #f8b400;
        }    
    /* Video Protection Styles */
    .video-container {
      position: relative;
      background: #f8f9fa;
      border-radius: 15px;
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.3s, box-shadow 0.3s;
      min-width: 280px;
      flex-shrink: 0;
    }

    .video-container:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .video-thumbnail {
      width: 100%;
      height: 200px;
      background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: #666;
      position: relative;
    }

    .play-button {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 60px;
      height: 60px;
      background: rgba(255, 107, 107, 0.9);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      transition: all 0.3s;
    }

    .play-button:hover {
      background: rgba(255, 107, 107, 1);
      transform: translate(-50%, -50%) scale(1.1);
    }

    .video-info {
      padding: 1rem;
    }

    .video-info h4 {
      margin: 0 0 0.5rem 0;
      color: #ff6b6b;
    }

    .video-info p {
      margin: 0;
      color: #666;
      font-size: 14px;
    }

    .video-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 0.5rem;
      font-size: 12px;
      color: #999;
    }

    .video-duration {
      background: rgba(255, 107, 107, 0.1);
      padding: 2px 8px;
      border-radius: 12px;
      color: #ff6b6b;
    }

    /* Video Grid */
    .videos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }

    /* Course/Program Image Styling */
    .course-image {
      width: 100%;
      height: 180px;
      background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
      border-radius: 12px;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      position: relative;
      overflow: hidden;
    }

    .course-image::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(255, 107, 107, 0.1), rgba(248, 180, 0, 0.1));
      z-index: 1;
    }

    .course-image .emoji {
      position: relative;
      z-index: 2;
    }

    .program:hover .course-image {
      transform: scale(1.05);
      transition: transform 0.3s ease;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <?php include 'includes/header.php';?>

    <!-- Welcome Message for Logged In Users -->
    <?php if ($isLoggedIn): ?>
    <div class="container" style="margin-top: 2rem;">
      <div class="welcome-message">
        <h3>Welcome back, <?php echo htmlspecialchars($username); ?>! 🧘‍♀️</h3>
        <p>Ready to continue your yoga journey? Browse our courses below.</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <section id="home" class="hero">
      <div class="container hero__inner">
        <div class="hero__text">
          <h1>YogaMart for your wellness journey ☀️</h1>
          <p>Quick morning energizers, cozy stretch breaks, and calming breath—designed to make you smile.</p>
          <div class="cta-row">
            <a class="btn btn--primary" href="about_us.php">Start 7-day free trial</a>
            <a class="btn btn--ghost" href="courses.php">Browse Courses</a>
          </div>
          <div class="badges">
            <span class="badge">Beginner-friendly</span>
            <span class="badge">No props needed</span>
            <span class="badge">10-40 min</span>
          </div>
        </div>
        <div class="hero__art" aria-hidden="true">
          <div class="blob b1"></div>
          <div class="blob b2"></div>
          <div class="blob b3"></div>
        </div>
      </div>
    </section>

    <!-- Featured Videos -->
    <section class="section" id="featured-videos">
      <div class="container">
        <div class="section__head">
          <h2>Featured Yoga Videos 🎥</h2>
          <p class="muted">Watch our most popular yoga sessions and tutorials</p>
        </div>
        <div class="videos-horizontal-scroll">
          <?php if (empty($featured_videos)): ?>
            <div style="padding: 2rem; text-align: center; color: #666; width: 100%;">
              No featured videos yet.
            </div>
          <?php else: ?>
            <?php foreach ($featured_videos as $video): ?>
              <div class="video-container" onclick="handleVideoClick('<?php echo addslashes($video['title']); ?>', '<?php echo addslashes($video['url']); ?>')">
                <div class="video-thumbnail">
                  <?php if ($video['thumbnail'] && file_exists($video['thumbnail'])): ?>
                    <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    🧘
                  <?php endif; ?>
                  <div class="play-button">▶</div>
                </div>
                <div class="video-info">
                  <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                  <p><?php echo htmlspecialchars(substr($video['description'], 0, 60)) . (strlen($video['description']) > 60 ? '...' : ''); ?></p>
                  <div class="video-meta">
                    <span><?php echo $video['is_free'] ? 'Free' : 'Premium'; ?></span>
                    <span class="video-duration"><?php echo htmlspecialchars($video['duration']); ?></span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Free Courses -->
    <section class="section section--alt" id="free-courses">
      <div class="container">
        <div class="section__head">
          <h2>Free Courses <span><a href="courses.php"><img src="assets/images/plus.png" width="25px" height="25px" alt="View All"></a></span></h2>
          <p class="muted">Try these beginner-friendly yoga courses at no cost</p>
        </div>
        <?php if (empty($free_courses)): ?>
          <div style="padding: 2rem; text-align: center; color: #666;">
            No free courses available yet.
          </div>
        <?php else: ?>
          <div class="grid grid--programs">
            <?php foreach ($free_courses as $course): ?>
              <div class="program" onclick="handleDatabaseCourseClick(<?php echo $course['id']; ?>, '<?php echo addslashes($course['title']); ?>')">
                <div class="course-image">
                  <?php if ($course['thumbnail'] && file_exists($course['thumbnail'])): ?>
                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                  <?php else: ?>
                    <span class="emoji"><?php echo getLevelEmoji($course['level']); ?></span>
                  <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                <p><?php echo htmlspecialchars(substr($course['description'], 0, 80)) . (strlen($course['description']) > 80 ? '...' : ''); ?></p>
                <?php if ($isLoggedIn): ?>
                <div class="program-status">
                  <small style="color: #ff6b6b; font-weight: 600;">✓ Available</small>
                </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Premium Courses -->
    <section class="section" id="premium-courses">
      <div class="container">
        <div class="section__head">
          <h2>Premium Courses <span><a href="courses.php"><img src="assets/images/plus.png" width="25px" height="25px" alt="View All"></a></span></h2>
          <p class="muted">Transform your body and mind with our premium courses</p>
        </div>
        <?php if (empty($premium_courses)): ?>
          <div style="padding: 2rem; text-align: center; color: #666;">
            No premium courses available yet.
          </div>
        <?php else: ?>
          <div class="grid grid--programs">
            <?php foreach ($premium_courses as $course): ?>
              <div class="program" onclick="handleDatabaseCourseClick(<?php echo $course['id']; ?>, '<?php echo addslashes($course['title']); ?>')">
                <div class="course-image">
                  <?php if ($course['thumbnail'] && file_exists($course['thumbnail'])): ?>
                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                  <?php else: ?>
                    <span class="emoji">💎</span>
                  <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                <p><?php echo htmlspecialchars(substr($course['description'], 0, 80)) . (strlen($course['description']) > 80 ? '...' : ''); ?></p>
                <?php if ($isLoggedIn): ?>
                <div class="program-status">
                  <small style="color: #e67e22; font-weight: 600;">💰 Premium</small>
                </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

  <!-- Footer -->
  <?php include 'includes/footer.php';?>

</body>
</html>
