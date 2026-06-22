<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

auth_require_login();
$user_id = auth_user_id();

$foods_stmt = $pdo->query("SELECT * FROM foods ORDER BY name");
$foods = $foods_stmt->fetchAll();

$foods_by_type = [];
foreach($foods as $food) {
    $foods_by_type[$food['meal_type']][] = $food;
}

$stmt = $pdo->prepare("SELECT mp.*, f.name, f.calories FROM meal_plan mp JOIN foods f ON mp.food_id = f.id WHERE mp.user_id = ?");
$stmt->execute([$user_id]);
$plans = $stmt->fetchAll();

$plan_by_day = [];
foreach($plans as $plan) {
    $plan_by_day[$plan['day']][$plan['meal_type']] = $plan;
}

$days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
$day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$meal_types = ['breakfast', 'lunch', 'dinner'];
$meal_labels = ['breakfast' => '🍳 Breakfast', 'lunch' => '🥗 Lunch', 'dinner' => '🍽️ Dinner'];

include 'includes/header.php';
?>

<div class="planner-container">
    <h2>Weekly Meal Planner</h2>
    <p>Plan your meals for the week ahead</p>
    
    <?php if(isset($_GET['saved'])): ?>
        <div class="alert alert-success">Meal plan saved successfully!</div>
    <?php endif; ?>
    
    <table class="planner-table">
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
                <td class="day-cell"><strong><?php echo $day_names[$idx]; ?></strong></td>
                <?php foreach($meal_types as $meal_type): ?>
                <td class="meal-cell">
                    <?php if(isset($plan_by_day[$day][$meal_type])): ?>
                        <div class="planned-meal">
                            <strong><?php echo e($plan_by_day[$day][$meal_type]['name']); ?></strong>
                            <span class="calories">(<?php echo $plan_by_day[$day][$meal_type]['calories']; ?> cal)</span>
                            <button class="edit-meal" data-day="<?php echo $day; ?>" data-meal="<?php echo $meal_type; ?>">✏️ Edit</button>
                        </div>
                    <?php else: ?>
                        <button class="add-meal" data-day="<?php echo $day; ?>" data-meal="<?php echo $meal_type; ?>">+ Add <?php echo $meal_labels[$meal_type]; ?></button>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="mealModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Select a meal</h3>
        <div id="mealOptions"></div>
    </div>
</div>

<script>
const modal = document.getElementById('mealModal');
const closeBtn = document.querySelector('.close');
let currentDay = null;
let currentMeal = null;

function openModal(day, meal) {
    currentDay = day;
    currentMeal = meal;
    
    fetch(`get_foods.php?type=${meal}`)
        .then(response => response.json())
        .then(foods => {
            const optionsDiv = document.getElementById('mealOptions');
            optionsDiv.innerHTML = '<h4>Choose a food:</h4>';
            foods.forEach(food => {
                const div = document.createElement('div');
                div.className = 'food-option';
                div.innerHTML = `<strong>${food.name}</strong> - ${food.calories} cal | Protein: ${food.protein}g | Carbs: ${food.carbs}g | Fat: ${food.fat}g`;
                div.onclick = () => saveMeal(currentDay, currentMeal, food.id);
                optionsDiv.appendChild(div);
            });
        });
    
    modal.style.display = 'block';
}

function saveMeal(day, meal, foodId) {
    fetch('save_plan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `day=${day}&meal_type=${meal}&food_id=${foodId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        }
    });
}

document.querySelectorAll('.add-meal, .edit-meal').forEach(btn => {
    btn.onclick = () => openModal(btn.dataset.day, btn.dataset.meal);
});

closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if(e.target == modal) modal.style.display = 'none'; };
</script>

<?php include 'includes/footer.php'; ?>