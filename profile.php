<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

auth_require_login();
$user_id = auth_user_id();
$user = auth_get_user($pdo, $user_id);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = (int)$_POST['age'] ?? 0;
    $weight = (float)$_POST['weight'] ?? 0;
    $height = (float)$_POST['height'] ?? 0;
    $gender = $_POST['gender'] ?? '';
    $goal = $_POST['goal'] ?? '';
    
    if (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters';
    } elseif ($age < 10 || $age > 120) {
        $error = 'Enter a valid age';
    } elseif ($weight < 20 || $weight > 300) {
        $error = 'Enter a valid weight';
    } elseif ($height < 50 || $height > 250) {
        $error = 'Enter a valid height';
    } else {
        if (auth_update_profile($pdo, $user_id, [
            'name' => $name, 'age' => $age, 'weight' => $weight,
            'height' => $height, 'gender' => $gender, 'goal' => $goal
        ])) {
            $message = 'Profile updated successfully!';
            $user = auth_get_user($pdo, $user_id);
            $_SESSION['user_name'] = $name;
        } else {
            $error = 'Failed to update profile';
        }
    }
}

$bmi = auth_calc_bmi($user['weight'], $user['height']);
$bmi_category = auth_bmi_category($bmi);
include 'includes/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo $user['gender'] === 'female' ? '👩' : ($user['gender'] === 'male' ? '👨' : '👤'); ?>
        </div>
        <h2><?php echo e($user['name']); ?></h2>
        <p><?php echo e($user['email']); ?></p>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo e($message); ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>
    
    <div class="profile-grid">
        <div class="card">
            <h3>Personal Information</h3>
            <div class="info-row">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?php echo e($user['name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo e($user['email']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Age:</span>
                <span class="info-value"><?php echo e($user['age']); ?> years</span>
            </div>
            <div class="info-row">
                <span class="info-label">Gender:</span>
                <span class="info-value"><?php echo e(ucfirst($user['gender'] ?? 'Not specified')); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Member Since:</span>
                <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
            </div>
        </div>
        
        <div class="card">
            <h3>Health Metrics</h3>
            <div class="info-row">
                <span class="info-label">Weight:</span>
                <span class="info-value"><?php echo e($user['weight']); ?> kg</span>
            </div>
            <div class="info-row">
                <span class="info-label">Height:</span>
                <span class="info-value"><?php echo e($user['height']); ?> cm</span>
            </div>
            <div class="info-row">
                <span class="info-label">BMI:</span>
                <span class="info-value"><?php echo $bmi ?: '—'; ?> (<?php echo e($bmi_category); ?>)</span>
            </div>
            <div class="info-row">
                <span class="info-label">Health Goal:</span>
                <span class="info-value">
                    <?php
                    $goal_labels = [
                        'lose_weight' => '🎯 Lose Weight',
                        'maintain' => '⚖️ Maintain Weight',
                        'gain_muscle' => '💪 Gain Muscle'
                    ];
                    echo $goal_labels[$user['goal']] ?? ucfirst($user['goal']);
                    ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="card" style="margin-top: 20px;">
        <h3>Update Profile</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo e($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" value="<?php echo e($user['age']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" step="0.1" name="weight" value="<?php echo e($user['weight']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" step="0.1" name="height" value="<?php echo e($user['height']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="prefer_not" <?php echo $user['gender'] == 'prefer_not' ? 'selected' : ''; ?>>Prefer not to say</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Health Goal</label>
                    <select name="goal">
                        <option value="lose_weight" <?php echo $user['goal'] == 'lose_weight' ? 'selected' : ''; ?>>Lose Weight</option>
                        <option value="maintain" <?php echo $user['goal'] == 'maintain' ? 'selected' : ''; ?>>Maintain Weight</option>
                        <option value="gain_muscle" <?php echo $user['goal'] == 'gain_muscle' ? 'selected' : ''; ?>>Gain Muscle</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>