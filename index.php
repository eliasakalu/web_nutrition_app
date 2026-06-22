<?php
session_start();
$host = 'localhost';
$dbname = 'smart_meal_planner';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(191) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            age TINYINT UNSIGNED NULL,
            weight DECIMAL(5,2) NULL CHECK(weight>0 and weight<600),
            height DECIMAL(5,2) NULL CHECK(height>0 and height<270),
            gender VARCHAR(20) NULL,
            goal VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
   
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS foods (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            calories INT NOT NULL,
            protein DECIMAL(5,2) NOT NULL,
            carbs DECIMAL(5,2) NOT NULL,
            fat DECIMAL(5,2) NOT NULL,
            meal_type VARCHAR(20) NOT NULL
        )
    ");
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM foods");
    $foodCount = $stmt->fetchColumn();
    
    if($foodCount == 0) {
        $pdo->exec("
            INSERT INTO foods (name, calories, protein, carbs, fat, meal_type) VALUES
            -- Ethiopian Breakfast
            ('Firfir (Injera with Berbere)', 310, 9, 52, 8, 'breakfast'),
            ('Chechebsa (Kita Firfir)', 420, 11, 58, 16, 'breakfast'),
            ('Kinche (Cracked Wheat Porridge)', 280, 9, 52, 5, 'breakfast'),
            ('Genfo (Barley Porridge with Niter Kibbeh)', 350, 10, 55, 11, 'breakfast'),
            ('Enqulal Firfir (Ethiopian Scrambled Eggs)', 390, 20, 30, 22, 'breakfast'),
            -- Ethiopian Lunch
            ('Doro Wat (Spiced Chicken Stew) with Injera', 580, 42, 48, 22, 'lunch'),
            ('Shiro Wat (Chickpea Stew) with Injera', 460, 18, 72, 12, 'lunch'),
            ('Misir Wat (Red Lentil Stew) with Injera', 420, 16, 70, 8, 'lunch'),
            ('Tibs (Sautéed Beef with Vegetables)', 520, 38, 18, 30, 'lunch'),
            ('Gomen Besiga (Collard Greens with Beef)', 390, 28, 22, 18, 'lunch'),
            -- Ethiopian Dinner
            ('Kitfo (Ethiopian Beef Tartare) with Injera', 610, 48, 38, 28, 'dinner'),
            ('Yetsom Beyaynetu (Fasting Platter)', 480, 18, 68, 16, 'dinner'),
            ('Zigni (Beef Stew) with Injera', 560, 40, 45, 22, 'dinner'),
            ('Awaze Tibs (Spicy Pan-fried Lamb)', 590, 44, 12, 38, 'dinner'),
            ('Atkilt Wat (Cabbage, Potato & Carrot Stew)', 320, 8, 55, 9, 'dinner'),
            -- Ethiopian Snacks
            ('Kolo (Roasted Barley & Groundnut Mix)', 210, 8, 28, 9, 'snack'),
            ('Sambusa (Ethiopian Pastry with Lentils)', 280, 9, 34, 13, 'snack'),
            ('Besso (Roasted Barley Flour Drink)', 160, 5, 30, 3, 'snack'),
            ('Dabo Kolo (Crunchy Wheat Snack)', 195, 5, 32, 6, 'snack'),
            ('Fresh Mango & Papaya Plate', 130, 2, 32, 0.5, 'snack')
        ");
    }
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS meal_plan (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            day VARCHAR(10) NOT NULL,
            meal_type VARCHAR(20) NOT NULL,
            food_id INT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS progress (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            weight DECIMAL(5,2) NOT NULL,
            date DATE NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}


function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function getBMI($weight, $height) {
    if($height <= 0 || $weight <= 0) return null;
    $h = $height / 100;
    return round($weight / ($h * $h), 1);
}

function getBMICategory($bmi) {
    if($bmi < 18.5) 
        return 'Underweight';
    elseif($bmi < 25) 
        return 'Normal weight';
    elseif($bmi < 30) 
        return 'Overweight';
    else
        return 'Obese';
}

function getBMIColor($bmi) {
    if($bmi < 18.5) 
        return '#60a5fa';
    elseif($bmi < 25) 
        return '#4ade80';
    elseif($bmi < 30) 
        return '#fbbf24';
    else
        return '#ef4444';
}

function getCalories($user) {
    if(!$user['weight'] || !$user['height'] || !$user['age']) return null;
    
    $w = $user['weight'];
    $h = $user['height'];
    $a = $user['age'];
    

    if($user['gender'] == 'female') {
        $bmr = (10 * $w) + (6.25 * $h) - (5 * $a) - 161;
    } else {
        $bmr = (10 * $w) + (6.25 * $h) - (5 * $a) + 5;
    }
    
  
    $tdee = $bmr * 1.2;
    
    if($user['goal'] == 'lose_weight') {
        return round($tdee - 500);
    } elseif($user['goal'] == 'gain_muscle') {
        return round($tdee + 300);
    } else {
        return round($tdee);
    }
}


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = "Invalid email or password";
    }
}

// Register
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $age = (int)$_POST['age'];
    $weight = (float)$_POST['weight'];
    $height = (float)$_POST['height'];
    $gender = $_POST['gender'];
    $goal = $_POST['goal'];

    if ($age <= 0 || $weight <= 0 || $height <= 0) {
        $error = "Age, weight, and height must be positive values.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, age, weight, height, gender, goal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $age, $weight, $height, $gender, $goal]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            header('Location: index.php?page=dashboard');
            exit;
        } catch(PDOException $e) {
            $error = "Email already exists!";
        }
    }
}

// Update Profile
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile']) && isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $weight = (float)$_POST['weight'];
    $height = (float)$_POST['height'];
    $gender = $_POST['gender'];
    $goal = $_POST['goal'];

    if ($age <= 0 || $weight <= 0 || $height <= 0) {
        $error = "Age, weight, and height must be positive values.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name=?, age=?, weight=?, height=?, gender=?, goal=? WHERE id=?");
        $stmt->execute([$name, $age, $weight, $height, $gender, $goal, $user_id]);
        $_SESSION['user_name'] = $name;
        $success = "Profile updated successfully!";
    }
}

// Add Food
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_food']) && isLoggedIn()) {
    $name = trim($_POST['name']);
    $calories = (int)$_POST['calories'];
    $protein = (float)$_POST['protein'];
    $carbs = (float)$_POST['carbs'];
    $fat = (float)$_POST['fat'];
    $meal_type = $_POST['meal_type'];
    
    $stmt = $pdo->prepare("INSERT INTO foods (name, calories, protein, carbs, fat, meal_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $calories, $protein, $carbs, $fat, $meal_type]);
    $success = "Food added successfully!";
}

// Save Meal Plan
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_meal']) && isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $day = $_POST['day'];
    $meal_type = $_POST['meal_type'];
    $food_id = $_POST["food_id"] ?? "";

    if ($food_id === "" || $food_id === "none") {
        $stmt = $pdo->prepare("DELETE FROM meal_plan WHERE user_id = ? AND day = ? AND meal_type = ?");
        $stmt->execute([$user_id, $day, $meal_type]);
        header("Location: index.php?page=planner&saved=1");
        exit;
    }

    $food_id = (int)$food_id;
    
    $stmt = $pdo->prepare("SELECT id FROM meal_plan WHERE user_id = ? AND day = ? AND meal_type = ?");
    $stmt->execute([$user_id, $day, $meal_type]);
    $existing = $stmt->fetch();
    
    if($existing) {
        $stmt = $pdo->prepare("UPDATE meal_plan SET food_id = ? WHERE user_id = ? AND day = ? AND meal_type = ?");
        $stmt->execute([$food_id, $user_id, $day, $meal_type]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO meal_plan (user_id, day, meal_type, food_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $day, $meal_type, $food_id]);
    }
    
    header('Location: index.php?page=planner&saved=1');
    exit;
}

// Save Progress
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_progress']) && isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $weight = (float)$_POST['weight'];
    $date = $_POST['date'];
    
    $stmt = $pdo->prepare("INSERT INTO progress (user_id, weight, date) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $weight, $date]);
    
    header('Location: index.php?page=progress&saved=1');
    exit;
}

// Getting user data
$user = null;
if(isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Page Routing
$page = $_GET['page'] ?? 'home';

// Handle logout
if($page == 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Meal Planner - <?php echo ucfirst($page); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        
        .animate {
            animation: fadeIn 0.5s ease-out;
        }
        
        
        .navbar {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: slideIn 0.5s ease-out;
        }
        
        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-brand span {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
       
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white;
            box-shadow: 0 2px 5px rgba(46,125,50,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46,125,50,0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
       
        .form-container {
            max-width: 750px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease-out;
        }
        
        .auth-home-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            margin-bottom: 26px;
            padding: 11px 18px 11px 14px;
            color: #1b5e20;
            background: linear-gradient(135deg, #f1f8e9, #e8f5e9);
            border: 1px solid #c8e6c9;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(46,125,50,0.14);
            transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, color 0.25s ease;
        }

        .auth-home-link::before {
            content: "\2190";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            color: white;
            background: #2e7d32;
            border-radius: 50%;
            font-size: 1rem;
            line-height: 1;
            box-shadow: inset 0 -2px 0 rgba(0,0,0,0.12);
        }

        .auth-home-link:hover,
        .auth-home-link:focus {
            color: white;
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            border-color: #2e7d32;
            box-shadow: 0 12px 28px rgba(46,125,50,0.28);
            transform: translateY(-2px);
            outline: none;
        }

        .auth-home-link:hover::before,
        .auth-home-link:focus::before {
            color: #2e7d32;
            background: white;
        }

        .form-container h2 {
            text-align: center;
            color: #2e7d32;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .form-container > p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46,125,50,0.1);
        }
        
        /* Register form 2 columns */
        .form-row-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 600px) {
            .form-row-2col {
                grid-template-columns: 1fr;
            }
        }
        
   
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .card h2 {
            color: #2e7d32;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .card h3 {
            color: #2e7d32;
            margin-bottom: 15px;
        }
        
     
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-card h3 {
            color: #666;
            margin-bottom: 15px;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: bold;
            color: #2e7d32;
        }
        
    
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
        }
        
        th {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white;
            padding: 14px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            animation: fadeIn 0.3s ease-out;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            animation: fadeIn 0.3s ease-out;
        }
        
        .hero {
            text-align: center;
            padding: 80px 20px;
            background: url(./Ethiopian-Food.jpg);
            background-size: cover;
            color: white;
            border-radius: 12px;
            margin-bottom: 60px;
            animation: fadeIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        .hero .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(27,94,32,0.82) 0%, rgba(0,0,0,0.55) 100%);
            border-radius: 12px;
        }
        
        .hero .content {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }
        
        .feature {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .feature h3 {
            color: #1b5e20;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .feature .dish-meta {
            margin-top: 14px;
            padding: 10px;
            background: #f1f1f1;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #2e7d32;
        }
        
        /* Image holder for Ancient Wisdom section */
        .img-placeholder {
            background-image: url('./foods.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 280px;
            width: 100%;
            display: block;
            border-radius: 12px;
}
        
        .planner-table {
            width: 100%;
            overflow-x: auto;
        }
        
        .planner-table table {
            min-width: 600px;
        }
        
        .meal-cell {
            min-width: 180px;
        }
        
        select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        
    
        footer {
            text-align: center;
            padding: 25px;
            margin-top: 60px;
            background: white;
            border-radius: 12px;
            color: #666;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .navbar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-container {
                margin: 30px 15px;
                padding: 25px;
            }
            
            .btn {
                padding: 10px 20px;
            }
        }
        
        @media (max-width: 480px) {
            .stat-value {
                font-size: 2rem;
            }
            
            .hero {
                padding: 50px 15px;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if(isLoggedIn()): ?>
        <nav class="navbar">
            <div class="nav-brand">
             <span>
                <a href="?page=home" style="text-decoration: none; color: white;">
                    Smart Meal Planner
                </a>
            </span>
            </div>
            <div class="nav-links">
                <a href="?page=dashboard">Dashboard</a>
                <a href="?page=profile">Profile</a>
                <a href="?page=foods"> Foods</a>
                <a href="?page=add_food">Add Food</a>
                <a href="?page=planner">Planner</a>
                <a href="?page=progress">Progress</a>
                <a href="?page=logout"> Logout</a>
            </div>
        </nav>
        <?php endif; ?>
        
        <?php
        if($page == 'home'):
        ?>

        <!-- HERO - removed "ROOTED IN ETHIOPIAN TRADITION" tag -->
        <div class="hero">
            <div class="overlay"></div>
            <div class="content">
                <h1>Smart Meal Planner</h1>
                <p>
                    Personalized nutrition rooted in the rich flavors of Ethiopian cuisine —<br>
                    plan your meals, track your health, and celebrate your culture.
                </p>
                <?php if(!isLoggedIn()): ?>
                    <a href="?page=login" class="btn" style="background:white;color:#2e7d32;margin:0 10px;font-weight:700;box-shadow:0 4px 15px rgba(0,0,0,0.2);">Sign In</a>
                    <a href="?page=register" class="btn btn-primary" style="margin:0 10px;border:2px solid rgba(255,255,255,0.6);">Get Started Free</a>
                <?php else: ?> 
                    <a href="?page=dashboard" class="btn" style="background:white;color:#2e7d32;font-weight:700;">Go to Dashboard →</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- WHY ETHIOPIAN FOOD - replaced teff/legumes/berbere/vegetable with image holder -->
        <div class="card" style="margin-bottom:35px;background:linear-gradient(135deg,#f1f8e9,#e8f5e9);">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;">
                <div>
                    <div style="color:#2e7d32;font-weight:700;font-size:0.85rem;letter-spacing:2px;margin-bottom:10px;">WHY ETHIOPIAN NUTRITION?</div>
                    <h2 style="font-size:2rem;color:#1b5e20;margin-bottom:18px;line-height:1.3;">Ancient Wisdom.<br>Modern Health.</h2>
                    <p style="color:#555;line-height:1.8;margin-bottom:16px;">
                        Ethiopian cuisine is one of the world's most nutritionally balanced food traditions. Built around <strong>injera</strong> — a fermented teff flatbread packed with iron, fiber, and calcium — and complemented by protein-rich legume stews, this 3,000-year-old food culture aligns naturally with modern nutrition science.
                    </p>
                    <p style="color:#555;line-height:1.8;">
                        Our planner maps beloved dishes like <strong>Doro Wat, Misir, Shiro,</strong> and <strong>Kitfo</strong> to your personal caloric and macronutrient goals — so you never have to choose between culture and health.
                    </p>
                </div>
                <div>
                    <!-- Image holder - removed teff, legumes, berbere, vegetables icons -->
                    <div class="img-placeholder" style="min-height:280px; background-image: url('./foods.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; width: 100%; display: block; border-radius: 12px;"></div>
                </div>
            </div>
        </div>

        <!-- FEATURED ETHIOPIAN DISHES - reduced curve, removed emojis and colors -->
        <div style="margin-bottom:40px;">
            <div style="text-align:center;margin-bottom:30px;">
                <h2 style="color:#1b5e20;font-size:1.9rem;">Featured Ethiopian Dishes</h2>
                <p style="color:#666;margin-top:8px;">Traditional meals mapped to your nutritional needs</p>
            </div>
            <div class="features-grid">
                <div class="feature">
                    <h3>Doro Wat</h3>
                    <p style="color:#555;font-size:0.95rem;line-height:1.7;">Ethiopia's iconic slow-cooked chicken stew simmered in rich berbere sauce with hard-boiled eggs. A staple of celebrations, packed with <strong>42g of protein</strong> per serving with injera.</p>
                    <div class="dish-meta">580 kcal &nbsp;|&nbsp; 42g protein &nbsp;|&nbsp; 48g carbs</div>
                </div>
                <div class="feature">
                    <h3>Misir Wat</h3>
                    <p style="color:#555;font-size:0.95rem;line-height:1.7;">Red lentils cooked down with onion, garlic, ginger, and berbere until deeply flavored. A beloved everyday dish and fasting food, high in fiber and plant protein — naturally <strong>vegan and nutritious</strong>.</p>
                    <div class="dish-meta">420 kcal &nbsp;|&nbsp; 16g protein &nbsp;|&nbsp; 70g carbs</div>
                </div>
                <div class="feature">
                    <h3>Shiro Wat</h3>
                    <p style="color:#555;font-size:0.95rem;line-height:1.7;">Smooth, savory chickpea flour stew seasoned with spiced butter and berbere. A beloved comfort food with <strong>high iron content</strong> and a creamy texture that pairs perfectly with injera.</p>
                    <div class="dish-meta">460 kcal &nbsp;|&nbsp; 18g protein &nbsp;|&nbsp; 72g carbs</div>
                </div>
                <div class="feature">
                    <h3>Kitfo</h3>
                    <p style="color:#555;font-size:0.95rem;line-height:1.7;">Ethiopia's celebrated minced beef seasoned with mitmita spice and niter kibbeh spiced butter. Rich in <strong>high-quality protein and iron</strong>, often served with ayib (fresh cheese) and gomen.</p>
                    <div class="dish-meta">610 kcal &nbsp;|&nbsp; 48g protein &nbsp;|&nbsp; 38g carbs</div>
                </div>
                <div class="feature">
                    <h3>Yetsom Beyaynetu</h3>
                    <p style="color:#555;font-size:0.95rem;line-height:1.7;">The fasting platter — a beautiful spread of multiple vegetable and legume dishes on a single injera. Low in fat, high in fiber and micronutrients. The <strong>ultimate balanced vegan meal</strong>.</p>
                    <div class="dish-meta">480 kcal &nbsp;|&nbsp; 18g protein &nbsp;|&nbsp; 68g carbs</div>
                </div>
                
            </div>
        </div>

        <!-- HOW IT WORKS -->
        <div class="card" style="margin-bottom:35px;text-align:center;">
            <h2 style="color:#1b5e20;margin-bottom:8px;">How It Works</h2>
            <p style="color:#666;margin-bottom:35px;">Your personalized Ethiopian nutrition journey in four steps</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:25px;">
                <div style="padding:20px;">
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#2e7d32,#1b5e20);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.4rem;color:white;font-weight:700;">1</div>
                    <h4 style="color:#2e7d32;margin-bottom:10px;">Create Your Profile</h4>
                    <p style="color:#666;font-size:0.92rem;line-height:1.7;">Enter your age, weight, height, and health goal. We calculate your BMI and daily caloric needs instantly.</p>
                </div>
                <div style="padding:20px;">
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#2e7d32,#1b5e20);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.4rem;color:white;font-weight:700;">2</div>
                    <h4 style="color:#2e7d32;margin-bottom:10px;">Browse Ethiopian Foods</h4>
                    <p style="color:#666;font-size:0.92rem;line-height:1.7;">Explore our database of authentic Ethiopian dishes — each with accurate calorie, protein, carb, and fat data.</p>
                </div>
                <div style="padding:20px;">
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#2e7d32,#1b5e20);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.4rem;color:white;font-weight:700;">3</div>
                    <h4 style="color:#2e7d32;margin-bottom:10px;">Plan Your Week</h4>
                    <p style="color:#666;font-size:0.92rem;line-height:1.7;">Use the weekly planner to assign breakfast, lunch, and dinner for every day — tailored to your calorie target.</p>
                </div>
                <div style="padding:20px;">
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#2e7d32,#1b5e20);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.4rem;color:white;font-weight:700;">4</div>
                    <h4 style="color:#2e7d32;margin-bottom:10px;">Track Your Progress</h4>
                    <p style="color:#666;font-size:0.92rem;line-height:1.7;">Log your weight regularly and watch your progress over time. Stay motivated with a clear history of your journey.</p>
                </div>
            </div>
            <?php if(!isLoggedIn()): ?>
            <div style="margin-top:30px;">
                <a href="?page=register" class="btn btn-primary" style="font-size:1.05rem;padding:14px 36px;">Start Your Journey →</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- NUTRITION FACTS BANNER -->
        <div style="background:linear-gradient(135deg,#1b5e20,#2e7d32);border-radius:12px;padding:40px;margin-bottom:35px;color:white;text-align:center;">
            <h2 style="font-size:1.7rem;margin-bottom:8px;">Did You Know?</h2>
            <p style="opacity:0.85;margin-bottom:30px;">Ethiopian food is among the healthiest traditional cuisines in the world</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:20px;">
                <div style="background:rgba(255,255,255,0.12);padding:22px;border-radius:14px;">
                    <div style="font-size:2.2rem;font-weight:800;">3×</div>
                    <div style="font-size:0.88rem;margin-top:6px;opacity:0.9;">more iron in teff than wheat flour</div>
                </div>
                <div style="background:rgba(255,255,255,0.12);padding:22px;border-radius:14px;">
                    <div style="font-size:2.2rem;font-weight:800;">40%</div>
                    <div style="font-size:0.88rem;margin-top:6px;opacity:0.9;">of Ethiopian meals are naturally vegan (fasting days)</div>
                </div>
                <div style="background:rgba(255,255,255,0.12);padding:22px;border-radius:14px;">
                    <div style="font-size:2.2rem;font-weight:800;">18g</div>
                    <div style="font-size:0.88rem;margin-top:6px;opacity:0.9;">protein in a single bowl of Shiro Wat</div>
                </div>
                <div style="background:rgba(255,255,255,0.12);padding:22px;border-radius:14px;">
                    <div style="font-size:2.2rem;font-weight:800;">12+</div>
                    <div style="font-size:0.88rem;margin-top:6px;opacity:0.9;">spices in Berbere — nature's anti-inflammatory blend</div>
                </div>
            </div>
        </div>
        
        <?php
        elseif($page == 'login'):
        ?>
        <div class="form-container">
            <a href="?page=home" class="auth-home-link">Back to Home</a>
            <h2>Welcome Back!</h2>
            <p>Sign in to continue your health journey</p>
            
            <?php if(isset($error)): ?>
                <div class="alert-error"><?php echo $error; ?></div>
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
                <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Sign In</button>
                <p style="text-align: center; margin-top: 20px;">
                    Don't have an account? <a href="?page=register" style="color: #2e7d32;">Register here</a>
                </p>
            </form>
        </div>
        
        <?php
        elseif($page == 'register'):
        ?>
        <div class="form-container">
            <a href="?page=home" class="auth-home-link">Back to Home</a>
            <h2>Create Account 🎉</h2>
            <p>Join us and start your health transformation</p>
            
            <?php if(isset($error)): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- 2 columns for register -->
                <div class="form-row-2col">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required placeholder="Abebe Bekele" value="<?php echo e($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="abebe@example.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Min 6 characters">
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" required placeholder="28" value="<?php echo e($_POST['age'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" required placeholder="72" value="<?php echo e($_POST['weight'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" step="0.1" name="height" required placeholder="175" value="<?php echo e($_POST['height'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="">Select gender</option>
                            <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="prefer_not" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'prefer_not') ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Health Goal</label>
                        <select name="goal" required>
                            <option value="">Select your goal</option>
                            <option value="lose_weight" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'lose_weight') ? 'selected' : ''; ?>>Lose Weight</option>
                            <option value="maintain" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'maintain') ? 'selected' : ''; ?>>Maintain Weight</option>
                            <option value="gain_muscle" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'gain_muscle') ? 'selected' : ''; ?>>Gain Muscle</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="register" class="btn btn-primary" style="width: 100%;">Create Account</button>
                <p style="text-align: center; margin-top: 20px;">
                    Already have an account? <a href="?page=login" style="color: #2e7d32;">Login here</a>
                </p>
            </form>
        </div>
        
        <?php
        elseif($page == 'dashboard' && isLoggedIn()):
            $bmi = getBMI($user['weight'], $user['height']);
            $bmiCategory = getBMICategory($bmi);
            $bmiColor = getBMIColor($bmi);
            $calories = getCalories($user);

       
            $stmt = $pdo->prepare("SELECT mp.day, mp.meal_type, f.name, f.calories FROM meal_plan mp JOIN foods f ON mp.food_id = f.id WHERE mp.user_id = ? ORDER BY FIELD(mp.day,'mon','tue','wed','thu','fri','sat','sun'), mp.meal_type");
            $stmt->execute([$_SESSION['user_id']]);
            $dashboardMeals = $stmt->fetchAll();
            $dashboardMealsByDay = [];
            foreach($dashboardMeals as $dashMeal) {
                $dashboardMealsByDay[$dashMeal['day']][$dashMeal['meal_type']] = $dashMeal;
            }
            $dayNames = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
        ?>
        <div class="card animate" style="background: linear-gradient(135deg, #2e7d32, #1b5e20); color: white; text-align: center;">
            <h2 style="color: white;">Welcome back, <?php echo e($user['name']); ?>! </h2>
            <p>Here's your health summary for today</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>BMI Index</h3>
                <div class="stat-value" style="color: <?php echo $bmiColor; ?>;"><?php echo $bmi ?: '—'; ?></div>
                <p style="margin-top: 10px; font-weight: 500;"><?php echo e($bmiCategory); ?></p>
            </div>
            <div class="stat-card">
                <h3>Daily Calories</h3>
                <div class="stat-value"><?php echo number_format($calories); ?></div>
                <p style="margin-top: 10px;">kcal per day</p>
            </div>
            <div class="stat-card">
                <h3>Current Weight</h3>
                <div class="stat-value"><?php echo e($user['weight']); ?></div>
                <p style="margin-top: 10px;">kilograms</p>
            </div>
        </div>

        <div class="card">
            <h3>Selected Planner Foods</h3>
            <?php if(count($dashboardMeals) > 0): ?>
                <div class="planner-table" style="overflow-x: auto; margin-top: 10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Breakfast</th>
                                <th>Lunch</th>
                                <th>Dinner</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($dayNames as $short => $label): ?>
                                <tr>
                                    <td style="background: #f5f5f5; font-weight: bold; text-align: center;"><?php echo $label; ?></td>
                                    <td><?php echo isset($dashboardMealsByDay[$short]['breakfast']) ? e($dashboardMealsByDay[$short]['breakfast']['name']) : '&mdash;'; ?></td>
                                    <td><?php echo isset($dashboardMealsByDay[$short]['lunch']) ? e($dashboardMealsByDay[$short]['lunch']['name']) : '&mdash;'; ?></td>
                                    <td><?php echo isset($dashboardMealsByDay[$short]['dinner']) ? e($dashboardMealsByDay[$short]['dinner']['name']) : '&mdash;'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #666; margin-top: 10px;">No planner foods selected yet. Select meals on the Planner page to see them here.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
                <a href="?page=planner" class="btn btn-primary">Plan Your Meals</a>
                <a href="?page=progress" class="btn btn-secondary">Track Progress</a>
                <a href="?page=profile" class="btn btn-secondary">Update Profile</a>
                <a href="?page=foods" class="btn btn-secondary">View Foods</a>
            </div>
        </div>
        
        <?php
        elseif($page == 'profile' && isLoggedIn()):
            $bmi = getBMI($user['weight'], $user['height']);
        ?>
        <div class="card animate">
            <h2>My Profile</h2>
            
            <?php if(isset($success)): ?>
                <div class="alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <div>
                    <h3>Personal Information</h3>
                    <div style="margin-top: 15px;">
                        <p><strong>Name:</strong> <?php echo e($user['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo e($user['email']); ?></p>
                        <p><strong>Age:</strong> <?php echo e($user['age']); ?> years</p>
                        <p><strong>Gender:</strong> <?php echo ucfirst(e($user['gender'])); ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                <div>
                    <h3>Health Metrics</h3>
                    <div style="margin-top: 15px;">
                        <p><strong>Weight:</strong> <?php echo e($user['weight']); ?> kg</p>
                        <p><strong>Height:</strong> <?php echo e($user['height']); ?> cm</p>
                        <p><strong>BMI:</strong> <?php echo $bmi ?: '—'; ?></p>
                        <p><strong>Goal:</strong> 
                            <?php 
                            $goalLabels = ['lose_weight' => 'Lose Weight', 'maintain' => ' Maintain Weight', 'gain_muscle' => 'Gain Muscle'];
                            echo $goalLabels[$user['goal']] ?? ucfirst(e($user['goal']));
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>Update Profile</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo e($_POST['name'] ?? $user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" value="<?php echo e($_POST['age'] ?? $user['age']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" value="<?php echo e($_POST['weight'] ?? $user['weight']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="number" step="0.1" name="height" value="<?php echo e($_POST['height'] ?? $user['height']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="male" <?php echo (isset($_POST['gender']) ? $_POST['gender'] == 'male' : $user['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (isset($_POST['gender']) ? $_POST['gender'] == 'female' : $user['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="prefer_not" <?php echo (isset($_POST['gender']) ? $_POST['gender'] == 'prefer_not' : $user['gender'] == 'prefer_not') ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Health Goal</label>
                        <select name="goal">
                            <option value="lose_weight" <?php echo (isset($_POST['goal']) ? $_POST['goal'] == 'lose_weight' : $user['goal'] == 'lose_weight') ? 'selected' : ''; ?>>Lose Weight</option>
                            <option value="maintain" <?php echo (isset($_POST['goal']) ? $_POST['goal'] == 'maintain' : $user['goal'] == 'maintain') ? 'selected' : ''; ?>>Maintain Weight</option>
                            <option value="gain_muscle" <?php echo (isset($_POST['goal']) ? $_POST['goal'] == 'gain_muscle' : $user['goal'] == 'gain_muscle') ? 'selected' : ''; ?>>Gain Muscle</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        
        <?php
        elseif($page == 'foods' && isLoggedIn()):
            $stmt = $pdo->query("SELECT * FROM foods ORDER BY meal_type, name");
            $foods = $stmt->fetchAll();
        ?>
        <div class="card animate">
            <h2> Ethiopian Food Database</h2>
            <p style="color:#666;margin-bottom:20px;">Authentic Ethiopian dishes with accurate nutritional data — from Doro Wat to Kolo.</p>
            <a href="?page=add_food" class="btn btn-primary" style="margin-bottom: 20px;">+ Add New Food</a>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Calories</th>
                            <th>Protein</th>
                            <th>Carbs</th>
                            <th>Fat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($foods as $food): ?>
                        <tr>
                            <td><strong><?php echo e($food['name']); ?></strong></td>
                            <td><span style="background: #e8f5e9; padding: 4px 8px; border-radius: 20px; font-size: 0.85rem;"><?php echo ucfirst(e($food['meal_type'])); ?></span></td>
                            <td><?php echo $food['calories']; ?> cal</td>
                            <td><?php echo $food['protein']; ?> g</td>
                            <td><?php echo $food['carbs']; ?> g</td>
                            <td><?php echo $food['fat']; ?> g</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php
        elseif($page == 'add_food' && isLoggedIn()):
        ?>
        <div class="form-container">
            <h2>Add New Food Item</h2>
            <p>Enter the nutritional information</p>
            
            <?php if(isset($success)): ?>
                <div class="alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Food Name</label>
                    <input type="text" name="name" required placeholder="e.g., Grilled Chicken">
                </div>
                <div class="form-group">
                    <label>Calories</label>
                    <input type="number" name="calories" required placeholder="Calories">
                </div>
                <div class="form-group">
                    <label>Protein (g)</label>
                    <input type="number" step="0.1" name="protein" required placeholder="Protein">
                </div>
                <div class="form-group">
                    <label>Carbohydrates (g)</label>
                    <input type="number" step="0.1" name="carbs" required placeholder="Carbs">
                </div>
                <div class="form-group">
                    <label>Fat (g)</label>
                    <input type="number" step="0.1" name="fat" required placeholder="Fat">
                </div>
                <div class="form-group">
                    <label>Meal Type</label>
                    <select name="meal_type" required>
                        <option value="">Select type</option>
                        <option value="breakfast">Breakfast</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                        <option value="snack">Snack</option>
                    </select>
                </div>
                <button type="submit" name="add_food" class="btn btn-primary" style="width: 100%;">Add Food</button>
                <a href="?page=foods" class="btn btn-secondary" style="width: 100%; margin-top: 10px; text-align: center;">View All Foods</a>
            </form>
        </div>
        
        <?php
        elseif($page == 'planner' && isLoggedIn()):
            $stmt = $pdo->query("SELECT * FROM foods ORDER BY name");
            $all_foods = $stmt->fetchAll();
            
            $foods_by_type = [];
            foreach($all_foods as $food) {
                $foods_by_type[$food['meal_type']][] = $food;
            }
            
            $stmt = $pdo->prepare("SELECT mp.*, f.name, f.calories FROM meal_plan mp JOIN foods f ON mp.food_id = f.id WHERE mp.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $plans = $stmt->fetchAll();
            
            $plan_by_day = [];
            foreach($plans as $plan) {
                $plan_by_day[$plan['day']][$plan['meal_type']] = $plan;
            }
            
            $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            $day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $meal_types = ['breakfast', 'lunch', 'dinner'];
            $meal_icons = ['breakfast' => '🍳', 'lunch' => '🥗', 'dinner' => '🍽️'];
            $meal_labels = ['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'];
            
            if(isset($_GET['saved'])) {
                echo '<div class="alert-success">Meal plan saved successfully!</div>';
            }
        ?>
        <div class="card animate">
            <h2>Weekly Meal Planner</h2>
            <p>Plan your meals for the week ahead</p>
            
            <div class="planner-table" style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Breakfast</th>
                            <th>Lunch</th>
                            <th>Dinner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($days as $idx => $day): ?>
                        <tr>
                            <td style="background: #f5f5f5; font-weight: bold; text-align: center;"><?php echo $day_names[$idx]; ?></td>
                            <?php foreach($meal_types as $meal_type): ?>
                            <td class="meal-cell">
                                <?php if(isset($plan_by_day[$day][$meal_type])): ?>
                                    <div style="background: #e8f5e9; padding: 8px; border-radius: 8px;">
                                        <strong><?php echo e($plan_by_day[$day][$meal_type]['name']); ?></strong><br>
                                        <small><?php echo $plan_by_day[$day][$meal_type]['calories']; ?> calories</small>
                                        <form method="POST" style="margin-top: 8px;">
                                            <input type="hidden" name="save_meal" value="1">
                                            <input type="hidden" name="day" value="<?php echo $day; ?>">
                                            <input type="hidden" name="meal_type" value="<?php echo $meal_type; ?>">
                                            <select name="food_id" onchange="this.form.submit()" style="font-size: 0.85rem;">
                                                <option value="" selected disabled>Change meal...</option>
                                                <option value="none">None - remove meal</option>
                                                <?php foreach($foods_by_type[$meal_type] ?? [] as $food): ?>
                                                    <option value="<?php echo $food['id']; ?>"><?php echo e($food['name']); ?> (<?php echo $food['calories']; ?> cal)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="save_meal" value="1">
                                        <input type="hidden" name="day" value="<?php echo $day; ?>">
                                        <input type="hidden" name="meal_type" value="<?php echo $meal_type; ?>">
                                        <select name="food_id" required onchange="this.form.submit()">
                                            <option value="">Select <?php echo $meal_icons[$meal_type]; ?> <?php echo $meal_labels[$meal_type]; ?></option>
                                            <?php foreach($foods_by_type[$meal_type] ?? [] as $food): ?>
                                                <option value="<?php echo $food['id']; ?>"><?php echo e($food['name']); ?> (<?php echo $food['calories']; ?> cal)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
</div>
        
        <?php
        elseif($page == 'progress' && isLoggedIn()):
            $stmt = $pdo->prepare("SELECT * FROM progress WHERE user_id = ? ORDER BY date DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $progress = $stmt->fetchAll();
            
            if(isset($_GET['saved'])) {
                echo '<div class="alert-success">Progress saved successfully!</div>';
            }
        ?>
        <div class="card animate">
            <h2>Weight Progress Tracking 📈</h2>
            
            <form method="POST">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" required placeholder="Enter your weight">
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button type="submit" name="save_progress" class="btn btn-primary">Save Progress</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Progress History</h3>
            <?php if(count($progress) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight (kg)</th>
                                <th>Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $prevWeight = null;
                            foreach($progress as $index => $record): 
                                $change = $prevWeight ? ($record['weight'] - $prevWeight) : 0;
                                $changeClass = $change < 0 ? 'text-success' : ($change > 0 ? 'text-danger' : '');
                            ?>
                            <tr>
                                <td><?php echo date('F j, Y', strtotime($record['date'])); ?></td>
                                <td><strong><?php echo $record['weight']; ?> kg</strong></td>
                                <td>
                                    <?php if($index > 0): ?>
                                        <?php if($change < 0): ?>
                                            <span style="color: #28a745;">▼ <?php echo abs($change); ?> kg</span>
                                        <?php elseif($change > 0): ?>
                                            <span style="color: #dc3545;">▲ <?php echo $change; ?> kg</span>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">— no change</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">starting point</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                $prevWeight = $record['weight'];
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No progress records yet. Start tracking your weight today!</p>
            <?php endif; ?>
        </div>
        
        <?php
        else:
        ?>
        <div class="card animate" style="text-align: center;">
            <h2>404 - Page Not Found</h2>
            <p style="margin: 20px 0;">The page you're looking for doesn't exist.</p>
            <a href="?page=home" class="btn btn-primary">Go to Home</a>
        </div>
        
        <?php endif; ?>
        
        <footer style="padding:30px;">
            <div style="font-size:1.5rem;margin-bottom:8px;"></div>
            <p style="font-weight:600;color:#2e7d32;margin-bottom:6px;">Smart Meal Planner — Ethiopian Nutrition Edition</p>
            <p style="color:#888;font-size:0.9rem;">Celebrating the richness of Ethiopian food culture through personalized health. &nbsp;|&nbsp; © <?php echo date('Y'); ?></p>
            <p style="color:#aaa;font-size:0.82rem;margin-top:8px;font-style:italic;">"ጤናማ ምግብ ጤናማ ሕይወት" — Healthy food, healthy life</p>
        </footer>
    </div>
</body>
</html>