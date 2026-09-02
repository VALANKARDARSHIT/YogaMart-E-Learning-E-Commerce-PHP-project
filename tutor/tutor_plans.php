<?php
require_once '../includes/init.php';
requireApprovedTutor();
include '../includes/connect.php';

$tutor = getCurrentUser();
$tutor_id = $tutor['id'];
$verify_tutor = mysqli_query($con, "SELECT id FROM tutors WHERE id = '$tutor_id'");
$is_valid_tutor = (mysqli_num_rows($verify_tutor) > 0);
$message = '';
$message_type = 'success';

if (!$is_valid_tutor) {
    $message = "Error: Your account is not registered as a Tutor. Only verified tutor accounts can manage content.";
    $message_type = 'error';
}

// Ensure table and column exist
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'subscription_plans'");
if (mysqli_num_rows($table_check) == 0) {
    $create_table = "CREATE TABLE `subscription_plans` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `tutor_id` INT(11) NOT NULL,
        `plan_name` VARCHAR(255) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `duration_months` INT(11) NOT NULL,
        `features` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($con, $create_table);
} else {
    // Check if 'features' column exists
    $column_check = mysqli_query($con, "SHOW COLUMNS FROM `subscription_plans` LIKE 'features'");
    if (mysqli_num_rows($column_check) == 0) {
        mysqli_query($con, "ALTER TABLE `subscription_plans` ADD COLUMN `features` TEXT AFTER `duration_months` ");
    }
    
    // Ensure duration_months is INT (not ENUM or TINYINT with constraints)
    mysqli_query($con, "ALTER TABLE `subscription_plans` MODIFY COLUMN `duration_months` INT(11) NOT NULL");

    // Check if 'is_enabled' column exists
    $status_check = mysqli_query($con, "SHOW COLUMNS FROM `subscription_plans` LIKE 'is_enabled'");
    if (mysqli_num_rows($status_check) == 0) {
        mysqli_query($con, "ALTER TABLE `subscription_plans` ADD COLUMN `is_enabled` TINYINT(1) DEFAULT 1 AFTER `features` ");
    }

    // Ensure user_subscriptions table exists
    $sub_table_check = mysqli_query($con, "SHOW TABLES LIKE 'user_subscriptions'");
    if (mysqli_num_rows($sub_table_check) == 0) {
        $create_sub_table = "CREATE TABLE `user_subscriptions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) NOT NULL,
            `tutor_id` INT(11) NOT NULL,
            `plan_id` INT(11) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `transaction_id` VARCHAR(255) NOT NULL,
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `status` ENUM('active', 'expired') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users_tbl`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`tutor_id`) REFERENCES `tutors`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        mysqli_query($con, $create_sub_table);
    }
}

// Handle AJAX Status Toggle
if (isset($_POST['toggle_status']) && $is_valid_tutor) {
    header('Content-Type: application/json');
    try {
        $plan_id = intval($_POST['plan_id']);
        $new_status = intval($_POST['status']);
        
        $query = "UPDATE subscription_plans SET is_enabled = ? WHERE id = ? AND tutor_id = ?";
        $stmt = $con->prepare($query);
        $stmt->bind_param('iii', $new_status, $plan_id, $tutor_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle AJAX Subscription Check
if (isset($_POST['check_subscriptions']) && $is_valid_tutor) {
    header('Content-Type: application/json');
    try {
        $plan_id = intval($_POST['plan_id']);
        
        // Fetch the plan name to check in premium_members
        $p_query = "SELECT plan_name FROM subscription_plans WHERE id = ?";
        $p_stmt = $con->prepare($p_query);
        $p_stmt->bind_param('i', $plan_id);
        $p_stmt->execute();
        $plan_data = $p_stmt->get_result()->fetch_assoc();
        $plan_name = $plan_data['plan_name'] ?? '';

        // Count active subscriptions in premium_members
        $active_query = "SELECT COUNT(*) as sub_count FROM premium_members WHERE plan_name = ? AND end_date >= CURDATE()";
        $stmt_active = $con->prepare($active_query);
        $stmt_active->bind_param('s', $plan_name);
        $stmt_active->execute();
        $active_count = $stmt_active->get_result()->fetch_assoc()['sub_count'];

        // Count total history
        $total_query = "SELECT COUNT(*) as total_count FROM premium_members WHERE plan_name = ?";
        $stmt_total = $con->prepare($total_query);
        $stmt_total.bind_param('s', $plan_name);
        $stmt_total->execute();
        $total_count = $stmt_total->get_result()->fetch_assoc()['total_count'];

        echo json_encode([
            'success' => true, 
            'active_count' => intval($active_count),
            'total_count' => intval($total_count)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle Plan Creation
if (isset($_POST['create_plan']) && $is_valid_tutor) {
    try {
        $name = mysqli_real_escape_string($con, $_POST['plan_name']);
        $price = floatval($_POST['price']);
        $duration = intval($_POST['duration']);
        $features = mysqli_real_escape_string($con, $_POST['features']);

        $query = "INSERT INTO subscription_plans (tutor_id, plan_name, price, duration_months, features) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($query);
        if ($stmt) {
            $stmt->bind_param('isdis', $tutor_id, $name, $price, $duration, $features);
            if ($stmt->execute()) {
                $message = "Subscription plan created successfully!";
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        } else {
            throw new Exception("Prepare failed: " . $con->error);
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle Plan Update
if (isset($_POST['update_plan']) && $is_valid_tutor) {
    try {
        $plan_id = intval($_POST['plan_id']);
        $name = mysqli_real_escape_string($con, $_POST['plan_name']);
        $price = floatval($_POST['price']);
        $duration = intval($_POST['duration']);
        $features = mysqli_real_escape_string($con, $_POST['features']);

        $query = "UPDATE subscription_plans SET plan_name = ?, price = ?, duration_months = ?, features = ? WHERE id = ? AND tutor_id = ?";
        $stmt = $con->prepare($query);
        if ($stmt) {
            $stmt->bind_param('sdisii', $name, $price, $duration, $features, $plan_id, $tutor_id);
            if ($stmt->execute()) {
                $message = "Subscription plan updated successfully!";
            } else {
                throw new Exception("Update failed: " . $stmt->error);
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle Plan Deletion
if (isset($_GET['delete_id']) && $is_valid_tutor) {
    try {
        $delete_id = intval($_GET['delete_id']);
        
        // 1. Check if plan is disabled
        $check_query = "SELECT is_enabled, plan_name FROM subscription_plans WHERE id = ? AND tutor_id = ?";
        $stmt_check = $con->prepare($check_query);
        $stmt_check->bind_param('ii', $delete_id, $tutor_id);
        $stmt_check->execute();
        $plan_to_delete = $stmt_check->get_result()->fetch_assoc();
        
        if (!$plan_to_delete) {
            throw new Exception("Plan not found.");
        }
        
        if ($plan_to_delete['is_enabled'] == 1) {
            throw new Exception("Cannot delete an active plan. Please disable it first using the toggle switch.");
        }
        
        // 2. Check for active subscriptions
        $sub_query = "SELECT COUNT(*) as sub_count FROM user_subscriptions WHERE plan_id = ? AND status = 'active' AND end_date >= CURDATE()";
        $stmt_sub = $con->prepare($sub_query);
        $stmt_sub->bind_param('i', $delete_id);
        $stmt_sub->execute();
        $sub_data = $stmt_sub->get_result()->fetch_assoc();
        
        if ($sub_data['sub_count'] > 0) {
            throw new Exception("Cannot delete this plan because it has " . $sub_data['sub_count'] . " active student subscription(s). You can only delete plans that no one is currently using.");
        }
        
        // 3. Perform deletion
        $query = "DELETE FROM subscription_plans WHERE id = ? AND tutor_id = ?";
        $stmt = $con->prepare($query);
        if ($stmt) {
            $stmt->bind_param('ii', $delete_id, $tutor_id);
            if ($stmt->execute()) {
                header("Location: tutor_plans.php?msg=Plan+deleted+successfully&msg_type=success");
                exit();
            } else {
                throw new Exception("Failed to delete plan from database.");
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch existing plans
$query = "SELECT * FROM subscription_plans WHERE tutor_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plans - Tutor Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin-panel.css?v=<?php echo time(); ?>">
    <style>
        /* Toggle Switch Styling */
        .status-toggle-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #28a745;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #28a745;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .status-label {
            font-size: 0.9rem;
            font-weight: 700;
            min-width: 60px;
            transition: color 0.3s ease;
        }

        .status-enabled {
            color: #28a745;
        }

        .status-disabled {
            color: #dc3545;
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

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content" id="dashboardMain">
        <main class="tutor-content">
            <div class="card">
                <header class="header">
                    <h1 class="page-title">Manage Subscription Plans</h1>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create New Plan
                    </button>
                </header>
                <div class="content">
                    <div id="js-message-container"></div>
                    <?php 
                    $display_message = $message ?: ($_GET['msg'] ?? '');
                    $display_type = $message_type;
                    if (isset($_GET['msg_type'])) $display_type = $_GET['msg_type'];
                    
                    if (!empty($display_message)) : ?>
                        <div class="alert <?php echo $display_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo htmlspecialchars($display_message); ?>
                        </div>
                    <?php endif; ?>

                    <table class="plans-table" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 2px solid #eee;">
                                <th style="padding: 15px;">Plan Name</th>
                                <th style="padding: 15px;">Price (₹)</th>
                                <th style="padding: 15px;">Duration</th>
                                <th style="padding: 15px;">Status</th>
                                <th style="padding: 15px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($plans)): ?>
                                <tr>
                                    <td colspan="5" style="padding: 30px; text-align: center; color: #888;">No plans created yet. You must create at least one plan to offer premium courses.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($plans as $plan): ?>
                                    <tr style="border-bottom: 1px solid #f0f0f0;">
                                        <td style="padding: 15px; font-weight: 600;"><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                                        <td style="padding: 15px;">₹<?php echo number_format($plan['price'], 2); ?></td>
                                        <td style="padding: 15px;"><?php echo $plan['duration_months']; ?> Month(s)</td>
                                        <td style="padding: 15px;">
                                            <div class="status-toggle-container">
                                                <label class="switch">
                                                    <input type="checkbox" 
                                                           <?php echo $plan['is_enabled'] ? 'checked' : ''; ?> 
                                                           onchange="togglePlanStatus(<?php echo $plan['id']; ?>, this)">
                                                    <span class="slider"></span>
                                                </label>
                                                <span class="status-label <?php echo $plan['is_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                                                    <?php echo $plan['is_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td style="padding: 15px;">
                                            <div class="action-buttons">
                                                <button class="edit-btn" onclick='openEditModal(<?php echo json_encode($plan); ?>)'>
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="delete-btn" onclick="handleDelete(<?php echo $plan['id']; ?>, this)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php include '../includes/footer.php'; ?>
    </div>
</div>

<!-- Plan Modal (Shared for Create/Edit) -->
<div id="planModal" class="modal">
    <div class="modal-content">
        <header class="modal-header">
            <h2 class="modal-title" id="modalTitle">Create Subscription Plan</h2>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </header>
        <form method="POST" id="planForm">
            <input type="hidden" name="plan_id" id="plan_id">
            <div class="form-group">
                <label>Plan Name</label>
                <input type="text" name="plan_name" id="plan_name" placeholder="e.g. Monthly Premium" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" id="price" step="0.01" value="499.00" required>
                </div>
                <div class="form-group">
                    <label>Duration (Months)</label>
                    <input type="number" name="duration" id="duration" min="1" max="60" value="3" required>
                </div>
            </div>
            <div class="form-group">
                <label>Key Features (One per line)</label>
                <textarea name="features" id="features" rows="4" placeholder="Access to all premium videos&#10;One-on-one consultation"></textarea>
            </div>
            <div style="margin-top: 10px;">
                <button type="submit" name="create_plan" id="submitBtn" class="btn btn-primary" style="width: 100%;">Create Plan</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/admin-sidebar.js"></script>
<script>
    const modal = document.getElementById('planModal');
    const planForm = document.getElementById('planForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');

    function openCreateModal() {
        modalTitle.innerText = "Create Subscription Plan";
        submitBtn.innerText = "Create Plan";
        submitBtn.name = "create_plan";
        planForm.reset();
        document.getElementById('plan_id').value = "";
        modal.style.display = 'flex';
    }

    function openEditModal(plan) {
        modalTitle.innerText = "Edit Subscription Plan";
        submitBtn.innerText = "Update Plan";
        submitBtn.name = "update_plan";
        
        document.getElementById('plan_id').value = plan.id;
        document.getElementById('plan_name').value = plan.plan_name;
        document.getElementById('price').value = plan.price;
        document.getElementById('duration').value = plan.duration_months;
        document.getElementById('features').value = plan.features;
        
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    function showPlanMessage(message, type = 'error') {
        const container = document.getElementById('js-message-container');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        container.innerHTML = `
            <div class="alert ${alertClass}">
                ${message}
                <button type="button" class="close-alert" onclick="this.parentElement.remove()">&times;</button>
            </div>
        `;
        // Scroll to message
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function togglePlanStatus(planId, checkbox) {
        const isChecked = checkbox.checked;
        const status = isChecked ? 1 : 0;
        const label = checkbox.parentElement.nextElementSibling;
        
        // Show immediate visual feedback
        label.innerText = isChecked ? 'Enabled' : 'Disabled';
        label.className = `status-label ${isChecked ? 'status-enabled' : 'status-disabled'}`;

        const formData = new FormData();
        formData.append('toggle_status', '1');
        formData.append('plan_id', planId);
        formData.append('status', status);

        fetch('tutor_plans.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showPlanMessage('Failed to update status: ' + (data.error || 'Unknown error'));
                // Revert UI if failed
                checkbox.checked = !isChecked;
                label.innerText = !isChecked ? 'Enabled' : 'Disabled';
                label.className = `status-label ${!isChecked ? 'status-enabled' : 'status-disabled'}`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showPlanMessage('An error occurred while updating status.');
            // Revert UI
            checkbox.checked = !isChecked;
            label.innerText = !isChecked ? 'Enabled' : 'Disabled';
            label.className = `status-label ${!isChecked ? 'status-enabled' : 'status-disabled'}`;
        });
    }

    async function handleDelete(planId, btnElement) {
        // Find the checkbox in the same row to get the real-time status
        const row = btnElement.closest('tr');
        const checkbox = row.querySelector('input[type="checkbox"]');
        const isEnabled = checkbox.checked ? 1 : 0;

        if (isEnabled == 1) {
            showPlanMessage('Cannot delete an enabled plan. Please disable it first using the toggle switch.');
            return;
        }

        // Check for subscriptions via AJAX
        try {
            const formData = new FormData();
            formData.append('check_subscriptions', '1');
            formData.append('plan_id', planId);

            const response = await fetch('tutor_plans.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                if (data.active_count > 0) {
                    showPlanMessage(`Cannot delete this plan because it has <strong>${data.active_count} active</strong> student subscription(s). You can only delete plans that no one is currently using.`);
                    return;
                } else if (data.total_count > 0) {
                    showPlanMessage(`Cannot delete this plan because it has been <strong>purchased in the past</strong>. To protect financial records, you should keep this plan "Disabled" instead of deleting it.`);
                    return;
                }
            }
        } catch (error) {
            console.error('Error checking subscriptions:', error);
        }

        const confirmed = await window.customConfirm(
            'Confirm Deletion', 
            'Are you sure you want to delete this subscription plan? This action cannot be undone.'
        );

        if (confirmed) {
            window.location.href = `tutor_plans.php?delete_id=${planId}`;
        }
    }
</script>
</body>
</html>
