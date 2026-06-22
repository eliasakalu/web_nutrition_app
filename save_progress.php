<?php
require_once 'config/db.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // temp for testing
}

$user_id = $_SESSION['user_id'];
$weight = $_POST['weight'];
$date = $_POST['date'];

$conn = mysqli_connect("localhost", "root", "", "smart_meal_planner");
$stmt = $conn->prepare("INSERT INTO progress (user_id, weight, date) VALUES (?, ?, ?)");
$stmt->bind_param("ids", $user_id, $weight, $date);
$stmt->execute();

header("Location: progress.php");
?>