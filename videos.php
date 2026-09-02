<?php

// Include database connection and session check
include 'includes/connect.php';
require_once 'includes/init.php';

$isLoggedIn = checkSession();
$user = getUserInfo();

// Check if user is trying to access protected content
if (!$isLoggedIn && isset($_GET['access'])) {
    header("Location: login-registration.php?redirect=videos.php&msg=Please+login+to+access+videos");
    exit();
}

// Fetch all videos from tutorial_videos table
$videos = [];
$videos_query = "SELECT id, tutor_id, title, description, url as video_url, thumbnail, duration, is_free, created_at 
                FROM tutorial_videos 
                ORDER BY created_at DESC";
$videos_result = mysqli_query($con, $videos_query);

if ($videos_result) {
    while ($video = mysqli_fetch_assoc($videos_result)) {
        // Check if video is locked
        $is_locked = false;
        if (!$video['is_free']) {
            if (!$isLoggedIn) {
                $is_locked = true;
            } else {
                $is_locked = !isPremiumMember($con, $user['id'], $video['tutor_id']);
            }
        }
        $video['is_locked'] = $is_locked;

        // Set default values for tutorial videos (they don't have course association)
        $video['course_title'] = 'Tutorial Video';
        $video['course_level'] = 'tutorial';
        $videos[] = $video;
    }
}

// Function to get level color
function getLevelColor($level) {
    switch(strtolower($level)) {
        case 'beginner': return '#28a745';
        case 'intermediate': return '#ffc107';  
        case 'advanced': return '#dc3545';
        case 'tutorial': return '#ff6b6b';
        default: return '#6c757d';
    }
}

// Function to get level emoji (use safe ASCII/emoji)
function getLevelEmoji($level) {
    switch (strtolower($level)) {
        case 'beginner': return '🧘';
        case 'intermediate': return '⚖️';
        case 'advanced': return '🔥';
        case 'tutorial': return '🎬';
        default: return '📘';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Videos - YogaMart</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>" />
  <style>
    .videos-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    
    .video-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }
    
    .video-card {
      background: white;
      border-radius: 20px;
      padding: 1.25rem;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid #f0f0f0;
      cursor: pointer;
      display: flex;
      flex-direction: column;
    }
    
    .video-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }
    
    .video-thumbnail {
      width: 100%;
      height: 180px;
      border-radius: 12px;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: #adb5bd;
      margin-bottom: 1.25rem;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .video-thumbnail img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .video-card:hover .video-thumbnail img {
      transform: scale(1.08);
    }
    
    .play-button {
      position: absolute;
      width: 60px;
      height: 60px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #4a7c59;
      font-size: 24px;
      transition: all 0.3s ease;
    }
    
    .video-card:hover .play-button {
      background: white;
      transform: scale(1.1);
    }
    
    .video-info {
      flex: 1;
    }
    
    .video-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #2d5a3d;
      margin-bottom: 0.5rem;
      line-height: 1.3;
    }
    
    .video-course {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 0.5rem;
      font-style: italic;
    }
    
    .video-meta {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 0.75rem;
      flex-wrap: wrap;
    }
    
    .course-level-badge {
      padding: 0.3rem 0.6rem;
      border-radius: 15px;
      color: white;
      font-size: 0.8rem;
      font-weight: 500;
    }
    
    .video-duration {
      color: #4a7c59;
      font-size: 0.85rem;
      font-weight: 500;
      background: #e8f6f5;
      padding: 0.25rem 0.5rem;
      border-radius: 10px;
    }
    
    .video-description {
      color: #666;
      line-height: 1.5;
      font-size: 0.95rem;
      margin-bottom: 1rem;
    }
    
    .video-player-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.9);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .video-player-container {
      background: white;
      border-radius: 15px;
      padding: 1rem;
      max-width: 90vw;
      max-height: 90vh;
      position: relative;
    }
    
    .close-player {
      position: absolute;
      top: -10px;
      right: -10px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
    }
    
    .video-player-container video {
      width: 100%;
      max-width: 800px;
      height: auto;
      border-radius: 10px;
    }
    
    .no-videos {
      text-align: center;
      padding: 3rem;
      color: #666;
    }
    
    .filter-controls {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      align-items: center;
    }
    
    .filter-select {
      padding: 0.5rem 1rem;
      border: 2px solid #e0e0e0;
      border-radius: 25px;
      font-size: 0.9rem;
      background: white;
      cursor: pointer;
      transition: border-color 0.3s;
    }
    
    .filter-select:focus {
      outline: none;
      border-color: #4a7c59;
    }
    
    .search-box {
      flex: 1;
      padding: 0.5rem 1rem;
      border: 2px solid #e0e0e0;
      border-radius: 25px;
      font-size: 0.9rem;
      max-width: 300px;
    }
    
    .search-box:focus {
      outline: none;
      border-color: #4a7c59;
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
      opacity: 0.8;
    }
    
    .video-card.locked .play-button {
      background: rgba(0, 0, 0, 0.7);
      color: #ffc107;
    }
    
    .locked-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #ffc107;
      color: #212529;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      z-index: 10;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <?php
 include 'includes/header.php'; ?>
  
  <?php
 if (!$isLoggedIn): ?>
  <!-- Login Required Overlay -->
  <div class="login-required-overlay">
    <div class="login-prompt">
        <h3>📹 Login Required</h3>
      <p>Please log in to access our video library and start your yoga journey!</p>
      <a href="login-registration.php" class="btn-login">Login / Register</a>
      <a href="home.php" class="btn-home">Back to Home</a>
    </div>
  </div>
  <?php
 endif; ?>

  <section id="videos" class="section section--alt">
    <div class="container">
      <header class="section__head">
        <h2>Tutorial Video Library</h2>
        <p class="muted">Explore our collection of tutorial videos to enhance your yoga practice.</p>
      </header>
      
      <div class="videos-container">
        <!-- Filter Controls -->
        <div class="filter-controls">
          <input type="text" class="search-box" placeholder="Search tutorial videos..." id="searchBox">
        </div>
        
        <?php
 if (empty($videos)): ?>
          <div class="no-videos">
            <h3>📹 No Tutorial Videos Available Yet</h3>
            <p>We're working on adding amazing tutorial videos for you. Please check back soon!</p>
          </div>
        <?php
 else: ?>
          <div class="video-grid" id="videoGrid">
            <?php
 foreach ($videos as $video): ?>
              <div class="video-card <?php echo $video['is_locked'] ? 'locked' : ''; ?>" 
                   data-title="<?php
 echo strtolower($video['title']); ?>"
                   onclick="<?php
 if ($video['is_locked']) {
     echo "window.location.href='tutor_page.php?id=" . $video['tutor_id'] . "'";
 } else {
     echo !empty($video['video_url']) ? "playVideo('" . addslashes($video['video_url']) . "', '" . addslashes($video['title']) . "')" : "showGlobalToast('Video file not available', 'error')";
 } ?>">
                
                <div class="video-thumbnail">
                  <?php if ($video['is_locked']): ?>
                    <div class="locked-badge">🔒 Premium</div>
                  <?php endif; ?>

                  <?php
 if (!empty($video['thumbnail']) && file_exists($video['thumbnail'])): ?>
                    <img src="<?php
 echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php
 echo htmlspecialchars($video['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php
 else: ?>
                    <?php
 echo getLevelEmoji('tutorial'); ?>
                  <?php
 endif; ?>
                  <?php
 if ($video['is_locked']): ?>
                    <div class="play-button"><i class="fas fa-lock"></i></div>
                  <?php elseif (!empty($video['video_url'])): ?>
                    <div class="play-button">&#9658;</div>
                  <?php
 else: ?>
                    <div class="play-button" style="background: rgba(255,0,0,0.7); color: white;">&#9888;</div>
                  <?php
 endif; ?>
                </div>
                
                <div class="video-info">
                  <h3 class="video-title">
                    <?php echo htmlspecialchars($video['title']); ?>
                    <?php if ($video['is_locked']): ?>
                      <span style="font-size: 0.9rem; color: #ffc107;">🔒</span>
                    <?php endif; ?>
                  </h3>
                  
                  <div class="video-course">Tutorial Video</div>
                  
                  <div class="video-meta">
                    <span class="course-level-badge" style="background-color: <?php
 echo getLevelColor('tutorial'); ?>">
                      Tutorial
                    </span>
                    
                    <?php
 if ($video['duration']): ?>
                      <span class="video-duration">Duration: <?php
echo htmlspecialchars($video['duration']); ?></span>
                    <?php
 endif; ?>

                    <span style="font-weight: bold; color: #4a7c59; margin-left: auto;">
                      <?php echo $video['is_free'] ? 'Free' : 'Premium'; ?>
                    </span>
                  </div>
                  
                  <?php
 if ($video['description']): ?>
                    <div class="video-description">
                      <?php
 echo htmlspecialchars($video['description']); ?>
                    </div>
                  <?php
 endif; ?>
                  
                  <?php
 
                  $missing_items = [];
                  if (empty($video['video_url'])) $missing_items[] = "video file";
                  if (empty($video['thumbnail'])) $missing_items[] = "thumbnail";
                  
                  if (!empty($missing_items)): ?>
                    <div style="color: #dc3545; font-size: 0.85rem; font-style: italic; margin-top: 0.5rem;">
                      &#9888; Missing: <?php
echo implode(', ', $missing_items); ?>
                    </div>
                  <?php
 endif; ?>
                </div>
              </div>
            <?php
 endforeach; ?>
          </div>
        <?php
 endif; ?>
      </div>
    </div>
  </section>

  <!-- Video Player Modal -->
  <div class="video-player-modal" id="videoPlayerModal">
    <div class="video-player-container">
      <button class="close-player" onclick="closeVideoPlayer()">&times;</button>
      <video controls id="videoPlayer">
        Your browser does not support the video tag.
      </video>
    </div>
  </div>

  <!-- Footer -->
  <?php
 include 'includes/footer.php'; ?>

  <script>
    function playVideo(videoUrl, videoTitle) {
      const modal = document.getElementById('videoPlayerModal');
      const videoPlayer = document.getElementById('videoPlayer');
      
      // Set video source
      videoPlayer.src = videoUrl;
      
      // Show modal
      modal.style.display = 'flex';
      
      // Auto play (will be blocked by browsers if user hasn't interacted)
      videoPlayer.load();
      
      console.log(`Playing: ${videoTitle} from ${videoUrl}`);
    }
    
    function closeVideoPlayer() {
      const modal = document.getElementById('videoPlayerModal');
      const videoPlayer = document.getElementById('videoPlayer');
      
      // Stop video
      videoPlayer.pause();
      videoPlayer.src = '';
      
      // Hide modal
      modal.style.display = 'none';
    }
    
    // Close modal when clicking outside
    document.getElementById('videoPlayerModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeVideoPlayer();
      }
    });
    
    // Escape key to close modal
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeVideoPlayer();
      }
    });
    
    // Filter functionality
    function filterVideos() {
      const searchTerm = document.getElementById('searchBox').value.toLowerCase();
      const videoCards = document.querySelectorAll('.video-card');
      
      videoCards.forEach(card => {
        const title = card.dataset.title;
        const matchesSearch = title.includes(searchTerm);
        
        if (matchesSearch) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }
    
    // Add event listener for search
    document.getElementById('searchBox').addEventListener('input', filterVideos);

    // Auto-filter based on URL parameter
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const searchTerm = urlParams.get('search');
      
      if (searchTerm) {
        const searchBox = document.getElementById('searchBox');
        searchBox.value = searchTerm;
        filterVideos();
      }
    });
  </script>
</body>
</html>



