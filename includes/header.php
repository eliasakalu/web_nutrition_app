<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Meal Planner</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <?php if(isset($_SESSION['user_id'])): ?>
        <nav class="navbar">
            <div class="nav-brand">🍽️ Smart Meal Planner</div>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">Profile</a>
                <a href="foods/view_foods.php">Foods</a>
                <a href="foods/add_food.php">Add Food</a>
                <a href="planner.php">Planner</a>
                <a href="progress.php">Progress</a>
                <a href="logout.php">Logout</a>
            </div>
        </nav>
        <?php endif; ?>
        <main>