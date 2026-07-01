<?php
// goals.php - Goals Management Page
session_start();
require_once 'conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Get user settings for theme
$settings_sql = "SELECT color_scheme FROM user_settings WHERE user_id = $user_id";
$settings_result = $conn->query($settings_sql);
$settings = $settings_result->num_rows > 0 ? $settings_result->fetch_assoc() : null;
$current_theme = $settings['color_scheme'] ?? 'light';

// Get user's current goals
$goals_sql = "SELECT * FROM user_goals WHERE user_id = $user_id";
$goals_result = $conn->query($goals_sql);
$current_goals = $goals_result->num_rows > 0 ? $goals_result->fetch_assoc() : null;

// Process goal update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_goals'])) {
    $daily_steps = $_POST['daily_steps'];
    $daily_calories = $_POST['daily_calories'];
    $daily_exercise = $_POST['daily_exercise'];
    $weight_goal = $_POST['weight_goal'];
    
    if ($current_goals) {
        $update_sql = "UPDATE user_goals SET 
            daily_steps_goal = $daily_steps,
            daily_calorie_goal = $daily_calories,
            daily_exercise_minutes = $daily_exercise,
            weight_goal = " . ($weight_goal ? "'$weight_goal'" : "NULL") . ",
            updated_at = NOW()
            WHERE user_id = $user_id";
    } else {
        $update_sql = "INSERT INTO user_goals (user_id, daily_steps_goal, daily_calorie_goal, daily_exercise_minutes, weight_goal) 
                      VALUES ($user_id, $daily_steps, $daily_calories, $daily_exercise, " . 
                      ($weight_goal ? "'$weight_goal'" : "NULL") . ")";
    }
    
    if ($conn->query($update_sql)) {
        $message = "Goals updated successfully!";
        $message_type = "success";
        // Refresh goals
        $goals_result = $conn->query($goals_sql);
        $current_goals = $goals_result->fetch_assoc();
    } else {
        $message = "Error updating goals: " . $conn->error;
        $message_type = "error";
    }
}

// Get goal progress
$today = date('Y-m-d');
$progress_sql = "SELECT 
    (SELECT step_count FROM steps WHERE user_id = $user_id AND step_date = '$today') as today_steps,
    (SELECT SUM(calories) FROM food_entries WHERE user_id = $user_id AND entry_date = '$today') as today_calories,
    (SELECT SUM(duration_minutes) FROM activities WHERE user_id = $user_id AND activity_date = '$today') as today_exercise";
$progress_result = $conn->query($progress_sql);
$progress = $progress_result->fetch_assoc();

// Calculate percentages
$steps_goal = $current_goals['daily_steps_goal'] ?? 10000;
$calories_goal = $current_goals['daily_calorie_goal'] ?? 2000;
$exercise_goal = $current_goals['daily_exercise_minutes'] ?? 30;

$steps_percentage = $steps_goal > 0 ? min(100, (($progress['today_steps'] ?? 0) / $steps_goal) * 100) : 0;
$calories_percentage = $calories_goal > 0 ? min(100, (($progress['today_calories'] ?? 0) / $calories_goal) * 100) : 0;
$exercise_percentage = $exercise_goal > 0 ? min(100, (($progress['today_exercise'] ?? 0) / $exercise_goal) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack Pro - Goals</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
             background: linear-gradient(135deg, #e1e5f5ff 0%, #eaa9faff 100%);
            min-height: 100vh;
        }
        
        .theme-dark body {
            background: linear-gradient(135deg, #2e1a1a 0%, #3b0d0d 100%);
            color: white;
        }
        
        .theme-high-contrast body {
            background: linear-gradient(135deg, #330000 0%, #1a0000 100%);
            color: white;
        }
        
        /* Goals-specific colors */
        .stat-value.goals {
            color: #ff9800;
        }
        
        .btn.goals {
            background: linear-gradient(90deg, #ff9800, #ff5722);
        }
        
        .btn.goals:hover {
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #ff9800, #ff5722);
        }
        
        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .theme-dark .navbar,
        .theme-high-contrast .navbar {
            background: rgba(0, 0, 0, 0.9);
        }
        
        .logo h1 {
            background: linear-gradient(90deg, #ff9800, #ff5722);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.8rem;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav-link {
            text-decoration: none;
            color: #ff9800;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .theme-dark .nav-link,
        .theme-high-contrast .nav-link {
            color: white;
        }
        
        .nav-link:hover {
            background: #ff9800;
            color: white;
        }
        
        .container {
            max-width: 80%;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h2 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 10px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .theme-dark .card,
        .theme-high-contrast .card {
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }
        
        .card h3 {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .theme-dark .card h3,
        .theme-high-contrast .card h3 {
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .theme-dark .form-group label,
        .theme-high-contrast .form-group label {
            color: #ccc;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .theme-dark .form-group input,
        .theme-high-contrast .form-group input {
            background: #222;
            color: white;
            border-color: #555;
        }
        
        .btn {
            background: linear-gradient(90deg, #ff9800, #ff5722);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .success {
            background: #4CAF50;
            color: white;
        }
        
        .error {
            background: #ff416c;
            color: white;
        }
        
        /* Progress Cards */
        .progress-card {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.95);
            margin-bottom: 20px;
        }
        
        .theme-dark .progress-card,
        .theme-high-contrast .progress-card {
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }
        
        .progress-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .progress-bar {
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            width: 0%;
            transition: width 1s ease;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #666;
        }
        
        .theme-dark .progress-text,
        .theme-high-contrast .progress-text {
            color: #ccc;
        }
        
        .goal-status {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(255, 152, 0, 0.1);
        }
        
        .current-goals {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .theme-dark .current-goals,
        .theme-high-contrast .current-goals {
            border-color: #444;
        }
        
        .goal-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .theme-dark .goal-item,
        .theme-high-contrast .goal-item {
            border-color: #444;
        }
        
        .goal-item:last-child {
            border-bottom: none;
        }
        
        .goal-label {
            font-weight: 600;
        }
        
        .goal-value {
            color: #ff9800;
            font-weight: bold;
        }
        
        .theme-dark .goal-value,
        .theme-high-contrast .goal-value {
            color: white;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.getAttribute('data-width');
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width + '%';
                }, 300);
            });
            
            // Set default goal values
            const defaultGoals = {
                'daily_steps': 10000,
                'daily_calories': 2000,
                'daily_exercise': 30
            };
            
            // Set defaults if empty
            Object.keys(defaultGoals).forEach(id => {
                const input = document.getElementById(id);
                if (input && !input.value) {
                    input.value = defaultGoals[id];
                }
            });
            
            // Show recommended goals based on activity level
            const recommendBtn = document.createElement('button');
            recommendBtn.type = 'button';
            recommendBtn.innerHTML = '💡 Recommend Goals';
            recommendBtn.style.cssText = 'background: #4CAF50; color: white; border: none; padding: 8px 15px; border-radius: 5px; margin-top: 10px; cursor: pointer;';
            
            const form = document.querySelector('form');
            if (form) {
                form.appendChild(recommendBtn);
                
                recommendBtn.addEventListener('click', function() {
                    const stepsInput = document.getElementById('daily_steps');
                    const caloriesInput = document.getElementById('daily_calories');
                    const exerciseInput = document.getElementById('daily_exercise');
                    
                    if (confirm('Set recommended fitness goals?')) {
                        stepsInput.value = 10000;
                        caloriesInput.value = 2000;
                        exerciseInput.value = 30;
                    }
                });
            }
        });
    </script>
</head>
<body class="theme-<?php echo $current_theme; ?>">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <h1>🏃‍♂️ FitTrack Pro</h1>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
            <a href="profile.php" class="nav-link">👤 Profile</a>
            <a href="activities.php" class="nav-link">📊 Activities</a>
            <a href="food.php" class="nav-link">🥗 Food</a>
            <a href="goals.php" class="nav-link">🎯 Goals</a>
            <a href="logout.php" class="nav-link">🚪 Logout</a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <div class="header">
            <h2>🎯 Fitness Goals</h2>
            <p>Set and track your fitness targets</p>
        </div>
        
        <?php if(isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Today's Progress -->
            <div class="card">
                <h3>📊 Today's Progress</h3>
                
                <!-- Steps Progress -->
                <div class="progress-card">
                    <div class="progress-title">
                        <span>👣 Steps</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" data-width="<?php echo $steps_percentage; ?>" 
                             style="width: <?php echo $steps_percentage; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span><?php echo number_format($progress['today_steps'] ?? 0); ?> steps</span>
                        <span><?php echo number_format($steps_goal); ?> goal</span>
                    </div>
                    <div class="goal-status">
                        <?php if($steps_percentage >= 100): ?>
                            🎉 Goal Achieved!
                        <?php else: ?>
                            <?php echo number_format($steps_goal - ($progress['today_steps'] ?? 0)); ?> steps to go
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Calories Progress -->
                <div class="progress-card">
                    <div class="progress-title">
                        <span>🔥 Calories</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" data-width="<?php echo $calories_percentage; ?>" 
                             style="width: <?php echo $calories_percentage; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span><?php echo number_format($progress['today_calories'] ?? 0); ?> cal</span>
                        <span><?php echo number_format($calories_goal); ?> goal</span>
                    </div>
                    <div class="goal-status">
                        <?php if($calories_percentage >= 100): ?>
                            🎯 Target Reached!
                        <?php else: ?>
                            <?php echo number_format($calories_goal - ($progress['today_calories'] ?? 0)); ?> cal remaining
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Exercise Progress -->
                <div class="progress-card">
                    <div class="progress-title">
                        <span>💪 Exercise</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" data-width="<?php echo $exercise_percentage; ?>" 
                             style="width: <?php echo $exercise_percentage; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span><?php echo $progress['today_exercise'] ?? 0; ?> min</span>
                        <span><?php echo $exercise_goal; ?> min goal</span>
                    </div>
                    <div class="goal-status">
                        <?php if($exercise_percentage >= 100): ?>
                            🏆 Exercise Complete!
                        <?php else: ?>
                            <?php echo $exercise_goal - ($progress['today_exercise'] ?? 0); ?> min to complete
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Current Goals Summary -->
                <div class="current-goals">
                    <h4>📋 Current Goals</h4>
                    <div class="goal-item">
                        <span class="goal-label">Daily Steps:</span>
                        <span class="goal-value"><?php echo number_format($steps_goal); ?></span>
                    </div>
                    <div class="goal-item">
                        <span class="goal-label">Daily Calories:</span>
                        <span class="goal-value"><?php echo number_format($calories_goal); ?></span>
                    </div>
                    <div class="goal-item">
                        <span class="goal-label">Daily Exercise:</span>
                        <span class="goal-value"><?php echo $exercise_goal; ?> min</span>
                    </div>
                    <?php if($current_goals['weight_goal']): ?>
                    <div class="goal-item">
                        <span class="goal-label">Weight Goal:</span>
                        <span class="goal-value"><?php echo $current_goals['weight_goal']; ?> kg</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Set Goals Form -->
            <div class="card">
                <h3>🎯 Set New Goals</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="daily_steps">👣 Daily Steps Goal</label>
                        <input type="number" id="daily_steps" name="daily_steps" 
                               value="<?php echo $steps_goal; ?>" 
                               min="1000" max="50000" step="500" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="daily_calories">🔥 Daily Calorie Goal</label>
                        <input type="number" id="daily_calories" name="daily_calories" 
                               value="<?php echo $calories_goal; ?>" 
                               min="500" max="5000" step="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="daily_exercise">💪 Daily Exercise Goal (minutes)</label>
                        <input type="number" id="daily_exercise" name="daily_exercise" 
                               value="<?php echo $exercise_goal; ?>" 
                               min="5" max="300" step="5" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight_goal">⚖️ Weight Goal (kg) - Optional</label>
                        <input type="number" id="weight_goal" name="weight_goal" 
                               value="<?php echo $current_goals['weight_goal'] ?? ''; ?>" 
                               step="0.1" placeholder="e.g., 65.5">
                    </div>
                    
                    <button type="submit" name="update_goals" class="btn">💾 Save Goals</button>
                </form>
                
               
            </div>
        </div>
    </div>
</body>
</html>