<?php
// log_steps.php - Step Tracking Page
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

// Get user goals for steps
$goals_sql = "SELECT daily_steps_goal FROM user_goals WHERE user_id = $user_id";
$goals_result = $conn->query($goals_sql);
$steps_goal = $goals_result->num_rows > 0 ? $goals_result->fetch_assoc()['daily_steps_goal'] : 10000;

// Process step logging
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_steps'])) {
    $step_count = $_POST['step_count'];
    $step_date = $_POST['step_date'];
    
    // Check if entry already exists for this date
    $check_sql = "SELECT step_id FROM steps WHERE user_id = $user_id AND step_date = '$step_date'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Update existing entry
        $step_id = $check_result->fetch_assoc()['step_id'];
        $update_sql = "UPDATE steps SET step_count = $step_count, updated_at = NOW() 
                      WHERE step_id = $step_id";
        
        if ($conn->query($update_sql)) {
            $message = "Steps updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating steps: " . $conn->error;
            $message_type = "error";
        }
    } else {
        // Insert new entry
        $distance = calculateDistance($step_count);
        $calories = calculateCalories($step_count);
        
        $insert_sql = "INSERT INTO steps (user_id, step_count, step_date, distance_km, calories_burned) 
                      VALUES ($user_id, $step_count, '$step_date', $distance, $calories)";
        
        if ($conn->query($insert_sql)) {
            $message = "Steps logged successfully!";
            $message_type = "success";
        } else {
            $message = "Error logging steps: " . $conn->error;
            $message_type = "error";
        }
    }
}

// Get recent step entries
$steps_sql = "SELECT * FROM steps WHERE user_id = $user_id ORDER BY step_date DESC LIMIT 10";
$steps_result = $conn->query($steps_sql);

// Get step statistics
$today = date('Y-m-d');
$today_sql = "SELECT step_count FROM steps WHERE user_id = $user_id AND step_date = '$today'";
$today_result = $conn->query($today_sql);
$today_steps = $today_result->num_rows > 0 ? $today_result->fetch_assoc()['step_count'] : 0;

// Weekly statistics
$week_start = date('Y-m-d', strtotime('-6 days'));
$weekly_sql = "SELECT 
    SUM(step_count) as weekly_steps,
    AVG(step_count) as avg_daily_steps,
    COUNT(*) as days_tracked
    FROM steps 
    WHERE user_id = $user_id AND step_date >= '$week_start'";
$weekly_result = $conn->query($weekly_sql);
$weekly_stats = $weekly_result->fetch_assoc();

// Total statistics
$total_sql = "SELECT 
    SUM(step_count) as total_steps,
    SUM(distance_km) as total_distance,
    COUNT(*) as total_days
    FROM steps WHERE user_id = $user_id";
$total_result = $conn->query($total_sql);
$total_stats = $total_result->fetch_assoc();

// Helper functions
function calculateDistance($steps) {
    // Average step length: 0.000762 km (2.5 feet)
    return round($steps * 0.000762, 2);
}

function calculateCalories($steps) {
    // Average: 0.04 calories per step
    return round($steps * 0.04);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack Pro - Log Steps</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
             background: linear-gradient(135deg, #e1e5f5ff 0%, #cfa9faff 100%);
            min-height: 100vh;
        }
        
        .theme-dark body {
            background: linear-gradient(135deg, #2e1a2e 0%, #1a1a3b 100%);
            color: white;
        }
        
        .theme-high-contrast body {
            background: linear-gradient(135deg, #330033 0%, #000033 100%);
            color: white;
        }
        
        /* Steps-specific colors */
        .stat-value.steps {
            color: #9c27b0;
        }
        
        .btn.steps {
            background: linear-gradient(90deg, #9c27b0, #673ab7);
        }
        
        .btn.steps:hover {
            box-shadow: 0 5px 15px rgba(156, 39, 176, 0.3);
        }
        
        .progress-fill.steps {
            background: linear-gradient(90deg, #9c27b0, #673ab7);
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
            background: linear-gradient(90deg, #9c27b0, #673ab7);
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
            color: #9c27b0;
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
            background: #9c27b0;
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
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .theme-dark .stat-card,
        .theme-high-contrast .stat-card {
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .theme-dark .stat-label,
        .theme-high-contrast .stat-label {
            color: #ccc;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
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
            background: linear-gradient(90deg, #9c27b0, #673ab7);
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
            box-shadow: 0 5px 15px rgba(156, 39, 176, 0.3);
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
        
        /* Progress Section */
        .progress-section {
            margin-bottom: 30px;
        }
        
        .progress-card {
            background: rgba(156, 39, 176, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .progress-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .theme-dark .progress-title,
        .theme-high-contrast .progress-title {
            color: white;
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
            margin-top: 10px;
            font-weight: bold;
            color: #9c27b0;
        }
        
        /* Steps List */
        .steps-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .step-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .theme-dark .step-item,
        .theme-high-contrast .step-item {
            border-color: #444;
        }
        
        .step-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .theme-dark .step-info h4,
        .theme-high-contrast .step-info h4 {
            color: white;
        }
        
        .step-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .theme-dark .step-meta,
        .theme-high-contrast .step-meta {
            color: #ccc;
        }
        
        .step-count {
            font-weight: bold;
            color: #9c27b0;
            font-size: 1.2rem;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        /* Quick Input Buttons */
        .quick-input {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .quick-btn {
            background: #e0e0e0;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .theme-dark .quick-btn,
        .theme-high-contrast .quick-btn {
            background: #444;
            color: white;
        }
        
        .quick-btn:hover {
            background: #9c27b0;
            color: white;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to today
            const dateInput = document.getElementById('step_date');
            if (dateInput && !dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
            
            // Quick input buttons
            const quickButtons = document.querySelectorAll('.quick-btn');
            const stepInput = document.getElementById('step_count');
            
            quickButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    if (value === 'goal') {
                        stepInput.value = <?php echo $steps_goal; ?>;
                    } else if (value === 'average') {
                        stepInput.value = <?php echo round($weekly_stats['avg_daily_steps'] ?? 8000); ?>;
                    } else {
                        const current = parseInt(stepInput.value) || 0;
                        stepInput.value = current + parseInt(value);
                    }
                    
                    // Update preview
                    updateStepPreview();
                });
            });
            
            // Update step input preview
            function updateStepPreview() {
                const steps = parseInt(stepInput.value) || 0;
                const distance = (steps * 0.000762).toFixed(2);
                const calories = Math.round(steps * 0.04);
                
                document.getElementById('distance_preview').textContent = distance + ' km';
                document.getElementById('calories_preview').textContent = calories + ' cal';
                
                // Update progress bar
                const goal = <?php echo $steps_goal; ?>;
                const percentage = Math.min(100, (steps / goal) * 100);
                document.getElementById('progress_fill').style.width = percentage + '%';
                document.getElementById('progress_text').textContent = 
                    steps.toLocaleString() + ' / ' + goal.toLocaleString() + ' steps';
            }
            
            if (stepInput) {
                stepInput.addEventListener('input', updateStepPreview);
                // Initial update
                updateStepPreview();
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
            <h2>👣 Step Tracking</h2>
            <p>Log your daily steps and track your progress</p>
        </div>
        
        <?php if(isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-value steps"><?php echo number_format($today_steps); ?></div>
                <div class="stat-label">Today's Steps</div>
            </div>
            <div class="stat-card">
                <div class="stat-value steps"><?php echo number_format($weekly_stats['weekly_steps'] ?? 0); ?></div>
                <div class="stat-label">Weekly Steps</div>
            </div>
            <div class="stat-card">
                <div class="stat-value steps"><?php echo round($weekly_stats['avg_daily_steps'] ?? 0); ?></div>
                <div class="stat-label">Daily Average</div>
            </div>
            <div class="stat-card">
                <div class="stat-value steps"><?php echo number_format($steps_goal); ?></div>
                <div class="stat-label">Daily Goal</div>
            </div>
        </div>
        
        <div class="grid">
            <!-- Log Steps Form -->
            <div class="card">
                <h3>➕ Log Steps</h3>
                
                <!-- Today's Progress Preview -->
                <div class="progress-section">
                    <div class="progress-card">
                        <div class="progress-title">Today's Progress</div>
                        <div class="progress-bar">
                            <div id="progress_fill" class="progress-fill steps" 
                                 style="width: <?php echo min(100, ($today_steps / $steps_goal) * 100); ?>%"></div>
                        </div>
                        <div id="progress_text" class="progress-text">
                            <span><?php echo number_format($today_steps); ?> steps</span>
                            <span><?php echo number_format($steps_goal); ?> goal</span>
                        </div>
                        <div class="goal-status">
                            <?php 
                            $today_percentage = $steps_goal > 0 ? ($today_steps / $steps_goal) * 100 : 0;
                            if ($today_percentage >= 100) {
                                echo '🎉 Goal Achieved!';
                            } else {
                                echo number_format($steps_goal - $today_steps) . ' steps to go';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="step_count">👣 Step Count</label>
                        <input type="number" id="step_count" name="step_count" 
                               value="<?php echo $today_steps; ?>"
                               min="0" max="100000" required>
                        
                        <!-- Quick Input Buttons -->
                        <div class="quick-input">
                            <button type="button" class="quick-btn" data-value="1000">+1,000</button>
                            <button type="button" class="quick-btn" data-value="5000">+5,000</button>
                            <button type="button" class="quick-btn" data-value="goal">Goal</button>
                            <button type="button" class="quick-btn" data-value="2000">+2,000</button>
                            <button type="button" class="quick-btn" data-value="10000">+10,000</button>
                            <button type="button" class="quick-btn" data-value="average">Average</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="step_date">📅 Date</label>
                        <input type="date" id="step_date" name="step_date" required>
                    </div>
                    
                    <!-- Preview -->
                    <div style="padding: 15px; background: rgba(156, 39, 176, 0.1); border-radius: 10px; margin-bottom: 20px;">
                        <h4>📊 Step Preview:</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                            <div>
                                <div style="font-size: 0.9rem; color: #666;">Distance</div>
                                <div id="distance_preview" style="font-weight: bold; font-size: 1.2rem;">
                                    <?php echo calculateDistance($today_steps); ?> km
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; color: #666;">Calories</div>
                                <div id="calories_preview" style="font-weight: bold; font-size: 1.2rem;">
                                    <?php echo calculateCalories($today_steps); ?> cal
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="log_steps" class="btn">📝 Log Steps</button>
                </form>
            </div>
            
            <!-- Recent Steps -->
            <div class="card">
                <h3>📋 Recent Step Entries</h3>
                <div class="steps-list">
                    <?php if($steps_result->num_rows > 0): ?>
                        <?php while($step = $steps_result->fetch_assoc()): ?>
                            <div class="step-item">
                                <div class="step-info">
                                    <h4><?php echo date('F j, Y', strtotime($step['step_date'])); ?></h4>
                                    <div class="step-meta">
                                        Distance: <?php echo $step['distance_km']; ?> km • 
                                        Calories: <?php echo $step['calories_burned']; ?> cal
                                    </div>
                                </div>
                                <div class="step-count"><?php echo number_format($step['step_count']); ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No steps logged yet.</p>
                            <p>Start by logging your first steps!</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Total Statistics -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h4>📈 Total Statistics</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #9c27b0;">
                                <?php echo number_format($total_stats['total_steps'] ?? 0); ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #666;">Total Steps</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #9c27b0;">
                                <?php echo number_format($total_stats['total_distance'] ?? 0, 2); ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #666;">Total Distance (km)</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #9c27b0;">
                                <?php echo $total_stats['total_days'] ?? 0; ?>
                            </div>
                            <div style="font-size: 0.9rem; color: #666;">Days Tracked</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>