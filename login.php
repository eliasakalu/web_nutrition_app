<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_functions.php';

auth_start_session();
if (auth_is_logged_in()) { header('Location: /web_nutrition_app/profile.php'); exit; }

$error     = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email']    ?? '');
    $password  = $_POST['password'] ?? '';
    $old_email = $email;

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = auth_login($pdo, $email, $password);
        if ($result['ok']) {
            header('Location: /web_nutrition_app/profile.php');
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In — NutriPlan</title>
  <link rel="stylesheet" href="/web_nutrition_app/assets/css/auth.css">
</head>
<body>
<div class="bg-mesh"></div>
<div class="bg-grid"></div>

<div class="page-wrap">
  <div class="auth-container">

    <div class="brand">
      <div class="brand-icon">🥗</div>
      <h1>NutriPlan</h1>
      <p>Smart Meal Planner & Health Nutrition</p>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Welcome back</h2>
        <p>Sign in to continue to your meal plan</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error">
          <span>⚠</span>
          <div><?= e($error) ?></div>
        </div>
      <?php endif ?>

      <?php if (isset($_GET['logged_out'])): ?>
        <div class="alert alert-success">
          <span>✓</span>
          <div>You've been signed out successfully.</div>
        </div>
      <?php endif ?>

      <form method="POST" action="/web_nutrition_app/login.php" novalidate>

        <div class="field" style="margin-bottom:1.2rem;">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 value="<?= e($old_email) ?>"
                 placeholder="jane@example.com"
                 required autocomplete="email" autofocus>
        </div>

        <div class="field" style="margin-bottom:1.6rem;">
          <label for="password">Password</label>
          <div style="position:relative;">
            <input type="password" id="password" name="password"
                   placeholder="Your password"
                   required autocomplete="current-password"
                   style="padding-right:3rem;">
            <button type="button" data-pw-toggle="password"
                    style="position:absolute;right:.8rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">
              👁
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Sign In →</button>

      </form>
    </div>

    <div class="auth-footer">
      Don't have an account? <a href="/web_nutrition_app/register.php">Create one — it's free</a>
    </div>

  </div>
</div>

<script src="/web_nutrition_app/assets/js/auth.js"></script>
</body>
</html>
