<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

auth_require_login();
$user_id = auth_user_id();

$message = '';
$error = '';

// Get user data for the form
$user = auth_get_user($pdo, $user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['weight'])) {
    $weight = (float)$_POST['weight'];
    $date = $_POST['date'] ?? date('Y-m-d');
    
    // Validate
    if($weight <= 0 || $weight > 600) {
        $error = 'Please enter a valid weight (1-600 kg)';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO progress (user_id, weight, date) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $weight, $date]);
            $message = "Progress saved successfully!";
            
            // Update user's current weight
            $stmt = $pdo->prepare("UPDATE users SET weight = ? WHERE id = ?");
            $stmt->execute([$weight, $user_id]);
            
        } catch(PDOException $e) {
            $error = "Error saving progress: " . $e->getMessage();
        }
    }
}

// Get progress history
$stmt = $pdo->prepare("SELECT * FROM progress WHERE user_id = ? ORDER BY date DESC");
$stmt->execute([$user_id]);
$progress = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="progress-container">
    <h2>Weight Progress Tracking</h2>
    
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo e($message); ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo e($error); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <h3>Record Your Weight</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" step="0.1" name="weight" required 
                           placeholder="Enter your weight" min="1" max="600">
                </div>
                
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Progress</button>
        </form>
    </div>
    
    <div class="card">
        <h3>Progress History</h3>
        <?php if(count($progress) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Weight (kg)</th>
                            <th>Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $prevWeight = null;
                        foreach($progress as $index => $record): 
                            $change = $prevWeight ? ($record['weight'] - $prevWeight) : 0;
                        ?>
                        <tr>
                            <td><?php echo date('F j, Y', strtotime($record['date'])); ?></td>
                            <td><strong><?php echo $record['weight']; ?> kg</strong></td>
                            <td>
                                <?php if($index > 0): ?>
                                    <?php if($change < 0): ?>
                                        <span style="color: #28a745;">▼ <?php echo abs($change); ?> kg</span>
                                    <?php elseif($change > 0): ?>
                                        <span style="color: #dc3545;">▲ <?php echo $change; ?> kg</span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">— no change</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #6c757d;">starting point</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            $prevWeight = $record['weight'];
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #666; padding: 40px;">No progress records yet. Start tracking your weight today!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>