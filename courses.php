<?php

// Include database connection and session check
include 'includes/connect.php';
require_once 'includes/init.php';

$isLoggedIn = checkSession();
$user = getUserInfo();

// Check if user is trying to access protected content
if (!$isLoggedIn && isset($_GET['access'])) {
    header("Location: login-registration.php?redirect=courses.php&msg=Please+login+to+access+courses");
    exit();
}

// Fetch courses from database
$courses = [];
$courses_query = "SELECT id, tutor_id, title, description, level, thumbnail, is_free, created_at FROM courses ORDER BY created_at DESC";
$courses_result = mysqli_query($con, $courses_query);

if ($courses_result) {
    while ($course = mysqli_fetch_assoc($courses_result)) {
        // Check if course is locked for current user
        $is_locked = false;
        if (!$course['is_free']) {
            if (!$isLoggedIn) {
                $is_locked = true;
            } else {
                // Check if user is premium member of this tutor
                $is_locked = !isPremiumMember($con, $user['id'], $course['tutor_id']);
            }
        }
        $course['is_locked'] = $is_locked;

        // Fetch videos for each course
        $course_id = $course['id'];
        $videos_query = "SELECT id, title, description, video_url, duration FROM course_videos WHERE course_id = '$course_id' ORDER BY created_at ASC";
        $videos_result = mysqli_query($con, $videos_query);
        
        $course['videos'] = [];
        if ($videos_result) {
            while ($video = mysqli_fetch_assoc($videos_result)) {
                $course['videos'][] = $video;
            }
        }
        
        $courses[] = $course;
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
        case 'beginner': return '&#127793;'; // 🌱
        case 'intermediate': return '&#9889;'; // ⚡
        case 'advanced': return '&#128293;'; // 🔥
        default: return '&#128522;'; // 🙂
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Courses - YogaMart</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>" />
  <style>
    .courses-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .course-card {
      background: white;
      border-radius: 20px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 1px solid #f0f0f0;
    }
    
    .course-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    }
    
    .course-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      cursor: pointer;
    }
    
    .course-thumbnail {
      width: 80px;
      height: 80px;
      border-radius: 15px;
      object-fit: cover;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: white;
    }
    
    .course-info {
      flex: 1;
    }
    
    .course-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #2d5a3d;
      margin-bottom: 0.5rem;
    }
    
    .course-meta {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 0.5rem;
    }
    
    .course-level {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      color: white;
      font-size: 0.85rem;
      font-weight: 500;
    }
    
    .course-description {
      color: #666;
      line-height: 1.5;
    }
    
    .videos-section {
      display: none;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 2px solid #f0f0f0;
    }
    
    .videos-section.active {
      display: block;
    }
    
    .videos-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
    }
    
    .videos-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #2d5a3d;
    }
    
    .video-count {
      background: #e8f6f5;
      color: #2d5a3d;
      padding: 0.25rem 0.5rem;
      border-radius: 10px;
      font-size: 0.85rem;
    }
    
    .video-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1rem;
    }
    
    .video-card {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }
    
    .video-card:hover {
      background: #e8f6f5;
      border-color: #4a7c59;
      transform: translateY(-2px);
    }
    
    .video-title {
      font-weight: 600;
      color: #2d5a3d;
      margin-bottom: 0.5rem;
    }
    
    .video-description {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
      line-height: 1.4;
    }
    
    .video-duration {
      color: #4a7c59;
      font-size: 0.85rem;
      font-weight: 500;
    }
    
    .video-player {
      display: none;
      margin-bottom: 1rem;
      background: #000;
      border-radius: 12px;
      overflow: hidden;
    }
    
    .video-player.active {
      display: block;
    }
    
    .video-player video {
      width: 100%;
      height: 400px;
      object-fit: cover;
    }
    
    .expand-icon {
      transition: transform 0.3s ease;
      font-size: 1.2rem;
      color: #4a7c59;
    }
    
    .course-card.expanded .expand-icon {
      transform: rotate(180deg);
    }
    
    .no-courses {
      text-align: center;
      padding: 3rem;
      color: #666;
    }
    
    .empty-course {
      text-align: center;
      color: #999;
      font-style: italic;
      padding: 1rem;
    }
    
    /* Login Required Overlay */
    .login-required-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    
    .login-prompt {
      background: white;
      padding: 2rem;
      border-radius: 15px;
      text-align: center;
      max-width: 400px;
      margin: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .login-prompt h3 {
      color: #2d5a3d;
      margin-bottom: 1rem;
    }
    
    .login-prompt p {
      color: #666;
      margin-bottom: 1.5rem;
    }
    
    .btn-login, .btn-home {
      padding: 0.75rem 1.5rem;
      border-radius: 25px;
      text-decoration: none;
      display: inline-block;
      margin: 0.5rem;
      transition: all 0.3s;
    }
    
    .btn-login {
      background: #4a7c59;
      color: white;
    }
    
    .btn-login:hover {
      background: #3d6b4a;
    }
    
    .btn-home {
      background: #f0f0f0;
      color: #666;
    }
    
    .btn-home:hover {
      background: #e0e0e0;
    }
    .video-card.locked {
      cursor: not-allowed;
      opacity: 0.7;
      position: relative;
    }
    
    .video-card.locked::after {
      content: '🔒 Premium';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(0, 0, 0, 0.7);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.8rem;
    }

    .locked-notice {
      background: #fff3cd;
      color: #856404;
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1px solid #ffeeba;
    }

    .btn-upgrade {
      background: #ffc107;
      color: #212529;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9rem;
      margin-left: auto;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <?php include 'includes/header.php'; ?>
  
  <?php if (!$isLoggedIn): ?>
  <!-- Login Required Overlay -->
  <div class="login-required-overlay">
    <div class="login-prompt">
      <h3>🔒 Login Required</h3>
      <p>Please log in to access our premium courses and start your yoga journey!</p>
      <a href="login-registration.php" class="btn-login">Login / Register</a>
      <a href="home.php" class="btn-home">Back to Home</a>
    </div>
  </div>
  <?php endif; ?>

  <section id="courses" class="section section--alt">
    <div class="container">
      <header class="section__head">
        <h2>Our Yoga Courses</h2>
        <p class="muted">Transform your wellness journey with our expertly designed courses.</p>
      </header>
      
      <div class="courses-container">
        <?php if (empty($courses)): ?>
          <div class="no-courses">
            <h3>📚 No Courses Available Yet</h3>
            <p>We're working on adding amazing yoga courses for you. Please check back soon!</p>
          </div>
        <?php else: ?>
          <?php foreach ($courses as $index => $course): ?>
            <div class="course-card" id="course-<?php echo $course['id']; ?>">
              <div class="course-header" onclick="toggleCourse(<?php echo $course['id']; ?>)">
                <div class="course-thumbnail">
                  <?php if ($course['thumbnail'] && file_exists($course['thumbnail'])): ?>
                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 15px;">
                  <?php else: ?>
                    <?php echo getLevelEmoji($course['level']); ?>
                  <?php endif; ?>
                </div>
                
                <div class="course-info">
                  <h3 class="course-title">
                    <?php echo htmlspecialchars($course['title']); ?>
                    <?php if ($course['is_locked']): ?>
                      <span style="font-size: 0.9rem; vertical-align: middle; color: #ffc107;">🔒</span>
                    <?php endif; ?>
                  </h3>
                  <div class="course-meta">
                    <span class="course-level" style="background-color: <?php echo getLevelColor($course['level']); ?>">
                      <?php echo ucfirst($course['level']); ?>
                    </span>
                    <span class="video-count"><?php echo count($course['videos']); ?> videos</span>
                    <span class="course-price" style="font-weight: bold; color: #2d5a3d; margin-left: auto;">
                      <?php echo $course['is_free'] ? 'Free' : 'Premium'; ?>
                    </span>
                  </div>
                  <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
                </div>
                <div class="expand-icon">&#9662;</div> <!-- ▾ -->
              </div>
              
              <div class="videos-section" id="videos-<?php echo $course['id']; ?>">
                <?php if ($course['is_locked']): ?>
                  <div class="locked-notice">
                    <i class="fas fa-lock"></i>
                    <span>This course is exclusive to premium members of this tutor.</span>
                    <a href="tutor_page.php?id=<?php echo $course['tutor_id']; ?>" class="btn-upgrade">View Subscription Plans</a>
                  </div>
                <?php else: ?>
                  <div class="video-player" id="player-<?php echo $course['id']; ?>">
                    <video controls id="video-element-<?php echo $course['id']; ?>">
                      Your browser does not support the video tag.
                    </video>
                  </div>
                <?php endif; ?>
                
                <div class="videos-header">
                  <h4 class="videos-title">Course Videos</h4>
                  <span class="video-count"><?php echo count($course['videos']); ?> videos</span>
                </div>
                
                <?php if (empty($course['videos'])): ?>
                  <div class="empty-course">
                    <p>📹 No videos uploaded yet for this course.</p>
                  </div>
                <?php else: ?>
                  <div class="video-grid">
                    <?php foreach ($course['videos'] as $video): ?>
                      <div class="video-card <?php echo $course['is_locked'] ? 'locked' : ''; ?>" 
                           onclick="<?php echo $course['is_locked'] ? '' : "playVideo(" . $course['id'] . ", '" . addslashes($video['video_url']) . "', '" . addslashes($video['title']) . "')"; ?>">
                        <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                        <div class="video-description"><?php echo htmlspecialchars($video['description']); ?></div>
                        <div class="video-duration">⏱️ <?php echo htmlspecialchars($video['duration']); ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <script>
    function toggleCourse(courseId) {
      const courseCard = document.getElementById(`course-${courseId}`);
      const videosSection = document.getElementById(`videos-${courseId}`);
      
      // Toggle the expanded class
      courseCard.classList.toggle('expanded');
      
      // Toggle the videos section
      if (videosSection.classList.contains('active')) {
        videosSection.classList.remove('active');
      } else {
        // Close all other courses first
        document.querySelectorAll('.videos-section').forEach(section => {
          section.classList.remove('active');
        });
        document.querySelectorAll('.course-card').forEach(card => {
          card.classList.remove('expanded');
        });
        
        // Open this course
        videosSection.classList.add('active');
        courseCard.classList.add('expanded');
        
        // Scroll to the course
        setTimeout(() => {
          courseCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
      }
    }
    
    function playVideo(courseId, videoUrl, videoTitle) {
      const player = document.getElementById(`player-${courseId}`);
      const videoElement = document.getElementById(`video-element-${courseId}`);
      
      // Show the player
      player.classList.add('active');
      
      // Set the video source
      videoElement.src = videoUrl;
      videoElement.load();
      
      // Scroll to player
      setTimeout(() => {
        player.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 100);
      
      console.log(`Playing: ${videoTitle} from ${videoUrl}`);
    }
    
    // Auto-expand course based on URL parameter
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const courseId = urlParams.get('course');
      
      if (courseId) {
        toggleCourse(parseInt(courseId));
      }
    });
  </script>
</body>
</html>

