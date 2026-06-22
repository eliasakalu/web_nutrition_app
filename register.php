<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

auth_start_session();
if (auth_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $age = (int)$_POST['age'] ?? 0;
    $weight = (float)$_POST['weight'] ?? 0;
    $height = (float)$_POST['height'] ?? 0;
    $gender = $_POST['gender'] ?? '';
    $goal = $_POST['goal'] ?? '';
    
    // Validation
    if (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif ($age < 10 || $age > 120) {
        $error = 'Enter a valid age (10-120)';
    } elseif ($weight < 20 || $weight > 300) {
        $error = 'Enter a valid weight (20-300 kg)';
    } elseif ($height < 50 || $height > 250) {
        $error = 'Enter a valid height (50-250 cm)';
    } else {
        $result = auth_register($pdo, [
            'name' => $name, 'email' => $email, 'password' => $password,
            'age' => $age, 'weight' => $weight, 'height' => $height,
            'gender' => $gender, 'goal' => $goal
        ]);
        
        if ($result['ok']) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['user_name'] = $name;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Meal Planner</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="form-container">
        <a href="index.php" class="auth-home-link">Back to Home</a>
        <h2>Create Account</h2>
        <p>Join us to start your health journey</p>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="Enter your name">
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Min 6 characters">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm password">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" required placeholder="25">
                </div>
                
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="prefer_not">Prefer not to say</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" step="0.1" name="weight" required placeholder="70">
                </div>
                
                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" step="0.1" name="height" required placeholder="170">
                </div>
            </div>
            
            <div class="form-group">
                <label>Health Goal</label>
                <select name="goal" required>
                    <option value="">Select your goal</option>
                    <option value="lose_weight">Lose Weight</option>
                    <option value="maintain">Maintain Weight</option>
                    <option value="gain_muscle">Gain Muscle</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            
            <div class="text-center" style="margin-top: 20px;">
                <p>Already have an account? <a href="login.php" style="color: #2e7d32;">Login here</a></p>
            </div>
        </form>
    </div>
</body>
</html>