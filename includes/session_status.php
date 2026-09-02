<?php
// session_status.php - Display session status (for admin/debugging)
if (!function_exists('getSessionStatus')) {
    require_once 'init.php';
}

function displaySessionStatus($show_details = false) {
    $status = getSessionStatus();
    $user = getCurrentUser();
    
    if (!$status['logged_in']) {
        echo '<div class="session-status guest">';
        echo '<span class="status-indicator">🔴 Guest Session</span>';
        echo '</div>';
        return;
    }
    
    $time_remaining = $status['time_remaining'];
    $minutes_remaining = floor($time_remaining / 60);
    
    // Determine status color based on time remaining
    if ($time_remaining < 300) { // Less than 5 minutes
        $indicator_class = 'warning';
        $indicator_icon = '🟡';
    } elseif ($time_remaining < 900) { // Less than 15 minutes  
        $indicator_class = 'caution';
        $indicator_icon = '🟠';
    } else {
        $indicator_class = 'active';
        $indicator_icon = '🟢';
    }
    
    echo '<div class="session-status ' . $indicator_class . '">';
    echo '<span class="status-indicator">' . $indicator_icon . ' ' . $user['username'] . '</span>';
    
    if ($show_details) {
        echo '<div class="session-details">';
        echo '<small>Session expires in: ' . $minutes_remaining . ' min</small><br>';
        echo '<small>Role: ' . ucfirst($user['role']) . '</small>';
        echo '</div>';
    }
    
    echo '</div>';
}

function getSessionStatusCSS() {
    return '
    <style>
    .session-status {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.95);
        padding: 10px 15px;
        border-radius: 12px;
        border: 1px solid var(--border-color, #ddd);
        font-size: 13px;
        z-index: 2000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        gap: 5px;
        transition: all 0.3s ease;
    }
    
    .session-status:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .session-status.active {
        border-left: 4px solid #4CAF50;
    }
    
    .session-status.caution {
        border-left: 4px solid #FF9800;
    }
    
    .session-status.warning {
        border-left: 4px solid #f44336;
        animation: pulse 2s infinite;
    }
    
    .session-status.guest {
        border-left: 4px solid #9E9E9E;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.8; }
        100% { opacity: 1; }
    }
    
    .session-details {
        font-size: 11px;
        color: #666;
        border-top: 1px solid #eee;
        padding-top: 5px;
    }
    
    @media (max-width: 768px) {
        .session-status {
            bottom: 10px;
            right: 10px;
            padding: 8px 12px;
            font-size: 12px;
        }
    }
    </style>';
}
?>