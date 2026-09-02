<?php
// This file assumes that $user, $message, and $session_user are available from the parent file (Profile.php)
?>
<style>
    :root {
        --primary-color: #4a7c59; /* Default green if not defined elsewhere */
    }
    .profile-card {
        text-align: center;
        padding: 2rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    .profile-image {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        margin: 0 auto 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .profile-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .profile-image-placeholder {
        width: 100%;
        height: 100%;
        background: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 3rem;
    }
    .edit-btn {
        background: var(--primary-color);
        color: white !important;
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 600;
        margin-top: 1rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .edit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        background: #3a6245;
    }
    .modal-open {
        overflow: hidden;
    }
    .modal-open .tutor-dashboard-container,
    .modal-open .site-header {
        pointer-events: none;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow: auto;
        pointer-events: auto; /* Make sure modal is interactive */
    }
    .modal-content {
        padding: 0;
        border-radius: 15px;
        width: 95%;
        max-width: 550px;
        position: relative;
        margin: 2rem auto; /* Reduced margin */
        background: white;
        box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        display: flex;
        flex-direction: column;
        max-height: 90vh; /* Limit height to 90% of viewport */
        overflow: hidden;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        background: #f8f9fa;
        border-bottom: 2px solid #eee;
        flex-shrink: 0; /* Don't let header shrink */
        z-index: 100;
    }
    .modal-content form {
        padding: 2rem;
        overflow-y: auto; /* Enable scrolling only inside the form */
        flex-grow: 1; /* Form takes up remaining space */
        background: white;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 2rem;
        cursor: pointer;
        color: #999;
        transition: color 0.3s;
    }
    .close-btn:hover {
        color: #555;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
    }
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    .form-group input[type="file"] {
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .form-group input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(74, 124, 89, 0.2);
        outline: none;
    }
    .password-section {
        border-top: 1px solid #e1e1e1;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }
    .password-section h3 {
        margin-bottom: 1rem;
        color: #333;
    }
    .form-note {
        font-size: 0.9rem;
        color: #666;
        margin-top: 0.5rem;
    }
    .save-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        width: 100%;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    .save-btn:hover {
        background: #3a6245;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
</style>
<section class="section">
    <div class="container">
        <header class="section__head">
            <h1>Welcome back, <?php echo htmlspecialchars($user['username'] ?? $user['name']); ?>! 👋</h1>
            <p class="muted">Manage your profile and track your progress</p>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message" style="margin-bottom: 2rem;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-image">
                <?php 
                $hasProfileImage = false;
                $imageWebPath = '';
                
                // Support multiple possible column names for profile image
                $imagePath = $user['profile_pic'] ?? $user['image'] ?? $user['profile_picture'] ?? '';
                
                if (!empty($imagePath)) {
                    // Normalize path - if it starts with content/, check if it exists relative to root
                    if (strpos($imagePath, 'content/') === 0) {
                        if (file_exists($imagePath)) {
                            $fileSystemPath = $imagePath;
                            $imageWebPath = $imagePath;
                        } elseif (file_exists('../' . $imagePath)) {
                            $fileSystemPath = '../' . $imagePath;
                            $imageWebPath = '../' . $imagePath;
                        }
                    } else {
                        // Try common locations
                        $locations = ['content/users/profile_pictures/', 'content/user_images/', 'content/tutor_documents/'];
                        foreach ($locations as $loc) {
                            $testPath = $loc . basename($imagePath);
                            if (file_exists($testPath)) {
                                $fileSystemPath = $testPath;
                                $imageWebPath = $testPath;
                                break;
                            } elseif (file_exists('../' . $testPath)) {
                                $fileSystemPath = '../' . $testPath;
                                $imageWebPath = '../' . $testPath;
                                break;
                            }
                        }
                    }
                    
                    if (isset($fileSystemPath)) {
                        $hasProfileImage = file_exists($fileSystemPath);
                    }
                }
                ?>
                
                <?php if ($hasProfileImage): ?>
                    <img src="<?php echo htmlspecialchars($imageWebPath); ?>" alt="Profile Picture">
                <?php else: ?>
                    <div class="profile-image-placeholder">
                        <?php echo strtoupper(substr($user['username'] ?? $user['name'], 0, 2)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <h2><?php echo htmlspecialchars($user['username'] ?? $user['name']); ?></h2>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Member Since:</strong> <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            <button class="edit-btn" onclick="openEditModal()"><i class="fas fa-edit"></i> Edit Profile</button>
        </div>
        
    </div>
</section>
