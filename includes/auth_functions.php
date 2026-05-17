<?php
/**
 * auth_functions.php
 * Place in: includes/auth_functions.php
 */

function auth_start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_is_logged_in(): bool {
    auth_start_session();
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function auth_require_login(string $redirect = '/web_nutrition_app/login.php'): void {
    if (!auth_is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function auth_user_id(): ?int {
    auth_start_session();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function auth_validate_register(array $data): array {
    $errors = [];
    $name   = trim($data['name']  ?? '');
    $email  = trim($data['email'] ?? '');
    $pw     = $data['password']         ?? '';
    $pw2    = $data['confirm_password'] ?? '';
    $age    = (int)   ($data['age']    ?? 0);
    $wt     = (float) ($data['weight'] ?? 0);
    $ht     = (float) ($data['height'] ?? 0);
    $gender = $data['gender'] ?? '';
    $goal   = $data['goal']   ?? '';

    if (strlen($name) < 2)                        $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($pw) < 8)                           $errors[] = 'Password must be at least 8 characters.';
    if ($pw !== $pw2)                              $errors[] = 'Passwords do not match.';
    if ($age < 10 || $age > 120)                   $errors[] = 'Enter a valid age (10–120).';
    if ($wt  < 20 || $wt  > 500)                  $errors[] = 'Enter a valid weight (20–500 kg).';
    if ($ht  < 50 || $ht  > 300)                  $errors[] = 'Enter a valid height (50–300 cm).';

    $allowed_genders = ['male','female','prefer_not'];
    $allowed_goals   = ['lose_weight','maintain','gain_muscle','improve_health'];
    if (!in_array($gender, $allowed_genders, true)) $errors[] = 'Select a valid gender.';
    if (!in_array($goal,   $allowed_goals,   true)) $errors[] = 'Select a valid goal.';

    return ['ok' => empty($errors), 'errors' => $errors];
}

function auth_register(PDO $pdo, array $data): array {
    $email = strtolower(trim($data['email']));
    $chk   = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $chk->execute([$email]);
    if ($chk->fetch()) {
        return ['ok' => false, 'user_id' => null, 'error' => 'An account with that email already exists.'];
    }
    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('
        INSERT INTO users (name, email, password, age, weight, height, gender, goal)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    try {
        $stmt->execute([
            trim($data['name']), $email, $hash,
            (int)   $data['age'],
            (float) $data['weight'],
            (float) $data['height'],
            $data['gender'],
            $data['goal'],
        ]);
    } catch (PDOException $e) {
        error_log('auth_register: ' . $e->getMessage());
        return ['ok' => false, 'user_id' => null, 'error' => 'Registration failed. Please try again.'];
    }
    return ['ok' => true, 'user_id' => (int) $pdo->lastInsertId(), 'error' => null];
}

function auth_login(PDO $pdo, string $email, string $password): array {
    auth_start_session();
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address.'];
    }
    $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user  = $stmt->fetch(PDO::FETCH_ASSOC);
    $dummy = '$2y$12$invalidhashinvalidhashinvalidhash';
    $hash  = $user ? $user['password'] : $dummy;
    if (!$user || !password_verify($password, $hash)) {
        return ['ok' => false, 'error' => 'Incorrect email or password.'];
    }
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];
    return ['ok' => true, 'error' => null];
}

function auth_logout(string $redirect = '/web_nutrition_app/login.php?logged_out=1'): void {
    auth_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . $redirect);
    exit;
}

function auth_get_user(PDO $pdo, int $user_id): ?array {
    $stmt = $pdo->prepare('SELECT id, name, email, age, weight, height, gender, goal, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function auth_validate_profile_update(array $data): array {
    $errors = [];
    $name   = trim($data['name'] ?? '');
    $age    = (int)   ($data['age']    ?? 0);
    $wt     = (float) ($data['weight'] ?? 0);
    $ht     = (float) ($data['height'] ?? 0);
    if (strlen($name) < 2)        $errors[] = 'Name must be at least 2 characters.';
    if ($age < 10 || $age > 120)  $errors[] = 'Enter a valid age (10–120).';
    if ($wt  < 20 || $wt  > 500) $errors[] = 'Enter a valid weight (20–500 kg).';
    if ($ht  < 50 || $ht  > 300) $errors[] = 'Enter a valid height (50–300 cm).';
    $allowed_genders = ['male','female','prefer_not'];
    $allowed_goals   = ['lose_weight','maintain','gain_muscle','improve_health'];
    if (!in_array($data['gender'] ?? '', $allowed_genders, true)) $errors[] = 'Select a valid gender.';
    if (!in_array($data['goal']   ?? '', $allowed_goals,   true)) $errors[] = 'Select a valid goal.';
    return ['ok' => empty($errors), 'errors' => $errors];
}

function auth_update_profile(PDO $pdo, int $user_id, array $data): bool {
    try {
        $stmt = $pdo->prepare('UPDATE users SET name=?, age=?, weight=?, height=?, gender=?, goal=? WHERE id=?');
        $stmt->execute([
            trim($data['name']),
            (int)   $data['age'],
            (float) $data['weight'],
            (float) $data['height'],
            $data['gender'],
            $data['goal'],
            $user_id,
        ]);
        auth_start_session();
        $_SESSION['user_name'] = trim($data['name']);
        return true;
    } catch (PDOException $e) {
        error_log('auth_update_profile: ' . $e->getMessage());
        return false;
    }
}

function auth_calc_bmi(float $weight_kg, float $height_cm): ?float {
    if ($height_cm <= 0) return null;
    $h = $height_cm / 100;
    return round($weight_kg / ($h * $h), 1);
}

function auth_bmi_category(float $bmi): string {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25)   return 'Normal';
    if ($bmi < 30)   return 'Overweight';
    return 'Obese';
}

function auth_calc_calories(array $user): ?float {
    $w = (float) ($user['weight'] ?? 0);
    $h = (float) ($user['height'] ?? 0);
    $a = (int)   ($user['age']    ?? 0);
    if (!$w || !$h || !$a) return null;
    $bmr  = $user['gender'] === 'female'
          ? (10*$w) + (6.25*$h) - (5*$a) - 161
          : (10*$w) + (6.25*$h) - (5*$a) + 5;
    $tdee = $bmr * 1.2;
    return match($user['goal'] ?? '') {
        'lose_weight' => round($tdee - 500),
        'gain_muscle' => round($tdee + 300),
        default       => round($tdee),
    };
}

function auth_goal_label(string $goal): string {
    return match($goal) {
        'lose_weight'    => 'Lose Weight',
        'maintain'       => 'Maintain Weight',
        'gain_muscle'    => 'Gain Muscle',
        'improve_health' => 'Improve Overall Health',
        default          => ucwords(str_replace('_', ' ', $goal)),
    };
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
