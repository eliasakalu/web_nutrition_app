<?php

function auth_start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_is_logged_in() {
    auth_start_session();
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function auth_require_login($redirect = 'login.php') {
    if (!auth_is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function auth_user_id() { 
    auth_start_session();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function auth_register($pdo, $data) {
    $email = strtolower(trim($data['email']));
    $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $chk->execute([$email]);
    if ($chk->fetch()) {
        return ['ok' => false, 'error' => 'Email already exists'];
    }
    
    $age = (int)$data['age'];
    $weight = (float)$data['weight'];
    $height = (float)$data['height'];
    
    if ($age <= 0 || $age > 120) {
        return ['ok' => false, 'error' => 'Age must be between 1 and 120'];
    }
    if ($weight <= 0 || $weight >= 600) {
        return ['ok' => false, 'error' => 'Weight must be between 1 and 599 kg'];
    }
    if ($height <= 0 || $height >= 270) {
        return ['ok' => false, 'error' => 'Height must be between 1 and 269 cm'];
    }
    
    if (empty(trim($data['name']))) {
        return ['ok' => false, 'error' => 'Name is required'];
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Valid email is required'];
    }
    if (empty($data['password']) || strlen($data['password']) < 6) {
        return ['ok' => false, 'error' => 'Password must be at least 6 characters'];
    }
    
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password, age, weight, height, gender, goal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    
    try {
        $stmt->execute([
            trim($data['name']), $email, $hash,
            $age, $weight,
            $height, $data['gender'], $data['goal']
        ]);
        return ['ok' => true, 'user_id' => (int)$pdo->lastInsertId()];
    } catch(PDOException $e) {
        return ['ok' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
    }
}

function auth_login($pdo, $email, $password) {
    auth_start_session();
    $email = strtolower(trim($email));
    $stmt = $pdo->prepare('SELECT id, name, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['ok' => false, 'error' => 'Invalid email or password'];
    }
    
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    return ['ok' => true];
}

function auth_logout($redirect = 'login.php') {
    auth_start_session();
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $redirect);
    exit;
}

function auth_get_user($pdo, $user_id) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function auth_update_profile($pdo, $user_id, $data) {
    $age = (int)$data['age'];
    $weight = (float)$data['weight'];
    $height = (float)$data['height'];
    
    if ($age <= 0 || $age > 120) {
        return ['ok' => false, 'error' => 'Age must be between 1 and 120'];
    }
    if ($weight <= 0 || $weight >= 600) {
        return ['ok' => false, 'error' => 'Weight must be between 1 and 599 kg'];
    }
    if ($height <= 0 || $height >= 270) {
        return ['ok' => false, 'error' => 'Height must be between 1 and 269 cm'];
    }
    
    if (empty(trim($data['name']))) {
        return ['ok' => false, 'error' => 'Name is required'];
    }
    
    try {
        $stmt = $pdo->prepare('UPDATE users SET name=?, age=?, weight=?, height=?, gender=?, goal=? WHERE id=?');
        $stmt->execute([
            trim($data['name']), $age, $weight,
            $height, $data['gender'], $data['goal'], $user_id
        ]);
        return ['ok' => true];
    } catch(PDOException $e) {
        return ['ok' => false, 'error' => 'Update failed: ' . $e->getMessage()];
    }
}

function auth_calc_bmi($weight, $height) {
    if ($height <= 0) return null;
    $h = $height / 100;
    return round($weight / ($h * $h), 1);
}

function auth_bmi_category($bmi) {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
}

function auth_calc_calories($user) {
    $w = (float)($user['weight'] ?? 0);
    $h = (float)($user['height'] ?? 0);
    $a = (int)($user['age'] ?? 0);
    if ($w <= 0 || $h <= 0 || $a <= 0) return null;
    
    $bmr = ($user['gender'] === 'female') 
        ? (10*$w) + (6.25*$h) - (5*$a) - 161
        : (10*$w) + (6.25*$h) - (5*$a) + 5;
    
    $tdee = $bmr * 1.2;
    
    if ($user['goal'] === 'lose_weight') {
        return round($tdee - 500);
    } elseif ($user['goal'] === 'gain_muscle') {
        return round($tdee + 300);
    } else {
        return round($tdee);
    }
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

?>