<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_functions.php';

auth_start_session();
if (auth_is_logged_in()) { header('Location: /web_nutrition_app/profile.php'); exit; }

$error     = '';
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';
    $old_email = $email;
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $result = auth_login($pdo, $email, $password);
        if ($result['ok']) {
            header('Location: /web_nutrition_app/profile.php'); exit;
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
  <title>Sign In — SmartMeal</title>
  <link rel="stylesheet" href="/web_nutrition_app/assets/css/auth.css">
</head>
<body>

<div class="auth-layout">

  <!-- ── LEFT: green hero ── -->
  <div class="auth-hero">
    <div class="hero-overlay"></div>
    <div class="hero-overlay-grad"></div>

    <div class="hero-top">
      <div class="hero-brand">
        <div class="hero-brand-icon">🥗</div>
        <span class="hero-brand-name">SmartMeal</span>
      </div>
    </div>

    <div class="hero-bottom">
      <h2 class="hero-headline">Fuelling your journey to wellness.</h2>
      <p class="hero-sub">Expertly crafted meal plans designed for your unique lifestyle and nutritional goals.</p>
      <div class="hero-chips">
        <span class="hero-chip">🥦 Personalised Plans</span>
        <span class="hero-chip">📊 BMI Tracking</span>
        <span class="hero-chip">🍽 Weekly Planner</span>
        <span class="hero-chip">🔥 Calorie Goals</span>
      </div>
    </div>
  </div>

  <!-- ── RIGHT: form ── -->
  <div class="auth-panel">
    <div class="auth-box">

      <h1 class="auth-box-title">Welcome Back</h1>
      <p class="auth-box-sub">Sign in to continue to your nutrition journey.</p>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠ <?= e($error) ?></div>
      <?php endif ?>

      <?php if (isset($_GET['logged_out'])): ?>
        <div class="alert alert-success">✓ You've been signed out successfully.</div>
      <?php endif ?>

      <form method="POST" action="/web_nutrition_app/login.php" novalidate>

        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email"
                 value="<?= e($old_email) ?>"
                 placeholder="jane@example.com"
                 required autocomplete="email" autofocus>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password"
                   placeholder="Your password"
                   required autocomplete="current-password">
            <button type="button" class="pw-eye" data-pw-toggle="password">👁</button>
          </div>
        </div>

        <div class="form-row-between">
          <label class="checkbox-label">
            <input type="checkbox" name="remember"> Remember for 30 days
          </label>
          <a href="#" class="link-muted">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary">Sign in →</button>

        <div class="or-row">or continue with</div>

        <button type="button" class="btn btn-secondary" style="width:100%;gap:.5rem;">
          <span>🔗</span> Sign in with SSO
        </button>

      </form>

      <div class="auth-foot">
        Don't have an account? <a href="/web_nutrition_app/register.php">Sign up today</a>
      </div>
      <div class="auth-privacy">
        <a href="#">Privacy Policy</a> · <a href="#">Terms of Service</a>
      </div>

    </div>
  </div>

</div>

<script src="/web_nutrition_app/assets/js/auth.js"></script>
</body>
</html>
