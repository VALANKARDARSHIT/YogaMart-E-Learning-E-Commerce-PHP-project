<?php
require_once '../includes/init.php';
requireRole('admin');
include '../includes/connect.php';

$tutor_id = $_GET['tutor_id'] ?? null;

if (!$tutor_id) {
    header("Location: admin-panel.php");
    exit();
}

// Fetch tutor details
$tutor_query = "SELECT * FROM tutors WHERE id = ?";
$stmt = $con->prepare($tutor_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$tutor = $stmt->get_result()->fetch_assoc();

if (!$tutor) {
    die("Tutor not found.");
}

// Fetch tutor's courses
$courses_query = "SELECT * FROM courses WHERE tutor_id = ?";
$stmt = $con->prepare($courses_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

// Fetch tutor's course videos
$videos_query = "SELECT cv.*, c.title as course_title FROM course_videos cv JOIN courses c ON cv.course_id = c.id WHERE c.tutor_id = ?";
$stmt = $con->prepare($videos_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$videos_result = $stmt->get_result();
$course_videos = [];
while ($row = $videos_result->fetch_assoc()) {
    $course_videos[] = $row;
}

// Fetch tutor's tutorial videos (standalone)
$tutorials_query = "SELECT * FROM tutorial_videos WHERE tutor_id = ?";
$stmt = $con->prepare($tutorials_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$tutorials_result = $stmt->get_result();
$tutorials = [];
while ($row = $tutorials_result->fetch_assoc()) {
    $tutorials[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | View Tutor Content</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Open+Sans:wght@300;400;500&display=swap" rel="stylesheet?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-panel.css?v=<?php echo time(); ?>">
    <style>
        .tutor-info-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .tutor-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            overflow: hidden;
        }
        .tutor-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .section-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .item-card {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
            background: #fff;
        }
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .item-thumb {
            width: 100%;
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .item-thumb i {
            font-size: 3rem;
            color: #ddd;
        }
        .item-body {
            padding: 1rem;
        }
        .item-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2a5948;
        }
        .item-meta {
            font-size: 0.85rem;
            color: #666;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: var(--brand);
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="admin-panel">
        <!-- Mobile Toggle Button (Floating) -->
        <button class="sidebar-toggle-mobile" id="mobileSidebarToggle" onclick="document.getElementById('sidebarToggle').click()">
            <i class="fas fa-bars"></i>
        </button>

        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="sidebar-menu-container">
                <ul class="admin-menu">
                    <li class="menu-label">Navigation</li>
                    <li><a href="admin-panel.php"><i class="fas fa-arrow-left fa-fw"></i> Back to Panel</a></li>
                    <li class="active"><a href="#"><i class="fas fa-chalkboard-teacher fa-fw"></i> Tutor Content</a></li>
                    
                    <li class="menu-label">Actions</li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <div class="main-content" id="dashboardMain">
            <main style="padding: 40px;">
                <a href="admin-panel.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Admin Panel</a>
            
                <div class="tutor-info-banner">
                    <div class="tutor-avatar">
                        <?php 
                        $tutorPic = $tutor['profile_picture'] ?? '';
                        $showTutorPic = false;
                        if (!empty($tutorPic)) {
                            $checkPath = '../' . $tutorPic;
                            if (file_exists($checkPath)) {
                                $tutorPic = $checkPath;
                                $showTutorPic = true;
                            }
                        }
                        ?>
                        <?php if ($showTutorPic): ?>
                            <img src="<?php echo htmlspecialchars($tutorPic); ?>" alt="Tutor">
                        <?php else: ?>
                            <?php echo strtoupper(substr($tutor['username'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="tutor-details">
                        <h1 style="margin: 0; font-family: 'Playfair Display', serif;"><?php echo htmlspecialchars($tutor['username']); ?></h1>
                        <p style="margin: 5px 0 0; opacity: 0.9;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($tutor['email']); ?></p>
                        <p style="margin: 5px 0 0; font-size: 0.9rem; opacity: 0.8;"><i class="fas fa-calendar-check"></i> Joined: <?php echo date('M d, Y', strtotime($tutor['created_at'])); ?></p>
                        <button onclick="fixAllDurations()" class="btn" style="margin-top: 15px; background: #f39c12; color: white; border: none; font-size: 0.8rem;">
                            <i class="fas fa-magic"></i> Auto-Fix Zero Durations
                        </button>
                    </div>
                </div>

                <div class="section-card">
                    <h2 class="section-title" style="margin-bottom: 20px;"><i class="fas fa-book-open"></i> Courses (<?php echo count($courses); ?>)</h2>
                    <?php if (empty($courses)): ?>
                        <p style="color: #999; text-align: center; padding: 2rem;">No courses uploaded by this tutor.</p>
                    <?php else: ?>
                        <div class="item-grid">
                            <?php foreach ($courses as $course): ?>
                                <div class="item-card">
                                    <div class="item-thumb">
                                        <?php 
                                        $thumb = $course['thumbnail'];
                                        $showThumb = false;
                                        if (!empty($thumb)) {
                                            $checkPath = '../' . $thumb;
                                            if (file_exists($checkPath)) {
                                                $thumb = $checkPath;
                                                $showThumb = true;
                                            }
                                        }
                                        ?>
                                        <?php if ($showThumb): ?>
                                            <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Thumb">
                                        <?php else: ?>
                                            <i class="fas fa-book"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-body">
                                        <div class="item-title"><?php echo htmlspecialchars($course['title']); ?></div>
                                        <div class="item-meta">
                                            Level: <?php echo ucfirst($course['level'] ?? 'N/A'); ?><br>
                                            Status: <?php echo ($course['is_free'] ?? 1) ? 'Free' : 'Premium'; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <h2 class="section-title" style="margin-bottom: 20px;"><i class="fas fa-video"></i> Course Videos (<?php echo count($course_videos); ?>)</h2>
                    <?php if (empty($course_videos)): ?>
                        <p style="color: #999; text-align: center; padding: 2rem;">No course videos uploaded by this tutor.</p>
                    <?php else: ?>
                        <div class="item-grid">
                            <?php foreach ($course_videos as $video): ?>
                                <div class="item-card">
                                    <div class="item-thumb">
                                        <?php 
                                        $vThumb = $video['thumbnail'] ?? '';
                                        $showVThumb = false;
                                        if (!empty($vThumb)) {
                                            $checkPath = '../' . $vThumb;
                                            if (file_exists($checkPath)) {
                                                $vThumb = $checkPath;
                                                $showVThumb = true;
                                            }
                                        }
                                        ?>
                                        <?php if ($showVThumb): ?>
                                            <img src="<?php echo htmlspecialchars($vThumb); ?>" alt="Thumb">
                                        <?php else: ?>
                                            <i class="fas fa-play-circle"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-body">
                                        <div class="item-title"><?php echo htmlspecialchars($video['title']); ?></div>
                                        <div class="item-meta">
                                            Course: <?php echo htmlspecialchars($video['course_title']); ?><br>
                                            Duration: <?php echo htmlspecialchars($video['duration'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="section-card">
                    <h2 class="section-title" style="margin-bottom: 20px;"><i class="fas fa-play-circle"></i> Standalone Tutorials (<?php echo count($tutorials); ?>)</h2>
                    <?php if (empty($tutorials)): ?>
                        <p style="color: #999; text-align: center; padding: 2rem;">No standalone tutorials uploaded by this tutor.</p>
                    <?php else: ?>
                        <div class="item-grid">
                            <?php foreach ($tutorials as $tutorial): ?>
                                <div class="item-card">
                                    <div class="item-thumb">
                                        <?php 
                                        $tThumb = $tutorial['thumbnail'] ?? '';
                                        $showTThumb = false;
                                        if (!empty($tThumb)) {
                                            $checkPath = '../' . $tThumb;
                                            if (file_exists($checkPath)) {
                                                $tThumb = $checkPath;
                                                $showTThumb = true;
                                            }
                                        }
                                        ?>
                                        <?php if ($showTThumb): ?>
                                            <img src="<?php echo htmlspecialchars($tThumb); ?>" alt="Thumb">
                                        <?php else: ?>
                                            <i class="fas fa-video"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-body">
                                        <div class="item-title"><?php echo htmlspecialchars($tutorial['title']); ?></div>
                                        <div class="item-meta">
                                            Status: <?php echo ($tutorial['is_free'] ?? 1) ? 'Free' : 'Premium'; ?><br>
                                            Duration: <?php echo htmlspecialchars($tutorial['duration'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            <?php include '../includes/footer.php'; ?>
        </div>
    </div>

    <script>
        // DOM Elements
        const body = document.body;
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        
        // Toggle Sidebar function
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            body.classList.toggle('sidebar-active');
            
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }

        sidebarToggle.addEventListener('click', toggleSidebar);

        // Initial state for larger screens
        if (window.innerWidth > 900) {
            sidebar.classList.add('active');
            body.classList.add('sidebar-active');
            const icon = sidebarToggle.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
        }

        // Adjust on resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 900) {
                if (!sidebar.classList.contains('active')) {
                    sidebar.classList.add('active');
                    body.classList.add('sidebar-active');
                }
            } else {
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    body.classList.remove('sidebar-active');
                }
            }
        });

        // Function to fix zero durations
        async function fixAllDurations() {
            const courseVideos = <?php echo json_encode($course_videos); ?>;
            const standaloneTutorials = <?php echo json_encode($tutorials); ?>;
            
            let fixedCount = 0;
            showLoading('Scanning and fixing durations...');

            const processVideo = async (video, type) => {
                const currentDuration = video.duration || '00:00';
                if (currentDuration === '00:00' || currentDuration === '00:00:00' || !currentDuration) {
                    return new Promise((resolve) => {
                        const v = document.createElement('video');
                        v.preload = 'metadata';
                        v.onloadedmetadata = async function() {
                            window.URL.revokeObjectURL(v.src);
                            const duration = v.duration;
                            const hours = Math.floor(duration / 3600);
                            const minutes = Math.floor((duration % 3600) / 60);
                            const seconds = Math.floor(duration % 60);
                            
                            let formatted;
                            if (type === 'tutorial') {
                                formatted = (hours < 10 ? '0' : '') + hours + ':' + 
                                            (minutes < 10 ? '0' : '') + minutes + ':' + 
                                            (seconds < 10 ? '0' : '') + seconds;
                            } else {
                                formatted = (minutes < 10 ? '0' : '') + minutes + ':' + 
                                            (seconds < 10 ? '0' : '') + seconds;
                            }

                            // Update DB
                            const formData = new FormData();
                            formData.append('video_id', video.id);
                            formData.append('type', type);
                            formData.append('duration', formatted);

                            try {
                                const res = await fetch('fix_duration.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const data = await res.json();
                                if (data.success) fixedCount++;
                            } catch (e) {
                                console.error('Error fixing duration for video ' + video.id, e);
                            }
                            resolve();
                        };
                        v.onerror = () => resolve();
                        // Assume video_url or url exists
                        v.src = '../' + (video.video_url || video.url);
                    });
                }
            };

            for (const vid of courseVideos) await processVideo(vid, 'course');
            for (const tut of standaloneTutorials) await processVideo(tut, 'tutorial');

            hideLoading();
            if (fixedCount > 0) {
                if (typeof showToast === 'function') {
                    showToast(`Successfully fixed ${fixedCount} video durations! Reloading page...`, 'success');
                }
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                if (typeof showToast === 'function') {
                    showToast('No videos with zero duration were found or needed fixing.', 'info');
                }
            }
        }
    </script>
</body>
</html>
