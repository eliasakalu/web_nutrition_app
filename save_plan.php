<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

auth_require_login();
$user_id = auth_user_id();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $day = $_POST['day'];
    $meal_type = $_POST['meal_type'];
    $food_id = $_POST["food_id"] ?? "";

    if ($food_id === "" || $food_id === "none") {
        $stmt = $pdo->prepare("DELETE FROM meal_plan WHERE user_id = ? AND day = ? AND meal_type = ?");
        $stmt->execute([$user_id, $day, $meal_type]);
        echo json_encode(["success" => true]);
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
    
    echo json_encode(['success' => true]);
}
?>