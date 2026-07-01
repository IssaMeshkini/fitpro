<?php
// activities.php - Activity Tracking Page
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

// Process new activity form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_activity'])) {
    $activity_type = $_POST['activity_type'];
    $activity_name = $_POST['activity_name'];
    $duration = $_POST['duration'];
    $intensity = $_POST['intensity'];
    $calories = $_POST['calories'];
    $activity_date = $_POST['activity_date'];
    $notes = $_POST['notes'];
    
    // Calculate calories if not provided (simple estimation)
    if (empty($calories)) {
        $calories = calculateCalories($activity_type, $duration, $intensity);
    }
    
    $insert_sql = "INSERT INTO activities (user_id, activity_type, activity_name, duration_minutes, 
                   intensity, calories_burned, activity_date, notes) 
                   VALUES ($user_id, '$activity_type', '$activity_name', $duration, 
                   '$intensity', $calories, '$activity_date', '$notes')";
    
    if ($conn->query($insert_sql)) {
        $message = "Activity logged successfully!";
        $message_type = "success";
    } else {
        $message = "Error logging activity: " . $conn->error;
        $message_type = "error";
    }
}

// Delete activity
if (isset($_GET['delete'])) {
    $activity_id = $_GET['delete'];
    $delete_sql = "DELETE FROM activities WHERE activity_id = $activity_id AND user_id = $user_id";
    
    if ($conn->query($delete_sql)) {
        $message = "Activity deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting activity: " . $conn->error;
        $message_type = "error";
    }
}

// Get all activities for this user
$activities_sql = "SELECT * FROM activities WHERE user_id = $user_id ORDER BY activity_date DESC, activity_time DESC";
$activities_result = $conn->query($activities_sql);

// Get activity statistics
$stats_sql = "SELECT 
    COUNT(*) as total_activities,
    SUM(duration_minutes) as total_minutes,
    SUM(calories_burned) as total_calories,
    AVG(duration_minutes) as avg_duration
    FROM activities WHERE user_id = $user_id";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Helper function to estimate calories
function calculateCalories($type, $duration, $intensity) {
    $base_calories = 0;
    
    switch($type) {
        case 'walking': $base_calories = 4; break;
        case 'running': $base_calories = 10; break;
        case 'cycling': $base_calories = 8; break;
        case 'swimming': $base_calories = 7; break;
        case 'gym': $base_calories = 6; break;
        case 'yoga': $base_calories = 3; break;
        default: $base_calories = 5;
    }
    
    switch($intensity) {
        case 'low': $multiplier = 0.7; break;
        case 'high': $multiplier = 1.3; break;
        default: $multiplier = 1.0;
    }
    
    return round($base_calories * $duration * $multiplier);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack Pro - Activities</title>
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
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
        }
        
        .theme-high-contrast body {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: white;
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
            background: linear-gradient(90deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.8rem;
        }
        
        .theme-dark .logo h1,
        .theme-high-contrast .logo h1 {
            color: white;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav-link {
            text-decoration: none;
            color: #667eea;
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
            background: #667eea;
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
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .theme-dark .stat-value,
        .theme-high-contrast .stat-value {
            color: white;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .theme-dark .form-group input,
        .theme-dark .form-group select,
        .theme-dark .form-group textarea,
        .theme-high-contrast .form-group input,
        .theme-high-contrast .form-group select,
        .theme-high-contrast .form-group textarea {
            background: #222;
            color: white;
            border-color: #555;
        }
        
        .btn {
            background: linear-gradient(90deg, #667eea, #764ba2);
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
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .activities-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .theme-dark .activity-item,
        .theme-high-contrast .activity-item {
            border-color: #444;
        }
        
        .activity-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .theme-dark .activity-info h4,
        .theme-high-contrast .activity-info h4 {
            color: white;
        }
        
        .activity-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .theme-dark .activity-meta,
        .theme-high-contrast .activity-meta {
            color: #ccc;
        }
        
        .activity-calories {
            font-weight: bold;
            color: #ff416c;
        }
        
        .delete-btn {
            background: #ff416c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .delete-btn:hover {
            background: #ff1e4d;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .theme-dark .no-data,
        .theme-high-contrast .no-data {
            color: #ccc;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-calculate calories when activity details change
            const activityType = document.getElementById('activity_type');
            const duration = document.getElementById('duration');
            const intensity = document.getElementById('intensity');
            const caloriesInput = document.getElementById('calories');
            
            function calculateEstimatedCalories() {
                if (activityType.value && duration.value) {
                    // Simple calculation
                    const baseCalories = {
                        'walking': 4, 'running': 10, 'cycling': 8,
                        'swimming': 7, 'gym': 6, 'yoga': 3, 'other': 5
                    };
                    
                    const multipliers = {
                        'low': 0.7, 'medium': 1.0, 'high': 1.3
                    };
                    
                    const base = baseCalories[activityType.value] || 5;
                    const multiplier = multipliers[intensity.value] || 1.0;
                    const estimated = Math.round(base * duration.value * multiplier);
                    
                    if (!caloriesInput.value || caloriesInput.value == estimated) {
                        caloriesInput.value = estimated;
                    }
                }
            }
            
            if (activityType && duration && intensity) {
                activityType.addEventListener('change', calculateEstimatedCalories);
                duration.addEventListener('input', calculateEstimatedCalories);
                intensity.addEventListener('change', calculateEstimatedCalories);
            }
            
            // Set default date to today
            const dateInput = document.getElementById('activity_date');
            if (dateInput && !dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
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
            <h2>📊 Activity Tracking</h2>
            <p>Log your exercises and track your fitness journey</p>
        </div>
        
        <?php if(isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_activities'] ?? 0; ?></div>
                <div class="stat-label">Total Activities</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_minutes'] ?? 0; ?></div>
                <div class="stat-label">Total Minutes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_calories'] ?? 0; ?></div>
                <div class="stat-label">Calories Burned</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo round($stats['avg_duration'] ?? 0); ?></div>
                <div class="stat-label">Avg. Duration (min)</div>
            </div>
        </div>
        
        <div class="grid">
            <!-- Add Activity Form -->
            <div class="card">
                <h3>➕ Log New Activity</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="activity_type">Activity Type</label>
                        <select id="activity_type" name="activity_type" required>
                            <option value="">Select type</option>
                            <option value="walking">Walking</option>
                            <option value="running">Running</option>
                            <option value="cycling">Cycling</option>
                            <option value="swimming">Swimming</option>
                            <option value="gym">Gym Workout</option>
                            <option value="yoga">Yoga</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="activity_name">Activity Name (Optional)</label>
                        <input type="text" id="activity_name" name="activity_name" 
                               placeholder="e.g., Morning Run, Gym Session">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" min="1" max="300" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="intensity">Intensity Level</label>
                        <select id="intensity" name="intensity">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="calories">Calories Burned</label>
                        <input type="number" id="calories" name="calories" 
                               placeholder="Will auto-calculate">
                    </div>
                    
                    <div class="form-group">
                        <label for="activity_date">Date</label>
                        <input type="date" id="activity_date" name="activity_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="How did it feel?"></textarea>
                    </div>
                    
                    <button type="submit" name="add_activity" class="btn">📝 Log Activity</button>
                </form>
            </div>
            
            <!-- Activities List -->
            <div class="card">
                <h3>📋 Recent Activities</h3>
                <div class="activities-list">
                    <?php if($activities_result->num_rows > 0): ?>
                        <?php while($activity = $activities_result->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="activity-info">
                                    <h4><?php echo ucfirst($activity['activity_type']); ?> 
                                        <?php if($activity['activity_name']): ?>
                                            - <?php echo htmlspecialchars($activity['activity_name']); ?>
                                        <?php endif; ?>
                                    </h4>
                                    <div class="activity-meta">
                                        <?php echo date('M d, Y', strtotime($activity['activity_date'])); ?> • 
                                        <?php echo $activity['duration_minutes']; ?> min • 
                                        <?php echo ucfirst($activity['intensity']); ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="activity-calories">🔥 <?php echo $activity['calories_burned']; ?> cal</div> <br>
                                    <a href="activities.php?delete=<?php echo $activity['activity_id']; ?>" 
                                       class="delete-btn" 
                                       onclick="return confirm('Delete this activity?')">Delete</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No activities logged yet.</p>
                            <p>Start by logging your first activity!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>