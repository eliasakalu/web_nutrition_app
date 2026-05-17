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
            header('Location: /web_nutrition_app/profile.php');
            exit;
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
  <title>Create Account — NutriPlan</title>
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
        <h2>Create your account</h2>
        <p>Set up your health profile to get personalised meal plans</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <span>⚠</span>
          <div><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
        </div>
      <?php endif ?>

      <form method="POST" action="/web_nutrition_app/register.php" novalidate>

        <p class="section-title">Account Details</p>
        <div class="form-grid">

          <div class="field full">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name"
                   value="<?= e($old['name'] ?? '') ?>"
                   placeholder="Jane Doe" required autocomplete="name">
          </div>

          <div class="field full">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   value="<?= e($old['email'] ?? '') ?>"
                   placeholder="jane@example.com" required autocomplete="email">
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Min. 8 characters" required autocomplete="new-password">
            <span class="field-hint" id="pw-strength"></span>
          </div>

          <div class="field">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Repeat password" required autocomplete="new-password">
            <span class="field-hint" id="confirm-msg"></span>
          </div>

        </div>

        <div class="divider">Health Profile</div>

        <div class="form-grid">

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

          <!-- Live BMI preview -->
          <div class="field full">
            <div class="stat-card" style="text-align:left;display:flex;align-items:center;gap:1.5rem;">
              <div>
                <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">BMI Preview</div>
                <div style="display:flex;align-items:baseline;gap:.4rem;margin-top:.2rem;">
                  <span class="val" id="bmi-live" style="font-size:1.4rem;">—</span>
                  <span style="font-size:.85rem;color:var(--text-muted);" id="bmi-label"></span>
                </div>
              </div>
              <div style="flex:1;">
                <div class="bmi-bar-bg">
                  <div class="bmi-bar-fill" id="bmi-bar-fill" style="width:0;background:var(--accent);"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="field full">
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

        </div>

        <div style="margin-top:1.8rem;">
          <button type="submit" class="btn btn-primary">Create Account →</button>
        </div>
      </form>
    </div>

    <div class="auth-footer">
      Already have an account? <a href="/web_nutrition_app/login.php">Sign in</a>
    </div>

  </div>
</div>

<script src="/web_nutrition_app/assets/js/auth.js"></script>
</body>
</html>
