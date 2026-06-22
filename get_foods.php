<?php
require_once 'config/db.php';  // <-- ADD THIS LINE

$type = $_GET['type'] ?? 'breakfast';
$stmt = $pdo->prepare("SELECT * FROM foods WHERE meal_type = ? ORDER BY name");
$stmt->execute([$type]);
$foods = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($foods);
?>