<?php
// dashboard.php - Main Dashboard
require_once 'conn.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Get today's date
$today = date('Y-m-d');

// Get today's steps
$steps_sql = "SELECT step_count FROM steps WHERE user_id = $user_id AND step_date = '$today'";
$steps_result = $conn->query($steps_sql);
$today_steps = $steps_result->num_rows > 0 ? $steps_result->fetch_assoc()['step_count'] : 0;

// Get today's calories consumed
$calories_sql = "SELECT SUM(calories) as total_calories FROM food_entries WHERE user_id = $user_id AND entry_date = '$today'";
$calories_result = $conn->query($calories_sql);
$today_calories = $calories_result->fetch_assoc()['total_calories'] ?? 0;

// Get today's exercise minutes
$exercise_sql = "SELECT SUM(duration_minutes) as total_minutes FROM activities WHERE user_id = $user_id AND activity_date = '$today'";
$exercise_result = $conn->query($exercise_sql);
$today_exercise = $exercise_result->fetch_assoc()['total_minutes'] ?? 0;

// Get user goals
$goals_sql = "SELECT daily_steps_goal, daily_calorie_goal, daily_exercise_minutes FROM user_goals WHERE user_id = $user_id";
$goals_result = $conn->query($goals_sql);
if ($goals_result->num_rows > 0) {
    $goals = $goals_result->fetch_assoc();
    $steps_goal = $goals['daily_steps_goal'] ?? 10000;
    $calorie_goal = $goals['daily_calorie_goal'] ?? 2000;
    $exercise_goal = $goals['daily_exercise_minutes'] ?? 30;
} else {
    // Set default goals
    $steps_goal = 10000;
    $calorie_goal = 2000;
    $exercise_goal = 30;
}

// Calculate percentages
$steps_percentage = min(100, ($today_steps / $steps_goal) * 100);
$calories_percentage = min(100, ($today_calories / $calorie_goal) * 100);
$exercise_percentage = min(100, ($today_exercise / $exercise_goal) * 100);

// Get recent activities
$activities_sql = "SELECT * FROM activities WHERE user_id = $user_id ORDER BY activity_date DESC LIMIT 5";
$activities_result = $conn->query($activities_sql);

// Get recent food entries
$food_sql = "SELECT * FROM food_entries WHERE user_id = $user_id ORDER BY entry_date DESC LIMIT 5";
$food_result = $conn->query($food_sql);

// Get weekly summary
$week_start = date('Y-m-d', strtotime('-6 days'));
$weekly_sql = "SELECT 
    SUM(step_count) as weekly_steps,
    SUM(calories) as weekly_calories,
    SUM(duration_minutes) as weekly_exercise
    FROM (
        SELECT step_count, 0 as calories, 0 as duration_minutes, step_date as date FROM steps WHERE user_id = $user_id AND step_date >= '$week_start'
        UNION ALL
        SELECT 0, calories, 0, entry_date FROM food_entries WHERE user_id = $user_id AND entry_date >= '$week_start'
        UNION ALL
        SELECT 0, 0, duration_minutes, activity_date FROM activities WHERE user_id = $user_id AND activity_date >= '$week_start'
    ) as combined";
$weekly_result = $conn->query($weekly_sql);
$weekly_data = $weekly_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack Pro - Dashboard</title>
    <style>
        /* Colorful Dashboard Design */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #e1e5f5ff 0%, #bc88f7ff 100%);
            background-size: 400% 400%;
            min-height: 100vh;
        }
        
        
        
        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo h1 {
            background: linear-gradient(90deg, #667eea, #f5576c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .user-details h3 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .user-details p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-link {
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .nav-link:hover {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .logout-btn {
            background: linear-gradient(90deg, #ff416c, #ff4b2b);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 65, 108, 0.3);
        }
        
        /* Main Content */
        .container {
            max-width: 80%;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.8s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-section h2 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #667eea, #f5576c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .welcome-section p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.steps {
            border-top: 5px solid #667eea;
        }
        
        .stat-card.calories {
            border-top: 5px solid #4CAF50;
        }
        
        .stat-card.exercise {
            border-top: 5px solid #ff9800;
        }
        
        .stat-card.weekly {
            border-top: 5px solid #9c27b0;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .steps .stat-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .calories .stat-icon {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .exercise .stat-icon {
            background: linear-gradient(135deg, #ff9800, #ff5722);
            color: white;
        }
        
        .weekly .stat-icon {
            background: linear-gradient(135deg, #9c27b0, #673ab7);
            color: white;
        }
        
        .stat-title h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-title p {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Progress Bars */
        .progress-container {
            margin: 25px 0;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .progress-bar {
            height: 12px;
            background: #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 1.5s ease-in-out;
        }
        
        .steps .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .calories .progress-fill {
            background: linear-gradient(90deg, #4CAF50, #45a049);
        }
        
        .exercise .progress-fill {
            background: linear-gradient(90deg, #ff9800, #ff5722);
        }
        
        .goal-text {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        /* Recent Activity */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .activity-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .activity-card h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(102, 126, 234, 0.05);
            border-radius: 10px;
            transform: translateX(5px);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-name {
            font-weight: 600;
            color: #333;
        }
        
        .activity-value {
            font-weight: bold;
            color: #667eea;
        }
        
        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .quick-actions h3 {
            font-size: 1.8rem;
            margin-bottom: 25px;
            text-align: center;
            color: #333;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .action-btn i {
            font-size: 2rem;
        }
        
        .action-btn.log-activity { background: linear-gradient(135deg, #4CAF50, #45a049); }
        .action-btn.add-food { background: linear-gradient(135deg, #ff9800, #ff5722); }
        .action-btn.log-steps { background: linear-gradient(135deg, #9c27b0, #673ab7); }
        .action-btn.set-goals { background: linear-gradient(135deg, #00bcd4, #0097a7); }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .activity-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .activity-card {
                padding: 20px;
            }
            
            .activity-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .container {
                padding: 0 15px;
            }
        }
        
        @media (max-width: 480px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-section h2 {
                font-size: 2rem;
            }
            
            .stat-card {
                padding: 20px;
            }
        }
        
        /* Animations */
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            <div class="user-avatar">
                <?php echo strtoupper(substr($full_name, 0, 1)); ?>
            </div>
            <h1>🏃‍♂️ FitTrack Pro</h1>
        </div>
        
        <div class="user-info">
            <div class="user-details">
                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                <p>@<?php echo htmlspecialchars($username); ?></p>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
                <a href="profile.php" class="nav-link">👤 Profile</a>
                <a href="activities.php" class="nav-link">📊 Activities</a>
                <a href="food.php" class="nav-link">🥗 Food</a>
                <a href="goals.php" class="nav-link">🎯 Goals</a>
                <form action="logout.php" method="POST" style="display: inline;">
                    <button type="submit" class="logout-btn">🚪 Logout</button>
                </form>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $full_name)[0]); ?>! 👋</h2>
            <p>Track your fitness journey and achieve your goals. Here's your progress for today.</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <!-- Steps Card -->
            <div class="stat-card steps">
                <div class="stat-header">
                    <div class="stat-icon">👣</div>
                    <div class="stat-title">
                        <h3>Steps Today</h3>
                        <p>Daily Goal: <?php echo number_format($steps_goal); ?> steps</p>
                    </div>
                </div>
                <div class="progress-container">
                    <div class="progress-info">
                        <span><?php echo number_format($today_steps); ?> steps</span>
                        <span><?php echo round($steps_percentage, 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $steps_percentage; ?>%"></div>
                    </div>
                    <div class="goal-text">
                        <?php if($steps_percentage >= 100): ?>
                            🎉 Goal achieved! You're amazing!
                        <?php else: ?>
                            <?php echo number_format($steps_goal - $today_steps); ?> more steps to go!
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Calories Card -->
            <div class="stat-card calories">
                <div class="stat-header">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-title">
                        <h3>Calories Today</h3>
                        <p>Daily Goal: <?php echo number_format($calorie_goal); ?> kcal</p>
                    </div>
                </div>
                <div class="progress-container">
                    <div class="progress-info">
                        <span><?php echo number_format($today_calories); ?> kcal</span>
                        <span><?php echo round($calories_percentage, 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $calories_percentage; ?>%"></div>
                    </div>
                    <div class="goal-text">
                        <?php if($calories_percentage >= 100): ?>
                            🎯 Target reached! Great job!
                        <?php else: ?>
                            <?php echo number_format($calorie_goal - $today_calories); ?> kcal remaining
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Exercise Card -->
            <div class="stat-card exercise">
                <div class="stat-header">
                    <div class="stat-icon">💪</div>
                    <div class="stat-title">
                        <h3>Exercise Today</h3>
                        <p>Daily Goal: <?php echo $exercise_goal; ?> minutes</p>
                    </div>
                </div>
                <div class="progress-container">
                    <div class="progress-info">
                        <span><?php echo $today_exercise; ?> minutes</span>
                        <span><?php echo round($exercise_percentage, 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $exercise_percentage; ?>%"></div>
                    </div>
                    <div class="goal-text">
                        <?php if($exercise_percentage >= 100): ?>
                            🏆 Exercise goal completed! 💪
                        <?php else: ?>
                            <?php echo $exercise_goal - $today_exercise; ?> minutes to complete
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Weekly Summary Card -->
            <div class="stat-card weekly">
                <div class="stat-header">
                    <div class="stat-icon">📈</div>
                    <div class="stat-title">
                        <h3>Weekly Summary</h3>
                        <p>Last 7 days performance</p>
                    </div>
                </div>
                <div class="progress-container">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px;">
                        <div style="text-align: center;">
                            <h4 style="color: #667eea; font-size: 1.2rem;"><?php echo number_format($weekly_data['weekly_steps'] ?? 0); ?></h4>
                            <p style="color: #666; font-size: 0.9rem;">Total Steps</p>
                        </div>
                        <div style="text-align: center;">
                            <h4 style="color: #4CAF50; font-size: 1.2rem;"><?php echo number_format($weekly_data['weekly_calories'] ?? 0); ?></h4>
                            <p style="color: #666; font-size: 0.9rem;">Total Calories</p>
                        </div>
                        <div style="text-align: center;">
                            <h4 style="color: #ff9800; font-size: 1.2rem;"><?php echo number_format($weekly_data['weekly_exercise'] ?? 0); ?></h4>
                            <p style="color: #666; font-size: 0.9rem;">Exercise Minutes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-grid">
            <!-- Recent Activities -->
            <div class="activity-card">
                <h3>🏃‍♂️ Recent Activities</h3>
                <ul class="activity-list">
                    <?php if($activities_result->num_rows > 0): ?>
                        <?php while($activity = $activities_result->fetch_assoc()): ?>
                            <li class="activity-item">
                                <div>
                                    <div class="activity-name"><?php echo ucfirst($activity['activity_type']); ?></div>
                                    <div class="activity-time"><?php echo date('M d, Y', strtotime($activity['activity_date'])); ?></div>
                                </div>
                                <div class="activity-value"><?php echo $activity['duration_minutes']; ?> min</div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-name">No activities logged yet</div>
                            <div class="activity-value">0 min</div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Recent Food Entries -->
            <div class="activity-card">
                <h3>🥗 Recent Food Entries</h3>
                <ul class="activity-list">
                    <?php if($food_result->num_rows > 0): ?>
                        <?php while($food = $food_result->fetch_assoc()): ?>
                            <li class="activity-item">
                                <div>
                                    <div class="activity-name"><?php echo ucfirst($food['food_name']); ?></div>
                                    <div class="activity-time"><?php echo date('M d, Y', strtotime($food['entry_date'])); ?> • <?php echo ucfirst($food['meal_type']); ?></div>
                                </div>
                                <div class="activity-value"><?php echo $food['calories']; ?> kcal</div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-name">No food entries yet</div>
                            <div class="activity-value">0 kcal</div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>🚀 Quick Actions</h3>
            <div class="actions-grid">
                <button class="action-btn log-activity" onclick="window.location.href='activities.php'">
                    <span style="font-size: 2rem;">🏃‍♂️</span>
                    Log Activity
                </button>
                <button class="action-btn add-food" onclick="window.location.href='food.php'">
                    <span style="font-size: 2rem;">🍎</span>
                    Add Food Entry
                </button>
                <button class="action-btn log-steps" onclick="window.location.href='log_steps.php'">
                    <span style="font-size: 2rem;">👣</span>
                    Log Steps
                </button>
                <button class="action-btn set-goals" onclick="window.location.href='goals.php'">
                    <span style="font-size: 2rem;">🎯</span>
                    Set Goals
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
            
            // Add hover effects to cards
            const cards = document.querySelectorAll('.stat-card, .activity-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });
            
            // Welcome message animation
            const welcome = document.querySelector('.welcome-section');
            setTimeout(() => {
                welcome.classList.add('pulse');
            }, 1000);
            
            // Update time dynamically
            function updateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: true 
                });
                const dateString = now.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                // Update time in welcome message
                const welcomeText = document.querySelector('.welcome-section p');
                if (welcomeText) {
                    welcomeText.innerHTML = `Track your fitness journey and achieve your goals. Here's your progress for today.<br><small>${dateString} • ${timeString}</small>`;
                }
            }
            
            updateTime();
            setInterval(updateTime, 60000); // Update every minute
        });
    </script>
</body>
</html>