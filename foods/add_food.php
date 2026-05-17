<?php
include('../config/db.php');

if(isset($_POST['add_food'])){

    $name = $_POST['name'];
    $calories = $_POST['calories'];
    $protein = $_POST['protein'];
    $carbs = $_POST['carbs'];
    $fat = $_POST['fat'];
    $type = $_POST['type'];

    $sql = "INSERT INTO foods(name, calories, protein, carbs, fat, type)
            VALUES('$name','$calories','$protein','$carbs','$fat','$type')";

    $result = mysqli_query($conn, $sql);

    if($result){
        $message = "Food Added Successfully";
    }else{
        $message = "Error Adding Food";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Food</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="container">

    <h2>Add Food Item</h2>

    <?php
    if(isset($message)){
        echo "<p class='success'>$message</p>";
    }
    ?>

    <form method="POST">

        <label>Food Name</label>
        <input type="text" name="name" required>

        <label>Calories</label>
        <input type="number" name="calories" required>

        <label>Protein (g)</label>
        <input type="number" step="0.1" name="protein" required>

        <label>Carbohydrates (g)</label>
        <input type="number" step="0.1" name="carbs" required>

        <label>Fat (g)</label>
        <input type="number" step="0.1" name="fat" required>

        <label>Food Type</label>

        <select name="type" required>

            <option value="">Select Type</option>

            <option value="Breakfast">Breakfast</option>

            <option value="Lunch">Lunch</option>

            <option value="Dinner">Dinner</option>

            <option value="Snack">Snack</option>

        </select>

        <button type="submit" name="add_food">
            Add Food
        </button>

    </form>

</div>

</body>
</html>