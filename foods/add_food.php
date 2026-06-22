<?php
require_once '../config/db.php';
require_once '../includes/auth_functions.php';

auth_require_login();

$message = '';
if(isset($_POST['add_food'])){
    $name = $_POST['name'];
    $calories = $_POST['calories'];
    $protein = $_POST['protein'];
    $carbs = $_POST['carbs'];
    $fat = $_POST['fat'];
    $type = $_POST['type'];
    
    $sql = "INSERT INTO foods(name, calories, protein, carbs, fat, meal_type) VALUES('$name','$calories','$protein','$carbs','$fat','$type')";
    $result = mysqli_query($conn, $sql);
    
    if($result){
        $message = "Food Added Successfully!";
    } else {
        $message = "Error Adding Food";
    }
}
include '../includes/header.php';
?>

<div class="food-container">
    <div class="add-food-form">
        <h2>Add New Food Item</h2>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Food Name</label>
                <input type="text" name="name" required placeholder="e.g., Grilled Chicken">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Calories</label>
                    <input type="number" name="calories" required placeholder="Calories">
                </div>
                
                <div class="form-group">
                    <label>Protein (g)</label>
                    <input type="number" step="0.1" name="protein" required placeholder="Protein">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Carbohydrates (g)</label>
                    <input type="number" step="0.1" name="carbs" required placeholder="Carbs">
                </div>
                
                <div class="form-group">
                    <label>Fat (g)</label>
                    <input type="number" step="0.1" name="fat" required placeholder="Fat">
                </div>
            </div>
            
            <div class="form-group">
                <label>Meal Type</label>
                <select name="type" required>
                    <option value="">Select Type</option>
                    <option value="breakfast">Breakfast</option>
                    <option value="lunch">Lunch</option>
                    <option value="dinner">Dinner</option>
                    <option value="snack">Snack</option>
                </select>
            </div>
            
            <button type="submit" name="add_food" class="btn btn-primary">Add Food</button>
            <a href="view_foods.php" class="btn btn-secondary">View All Foods</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>