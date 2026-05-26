<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_functions.php';

auth_start_session();
if (auth_is_logged_in()) { header('Location: /web_nutrition_app/profile.php'); exit; }

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old        = $_POST;
    $validation = auth_validate_register($_POST);
    if (!$validation['ok']) {
        $errors = $validation['errors'];
    } else {
        $result = auth_register($pdo, $_POST);
        if ($result['ok']) {
            $_SESSION['user_id']   = $result['user_id'];
            $_SESSION['user_name'] = trim($_POST['name']);
            header('Location: /web_nutrition_app/profile.php'); exit;
        } else {
            $errors[] = $result['error'];
        }
    }
}

$goals = [
    'lose_weight'    => 'Lose Weight',
    'maintain'       => 'Maintain Weight',
    'gain_muscle'    => 'Gain Muscle',
    'improve_health' => 'Improve Overall Health',
];
$genders = [
    'male'       => 'Male',
    'female'     => 'Female',
    'prefer_not' => 'Prefer not to say',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Account — SmartMeal</title>
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
    <div class="auth-box" style="max-width:440px;">

      <h1 class="auth-box-title">Create Account</h1>
      <p class="auth-box-sub">Join our community to start your nutritional journey.</p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <span>⚠</span>
          <div><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
        </div>
      <?php endif ?>

      <form method="POST" action="/web_nutrition_app/register.php" novalidate>

        <!-- Account details -->
        <div class="field">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name"
                 value="<?= e($old['name'] ?? '') ?>"
                 placeholder="Jane Doe" required autocomplete="name">
        </div>

        <div class="field">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 value="<?= e($old['email'] ?? '') ?>"
                 placeholder="jane@example.com" required autocomplete="email">
        </div>

        <div class="field-row">
          <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
              <input type="password" id="password" name="password"
                     placeholder="Min. 8 chars" required autocomplete="new-password">
              <button type="button" class="pw-eye" data-pw-toggle="password">👁</button>
            </div>
            <span class="field-hint" id="pw-strength"></span>
          </div>
          <div class="field">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Repeat password" required autocomplete="new-password">
            <span class="field-hint" id="confirm-msg"></span>
          </div>
        </div>

        <!-- Health profile -->
        <div class="field-section">Health Profile</div>

        <div class="field-row">
          <div class="field">
            <label for="age">Age</label>
            <input type="number" id="age" name="age"
                   value="<?= e($old['age'] ?? '') ?>"
                   placeholder="25" min="10" max="120" required>
          </div>
          <div class="field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
              <option value="">Select…</option>
              <?php foreach ($genders as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= ($old['gender'] ?? '') === $val ? 'selected' : '' ?>>
                  <?= e($lbl) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="weight">Weight (kg)</label>
            <input type="number" id="weight" name="weight"
                   value="<?= e($old['weight'] ?? '') ?>"
                   placeholder="70" step="0.1" min="20" max="500" required>
          </div>
          <div class="field">
            <label for="height">Height (cm)</label>
            <input type="number" id="height" name="height"
                   value="<?= e($old['height'] ?? '') ?>"
                   placeholder="170" step="0.1" min="50" max="300" required>
          </div>
        </div>

        <!-- Live BMI -->
        <div class="bmi-strip">
          <div class="bmi-strip-val" id="bmi-live">—</div>
          <div class="bmi-strip-info">
            <div class="bmi-strip-cat" id="bmi-label">Enter weight & height to preview BMI</div>
            <div class="bmi-bar-bg">
              <div class="bmi-bar-fill" id="bmi-bar-fill" style="width:0;"></div>
            </div>
          </div>
        </div>

        <div class="field">
          <label for="goal">Health Goal</label>
          <select id="goal" name="goal" required>
            <option value="">Select your primary goal…</option>
            <?php foreach ($goals as $val => $lbl): ?>
              <option value="<?= e($val) ?>" <?= ($old['goal'] ?? '') === $val ? 'selected' : '' ?>>
                <?= e($lbl) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>

        <div style="margin-top:1.4rem;">
          <button type="submit" class="btn btn-primary">Create Account →</button>
        </div>

        <div class="or-row">or continue with</div>
        <button type="button" class="btn btn-secondary" style="width:100%;">🔗 Sign up with SSO</button>

      </form>

      <div class="auth-foot">
        Already have an account? <a href="/web_nutrition_app/login.php">Log in</a>
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
