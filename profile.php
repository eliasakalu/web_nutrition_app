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
$bmi_color = match(true) {
    !$bmi       => '#d5dbd5',
    $bmi < 18.5 => '#60a5fa',
    $bmi < 25   => '#52b788',
    $bmi < 30   => '#f59e0b',
    default     => '#ef4444',
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
$avatar = match($user['gender']) { 'female' => '👩', 'male' => '👨', default => '🧑' };
$bmi_warn = $bmi && ($bmi < 18.5 || $bmi >= 30);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile — SmartMeal</title>
  <link rel="stylesheet" href="/web_nutrition_app/assets/css/auth.css">
</head>
<body>

<div class="app-wrap">

  <!-- ══ SIDEBAR ══ -->
  <aside class="sidebar">
    <div class="sb-brand">
      <div class="sb-brand-icon">🥗</div>
      <span class="sb-brand-name">SmartMeal</span>
    </div>

    <div class="sb-section">Menu</div>
    <a href="#" class="sb-link"><span class="sb-icon">🏠</span> Dashboard</a>
    <a href="#" class="sb-link"><span class="sb-icon">📅</span> Meal Planner</a>
    <a href="#" class="sb-link"><span class="sb-icon">🍎</span> Food</a>
    <a href="/web_nutrition_app/profile.php" class="sb-link active"><span class="sb-icon">👤</span> Profile</a>

    <div class="sb-spacer"></div>

    <div class="sb-user">
      <div class="sb-avatar"><?= $avatar ?></div>
      <div>
        <div class="sb-user-name"><?= e(explode(' ', $user['name'])[0]) ?></div>
        <div class="sb-user-role"><?= e(auth_goal_label($user['goal'] ?? '')) ?></div>
      </div>
    </div>
  </aside>

  <!-- ══ MAIN CONTENT ══ -->
  <main class="main">

    <!-- Top bar -->
    <div class="main-topbar">
      <div>
        <div class="main-topbar-title">My Profile</div>
        <div class="main-topbar-sub">Manage your health information and account settings</div>
      </div>
      <div class="topbar-btns">
        <a href="/web_nutrition_app/logout.php" class="btn btn-danger btn-sm">Sign out</a>
      </div>
    </div>

    <!-- Alerts -->
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" style="margin-bottom:1.2rem;">
        <span>⚠</span>
        <div><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
      </div>
    <?php endif ?>
    <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:1.2rem;">
        ✓ Profile updated successfully.
      </div>
    <?php endif ?>

    <!-- Profile banner -->
    <div class="profile-banner">
      <div class="profile-banner-avatar"><?= $avatar ?></div>
      <div class="profile-banner-info">
        <div class="profile-banner-name"><?= e($user['name']) ?></div>
        <div class="profile-banner-email"><?= e($user['email']) ?></div>
        <div class="profile-banner-tags">
          <span class="banner-tag"><?= e(auth_goal_label($user['goal'] ?? '')) ?></span>
          <?php if ($bmi): ?>
            <span class="banner-tag <?= $bmi_warn ? 'warn' : '' ?>">
              BMI <?= $bmi ?> — <?= e($bmi_cat) ?>
            </span>
          <?php endif ?>
          <span class="banner-tag">Since <?= date('M Y', strtotime($user['created_at'])) ?></span>
        </div>
      </div>
      <div class="profile-banner-actions">
        <button class="btn btn-secondary btn-sm" id="edit-btn" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.2);color:#fff;">
          ✏ Edit Profile
        </button>
        <button class="btn btn-secondary btn-sm hidden" id="cancel-btn" style="background:rgba(255,255,255,0.12);border-color:rgba(255,255,255,0.2);color:#fff;">
          ✕ Cancel
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card green-card">
        <div class="stat-lbl">Daily Calories</div>
        <div class="stat-val"><?= $calories ? number_format($calories) : '—' ?></div>
        <div class="stat-unit">kcal / day (TDEE)</div>
      </div>
      <div class="stat-card">
        <div class="stat-lbl">BMI</div>
        <div class="stat-val" style="color:<?= $bmi_color ?>;"><?= $bmi ?? '—' ?></div>
        <div class="stat-unit"><?= e($bmi_cat) ?></div>
        <?php if ($bmi): ?>
          <div class="stat-bar-bg">
            <div class="stat-bar-fill" style="width:<?= $bmi_pct ?>%;background:<?= $bmi_color ?>;"></div>
          </div>
        <?php endif ?>
      </div>
      <div class="stat-card">
        <div class="stat-lbl">Age & Gender</div>
        <div class="stat-val"><?= $user['age'] ?? '—' ?></div>
        <div class="stat-unit"><?= e($genders[$user['gender'] ?? ''] ?? '—') ?></div>
      </div>
    </div>

    <!-- Main grid -->
    <div class="profile-grid">

      <!-- Left: view mode + edit form -->
      <div>

        <!-- VIEW MODE -->
        <div class="card" id="view-mode">
          <div class="card-label">Health Details</div>

          <div class="info-row">
            <span class="info-key">Full Name</span>
            <span class="info-val"><?= e($user['name']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-key">Email</span>
            <span class="info-val"><?= e($user['email']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-key">Weight</span>
            <span class="info-val"><?= $user['weight'] ? e($user['weight']) . ' kg' : '—' ?></span>
          </div>
          <div class="info-row">
            <span class="info-key">Height</span>
            <span class="info-val"><?= $user['height'] ? e($user['height']) . ' cm' : '—' ?></span>
          </div>
          <div class="info-row">
            <span class="info-key">Gender</span>
            <span class="info-val"><?= e($genders[$user['gender'] ?? ''] ?? '—') ?></span>
          </div>
          <div class="info-row">
            <span class="info-key">Goal</span>
            <span class="info-val">
              <span class="badge"><?= e(auth_goal_label($user['goal'] ?? '')) ?></span>
            </span>
          </div>
          <div class="info-row">
            <span class="info-key">Member Since</span>
            <span class="info-val"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
          </div>
        </div>

        <!-- EDIT FORM -->
        <div class="card hidden" id="edit-form-card">
          <form method="POST" action="/web_nutrition_app/profile.php" novalidate>
            <input type="hidden" name="_action" value="update_profile">

            <div class="pf-form-section">Account</div>
            <div class="pf-form-grid">
              <div class="field full">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required>
              </div>
            </div>

            <div class="pf-form-section">Health Information</div>
            <div class="pf-form-grid">
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

              <!-- Live BMI in edit mode -->
              <div class="field full">
                <div class="bmi-strip">
                  <div class="bmi-strip-val" id="bmi-live">—</div>
                  <div class="bmi-strip-info">
                    <div class="bmi-strip-cat" id="bmi-label">BMI Preview</div>
                    <div class="bmi-bar-bg">
                      <div class="bmi-bar-fill" id="bmi-bar-fill" style="width:0;"></div>
                    </div>
                  </div>
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

            <div class="pf-actions">
              <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
              <a href="/web_nutrition_app/logout.php" class="btn btn-danger btn-sm">Sign out</a>
            </div>
          </form>
        </div>

      </div>

      <!-- Right: quick info card -->
      <div>
        <div class="card">
          <div class="card-label">Nutrition Summary</div>

          <div style="text-align:center;padding:1rem 0 1.5rem;">
            <div style="font-size:2.8rem;font-family:'Lora',serif;font-weight:700;color:var(--green-600);">
              <?= $calories ? number_format($calories) : '—' ?>
            </div>
            <div style="font-size:0.78rem;color:var(--gray-400);margin-top:0.25rem;">estimated daily kcal</div>
            <div style="margin-top:1rem;">
              <span class="badge"><?= e(auth_goal_label($user['goal'] ?? '')) ?></span>
            </div>
          </div>

          <div style="border-top:1px solid var(--gray-50);padding-top:1rem;">
            <div class="info-row" style="font-size:0.82rem;">
              <span class="info-key">Weight</span>
              <span class="info-val"><?= $user['weight'] ? e($user['weight']) . ' kg' : '—' ?></span>
            </div>
            <div class="info-row" style="font-size:0.82rem;">
              <span class="info-key">Height</span>
              <span class="info-val"><?= $user['height'] ? e($user['height']) . ' cm' : '—' ?></span>
            </div>
            <div class="info-row" style="font-size:0.82rem;">
              <span class="info-key">BMI</span>
              <span class="info-val" style="color:<?= $bmi_color ?>;"><?= $bmi ?? '—' ?></span>
            </div>
            <div class="info-row" style="font-size:0.82rem;">
              <span class="info-key">Category</span>
              <span class="info-val"><?= e($bmi_cat) ?></span>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /profile-grid -->
  </main>
</div>

<script src="/web_nutrition_app/assets/js/auth.js"></script>
<script>
  const editBtn     = document.getElementById('edit-btn');
  const cancelBtn   = document.getElementById('cancel-btn');
  const viewMode    = document.getElementById('view-mode');
  const editFormCard= document.getElementById('edit-form-card');

  editBtn?.addEventListener('click', () => {
    viewMode.classList.add('hidden');
    editFormCard.classList.remove('hidden');
    editBtn.classList.add('hidden');
    cancelBtn.classList.remove('hidden');
  });
  cancelBtn?.addEventListener('click', () => {
    viewMode.classList.remove('hidden');
    editFormCard.classList.add('hidden');
    editBtn.classList.remove('hidden');
    cancelBtn.classList.add('hidden');
  });
</script>
</body>
</html>
