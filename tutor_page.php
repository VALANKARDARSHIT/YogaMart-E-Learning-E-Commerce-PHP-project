<?php
require_once 'includes/init.php';
include 'includes/connect.php';

$tutor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($tutor_id <= 0) {
    header("Location: home.php");
    exit();
}

// Fetch tutor details
$tutor_query = "SELECT * FROM tutors WHERE id = ?";
$stmt = $con->prepare($tutor_query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$tutor = $stmt->get_result()->fetch_assoc();

if (!$tutor) {
    header("Location: home.php");
    exit();
}

// Fetch subscription plans for this tutor (only enabled ones)
$plans_query = "SELECT * FROM subscription_plans WHERE tutor_id = ? AND is_enabled = 1";
$stmt_plans = $con->prepare($plans_query);
$stmt_plans->bind_param('i', $tutor_id);
$stmt_plans->execute();
$plans_res = $stmt_plans->get_result();
$plans = [];
while ($row = $plans_res->fetch_assoc()) {
    $plans[] = $row;
}

// Check if current user is already a premium member of this tutor
$is_premium = false;
$current_user = getCurrentUser();
if ($current_user) {
    $is_premium = isPremiumMember($con, $current_user['id'], $tutor_id);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tutor['username']); ?> - Subscription Plans</title>
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tutor-profile-header {
            background: linear-gradient(135deg, #fdfcf0 0%, #f5f9f7 100%);
            padding: 4rem 0;
            text-align: center;
            border-bottom: 2px solid #ffd166;
            margin-bottom: 3rem;
        }
        .tutor-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .tutor-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2a5948;
            margin-bottom: 0.5rem;
        }
        .tutor-bio {
            max-width: 600px;
            margin: 0 auto;
            color: #666;
            line-height: 1.6;
        }
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 5rem;
        }
        .plan-card {
            background: white;
            border: 2px solid #ffd166;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }
        .plan-card.featured {
            border-color: #ff6b6b;
            box-shadow: 0 15px 35px rgba(255, 107, 107, 0.15);
        }
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2a5948;
            margin-bottom: 1rem;
        }
        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            color: #2a5948;
            margin-bottom: 0.5rem;
        }
        .plan-price span {
            font-size: 1rem;
            font-weight: 400;
            color: #888;
        }
        .plan-duration {
            color: #666;
            margin-bottom: 2rem;
            font-weight: 600;
        }
        .plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 2.5rem 0;
            text-align: left;
            flex-grow: 1;
        }
        .plan-features li {
            margin-bottom: 12px;
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .plan-features li i {
            color: #4caf50;
        }
        .btn-subscribe {
            background: #2a5948;
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            display: block;
        }
        .btn-subscribe:hover {
            background: #1d4033;
            transform: scale(1.05);
        }
        .featured-tag {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff6b6b;
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .no-plans {
            text-align: center;
            padding: 5rem 2rem;
            background: #f9fbf8;
            border-radius: 20px;
            border: 2px dashed #ccc;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <header class="tutor-profile-header">
        <div class="container">
            <?php
            $pic_src = 'https://img.icons8.com/color/96/user-male-circle--v1.png';
            if (!empty($tutor['profile_picture'])) {
                $pic_path = $tutor['profile_picture'];
                if (filter_var($pic_path, FILTER_VALIDATE_URL)) {
                    $pic_src = $pic_path;
                } else {
                    // Prepend web_root and ensure we don't have double slashes
                    $pic_src = $web_root . ltrim($pic_path, '/');
                }
            }
            ?>
            <img src="<?php echo htmlspecialchars($pic_src); ?>" alt="<?php echo htmlspecialchars($tutor['username']); ?>" class="tutor-avatar" onerror="this.src='https://img.icons8.com/color/96/user-male-circle--v1.png';">
            <h1 class="tutor-name"><?php echo htmlspecialchars($tutor['username']); ?></h1>
            <p class="tutor-bio"><?php echo htmlspecialchars($tutor['bio'] ?? 'Expert Yoga Instructor dedicated to your wellness journey.'); ?></p>
        </div>
    </header>

    <main class="container">
        <div class="section-title" style="text-align: center; margin-bottom: 3rem;">
            <h2 style="font-family: 'Playfair Display', serif; font-size: 2rem; color: #2a5948;">Choose Your Premium Plan</h2>
            <p style="color: #666;">Get unlimited access to all courses and videos by <?php echo htmlspecialchars($tutor['username']); ?>.</p>
        </div>

        <?php if ($is_premium): ?>
            <div class="alert alert-success" style="background: #e8f5e9; color: #2e7d32; padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 3rem; border: 1px solid #c8e6c9;">
                <i class="fas fa-check-circle"></i> You are already a premium member of this instructor! Enjoy your unlimited access.
            </div>
        <?php endif; ?>

        <?php if (empty($plans)): ?>
            <div class="no-plans">
                <i class="fas fa-calendar-alt" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <h3>No Subscription Plans Available</h3>
                <p>This tutor hasn't set up any subscription plans yet. Please check back later.</p>
            </div>
        <?php else: ?>
            <div class="plans-grid">
                <?php foreach ($plans as $index => $plan): 
                    $is_featured = ($index === 1 || count($plans) === 1); // Mark second plan or only plan as featured
                ?>
                    <div class="plan-card <?php echo $is_featured ? 'featured' : ''; ?>">
                        <?php if ($is_featured): ?>
                            <div class="featured-tag">Most Popular</div>
                        <?php endif; ?>
                        <h3 class="plan-name"><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                        <div class="plan-price">₹<?php echo number_format($plan['price'], 2); ?></div>
                        <div class="plan-duration">for <?php echo $plan['duration_months']; ?> month(s)</div>
                        
                        <ul class="plan-features">
                            <?php 
                            $features = explode("\n", $plan['features'] ?? '');
                            foreach ($features as $feature): 
                                if (trim($feature)):
                            ?>
                                <li><i class="fas fa-check"></i> <?php echo htmlspecialchars(trim($feature)); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <li><i class="fas fa-check"></i> All courses by this tutor</li>
                            <li><i class="fas fa-check"></i> All tutorial videos</li>
                            <li><i class="fas fa-check"></i> Premium community access</li>
                        </ul>

                        <?php if ($is_premium): ?>
                            <button class="btn-subscribe" disabled style="opacity: 0.7; cursor: not-allowed; background: #888;">Currently Subscribed</button>
                        <?php else: ?>
                            <a href="subscription_checkout.php?plan_id=<?php echo $plan['id']; ?>" class="btn-subscribe">Get Started Now</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
