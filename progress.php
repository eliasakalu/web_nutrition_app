<?php
session_start(); // Start session to access user_id


include __DIR__ . "/db.php";

if (!isset($_SESSION["user_id"])) {
    $_SESSION["user_id"] = 1; // temporary for testing
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT * FROM progress WHERE user_id = ? ORDER BY date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Progress Tracking</title>
</head>
<body>

<h2>Weight Progress Tracking</h2>

<form method="POST" action="save_progress.php">
    <label>Weight:</label>
    <input type="number" step="0.01" name="weight" required>

    <label>Date:</label>
    <input type="date" name="date" required>

    <button type="submit">Save Progress</button>
</form>

<h3>Progress History</h3>

<table border="1" cellpadding="10">
    <tr>
        <th>Date</th>
        <th>Weight</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row["date"]; ?></td>
            <td><?php echo $row["weight"]; ?> kg</td>
        </tr>
    <?php endwhile; ?>
</table>

<br>
<a href="weekly_planner.php">Back to Weekly Planner</a>

</body>
</html>