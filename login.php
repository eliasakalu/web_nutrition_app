<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

auth_start_session();
if (auth_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = auth_login($pdo, $email, $password);
        if ($result['ok']) {
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
    <title>Login - Smart Meal Planner</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="form-container">
        <a href="index.php" class="auth-home-link">Back to Home</a>
        <h2>Welcome Back</h2>
        <p>Sign in to continue your health journey</p>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
            
            <div class="text-center" style="margin-top: 20px;">
                <p>Don't have an account? <a href="register.php" style="color: #2e7d32;">Register here</a></p>
            </div>
        </form>
    </div>
</body>
</html>