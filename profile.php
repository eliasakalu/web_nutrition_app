<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_functions.php';

auth_require_login('/web_nutrition_app/login.php');

$user_id = auth_user_id();
$user    = auth_get_user($pdo, $user_id);
if (!$user) auth_logout();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update_profile') {
    $validation = auth_validate_profile_update($_POST);
    if (!$validation['ok']) {
        $errors = $validation['errors'];
    } else {
        if (auth_update_profile($pdo, $user_id, $_POST)) {
            $success = true;
            $user    = auth_get_user($pdo, $user_id);
        } else {
            $errors[] = 'Could not save changes. Please try again.';
        }
    }
}

$bmi      = ($user['weight'] && $user['height']) ? auth_calc_bmi($user['weight'], $user['height']) : null;
$bmi_cat  = $bmi ? auth_bmi_category($bmi) : '—';
$calories = auth_calc_calories($user);
$bmi_pct  = $bmi ? max(2, min(98, round(($bmi - 10) / 30 * 100))) : 0;
$bmi_color= match(true) {
    !$bmi       => 'var(--border)',
    $bmi < 18.5 => '#70b4c9',
    $bmi < 25   => '#7cb87a',
    $bmi < 30   => '#c9a870',
    default     => '#c97070',
};

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
$avatar = match($user['gender']) {
    'female' => '👩', 'male' => '👨', default => '🧑'
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile — NutriPlan</title>
  <link rel="stylesheet" href="/web_nutrition_app/assets/css/auth.css">
  <style>
    .hidden { display:none !important; }
    .tag { display:inline-block;background:var(--accent-dim);color:var(--accent);border:1px solid rgba(124,184,122,.25);border-radius:99px;padding:.2rem .75rem;font-size:.78rem;font-weight:500; }
    .info-row { display:flex;align-items:center;justify-content:space-between;padding:.65rem 0;border-bottom:1px solid var(--border);font-size:.9rem; }
    .info-row:last-child { border-bottom:none; }
    .info-label { color:var(--text-muted);font-size:.8rem; }
    .info-val   { color:var(--text);font-weight:500; }
    body { padding-top:60px; }
  </style>
</head>
<body>
<div class="bg-mesh"></div>
<div class="bg-grid"></div>

<nav class="top-nav">
  <a class="nav-brand" href="/web_nutrition_app/profile.php">🥗 NutriPlan</a>
  <div class="nav-links">
    <a href="/web_nutrition_app/profile.php" class="active">Profile</a>
    <a href="/web_nutrition_app/logout.php" class="nav-logout">Sign out</a>
  </div>
</nav>

<div class="page-wrap" style="align-items:flex-start;padding-top:2.5rem;">
  <div class="profile-container">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:1.4rem;">
        <span>⚠</span>
        <div><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</div>'; ?></div>
      </div>
    <?php endif ?>

    <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:1.4rem;">
        <span>✓</span><div>Profile updated successfully.</div>
      </div>
    <?php endif ?>

    <div class="card">

      <!-- Header -->
      <div class="profile-header">
        <div class="avatar"><?= $avatar ?></div>
        <div class="profile-info" style="flex:1;">
          <h2><?= e($user['name']) ?></h2>
          <p><?= e($user['email']) ?></p>
          <div style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.4rem;">
            <span class="tag"><?= e(auth_goal_label($user['goal'] ?? '')) ?></span>
            <?php if ($bmi): ?>
              <span class="tag" style="<?= ($bmi < 18.5 || $bmi >= 30) ? 'background:rgba(201,112,112,.1);color:var(--danger);border-color:rgba(201,112,112,.25)' : '' ?>">
                BMI <?= $bmi ?>
              </span>
            <?php endif ?>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-shrink:0;">
          <button class="btn btn-secondary btn-sm" id="edit-btn">✏ Edit</button>
          <button class="btn btn-secondary btn-sm hidden" id="cancel-btn">✕ Cancel</button>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="val"><?= $bmi ?? '—' ?></div>
          <div class="lbl">BMI</div>
          <?php if ($bmi): ?>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:.2rem;"><?= e($bmi_cat) ?></div>
            <div class="bmi-bar-bg" style="margin-top:.4rem;">
              <div class="bmi-bar-fill" style="width:<?= $bmi_pct ?>%;background:<?= $bmi_color ?>;"></div>
            </div>
          <?php endif ?>
        </div>
        <div class="stat-card">
          <div class="val"><?= $calories ? number_format($calories) : '—' ?></div>
          <div class="lbl">Daily kcal</div>
          <div style="font-size:.7rem;color:var(--text-muted);margin-top:.2rem;">Estimated TDEE</div>
        </div>
        <div class="stat-card">
          <div class="val"><?= $user['age'] ? e($user['age']) : '—' ?></div>
          <div class="lbl">Age</div>
          <div style="font-size:.7rem;color:var(--text-muted);margin-top:.2rem;">
            <?= $user['gender'] ? e($genders[$user['gender']] ?? $user['gender']) : '' ?>
          </div>
        </div>
      </div>

      <!-- View mode -->
      <div id="view-mode">
        <p class="section-title">Health Details</p>
        <div class="info-row"><span class="info-label">Weight</span><span class="info-val"><?= $user['weight'] ? e($user['weight']) . ' kg' : '—' ?></span></div>
        <div class="info-row"><span class="info-label">Height</span><span class="info-val"><?= $user['height'] ? e($user['height']) . ' cm' : '—' ?></span></div>
        <div class="info-row"><span class="info-label">Goal</span><span class="info-val"><?= e(auth_goal_label($user['goal'] ?? '')) ?></span></div>
        <div class="info-row"><span class="info-label">Member since</span><span class="info-val"><?= date('F j, Y', strtotime($user['created_at'])) ?></span></div>
      </div>

      <!-- Edit form -->
      <form id="profile-form" method="POST" action="/web_nutrition_app/profile.php" class="hidden" novalidate>
        <input type="hidden" name="_action" value="update_profile">
        <p class="section-title">Edit Profile</p>
        <div class="form-grid">

          <div class="field full">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
          </div>

          <div class="field">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" value="<?= e($user['age'] ?? '') ?>" min="10" max="120" required>
          </div>

          <div class="field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
              <?php foreach ($genders as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $user['gender'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <div class="field">
            <label for="weight">Weight (kg)</label>
            <input type="number" id="weight" name="weight" value="<?= e($user['weight'] ?? '') ?>" step="0.1" min="20" max="500" required>
          </div>

          <div class="field">
            <label for="height">Height (cm)</label>
            <input type="number" id="height" name="height" value="<?= e($user['height'] ?? '') ?>" step="0.1" min="50" max="300" required>
          </div>

          <!-- Live BMI -->
          <div class="field full">
            <div style="display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);">
              <span style="font-size:.8rem;color:var(--text-muted);">BMI Preview:</span>
              <strong id="bmi-live" style="color:var(--accent);font-family:'Playfair Display',serif;">—</strong>
              <span id="bmi-label" style="font-size:.8rem;color:var(--text-muted);"></span>
              <div style="flex:1;"><div class="bmi-bar-bg"><div class="bmi-bar-fill" id="bmi-bar-fill" style="width:0;background:var(--accent);"></div></div></div>
            </div>
          </div>

          <div class="field full">
            <label for="goal">Health Goal</label>
            <select id="goal" name="goal" required>
              <?php foreach ($goals as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $user['goal'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
              <?php endforeach ?>
            </select>
          </div>

        </div>

        <div class="profile-actions">
          <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
          <a href="/web_nutrition_app/logout.php" class="btn btn-danger btn-sm">Sign out</a>
        </div>
      </form>

      <!-- View mode actions -->
      <div id="view-actions" class="profile-actions" style="justify-content:flex-end;">
        <a href="/web_nutrition_app/logout.php" class="btn btn-danger btn-sm">Sign out</a>
      </div>

    </div>
  </div>
</div>

<script src="/web_nutrition_app/assets/js/auth.js"></script>
<script>
  const editBtn  = document.getElementById('edit-btn');
  const cancelBtn= document.getElementById('cancel-btn');
  const viewActs = document.getElementById('view-actions');
  editBtn?.addEventListener('click',   () => viewActs?.classList.add('hidden'));
  cancelBtn?.addEventListener('click', () => viewActs?.classList.remove('hidden'));
</script>
</body>
</html>
