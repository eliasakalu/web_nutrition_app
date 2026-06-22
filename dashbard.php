<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

auth_require_login();
$user_id = auth_user_id();
$user = auth_get_user($pdo, $user_id);

$bmi = auth_calc_bmi($user['weight'], $user['height']);
$bmi_category = auth_bmi_category($bmi);
$daily_calories = auth_calc_calories($user);

include 'includes/header.php';
?>

<div class="welcome-card">
    <h2>Welcome back, <?php echo e($user['name']); ?>! 👋</h2>
    <p>Here's your health summary for today</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>BMI Index</h3>
        <div class="stat-value"><?php echo $bmi ?: '—'; ?></div>
        <div class="stat-label"><?php echo e($bmi_category); ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Daily Calories</h3>
        <div class="stat-value"><?php echo number_format($daily_calories); ?></div>
        <div class="stat-label">kcal per day</div>
    </div>
    
    <div class="stat-card">
        <h3>Current Weight</h3>
        <div class="stat-value"><?php echo e($user['weight']); ?></div>
        <div class="stat-label">kilograms</div>
    </div>
</div>

<div class="card">
    <h3>Quick Actions</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
        <a href="planner.php" class="btn btn-primary">📅 Plan Your Meals</a>
        <a href="progress.php" class="btn btn-secondary">📊 Track Progress</a>
        <a href="profile.php" class="btn btn-secondary">👤 Update Profile</a>
        <a href="foods/view_foods.php" class="btn btn-secondary">🥗 View Foods</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>