<?php
include('../config/db.php');

$sql = "SELECT * FROM foods";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Food List</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">

    <h2>Food List</h2>

    <table>

        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Calories</th>
            <th>Protein</th>
            <th>Carbs</th>
            <th>Fat</th>
            <th>Type</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($result)){ ?>

        <tr>

            <td><?php echo $row['id']; ?></td>

            <td><?php echo $row['name']; ?></td>

            <td><?php echo $row['calories']; ?></td>

            <td><?php echo $row['protein']; ?> g</td>

            <td><?php echo $row['carbs']; ?> g</td>

            <td><?php echo $row['fat']; ?> g</td>

            <td><?php echo $row['type']; ?></td>

        </tr>

        <?php } ?>

    </table>

</div>

</body>
</html>