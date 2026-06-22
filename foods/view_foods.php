<?php
require_once '../config/db.php';
require_once '../includes/auth_functions.php';

auth_require_login();

$sql = "SELECT * FROM foods ORDER BY meal_type, name";
$result = mysqli_query($conn, $sql);
include '../includes/header.php';
?>

<div class="food-container">
    <div class="food-list">
        <h2>Food Database</h2>
        <p>Browse through our collection of healthy foods</p>
        
        <a href="add_food.php" class="btn btn-primary" style="margin-bottom: 20px;">+ Add New Food</a>
        
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
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><strong><?php echo $row['name']; ?></strong></td>
                    <td><?php echo ucfirst($row['meal_type']); ?></td>
                    <td><?php echo $row['calories']; ?> cal</td>
                    <td><?php echo $row['protein']; ?> g</td>
                    <td><?php echo $row['carbs']; ?> g</td>
                    <td><?php echo $row['fat']; ?> g</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>