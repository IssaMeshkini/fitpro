<?php
// food.php - Food Tracking Page
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

// Process new food entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_food'])) {
    $meal_type = $_POST['meal_type'];
    $food_name = $_POST['food_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $calories = $_POST['calories'];
    $entry_date = $_POST['entry_date'];
    
    $insert_sql = "INSERT INTO food_entries (user_id, meal_type, food_name, quantity, unit, calories, entry_date) 
                   VALUES ($user_id, '$meal_type', '$food_name', $quantity, '$unit', $calories, '$entry_date')";
    
    if ($conn->query($insert_sql)) {
        $message = "Food entry added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding food entry: " . $conn->error;
        $message_type = "error";
    }
}

// Delete food entry
if (isset($_GET['delete'])) {
    $food_id = $_GET['delete'];
    $delete_sql = "DELETE FROM food_entries WHERE food_id = $food_id AND user_id = $user_id";
    
    if ($conn->query($delete_sql)) {
        $message = "Food entry deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting food entry: " . $conn->error;
        $message_type = "error";
    }
}

// Get all food entries for this user
$food_sql = "SELECT * FROM food_entries WHERE user_id = $user_id ORDER BY entry_date DESC, entry_time DESC";
$food_result = $conn->query($food_sql);

// Get today's total calories
$today = date('Y-m-d');
$today_sql = "SELECT SUM(calories) as total_calories FROM food_entries 
              WHERE user_id = $user_id AND entry_date = '$today'";
$today_result = $conn->query($today_sql);
$today_calories = $today_result->fetch_assoc()['total_calories'] ?? 0;

// Get weekly average
$week_start = date('Y-m-d', strtotime('-6 days'));
$weekly_sql = "SELECT AVG(calories) as avg_calories FROM food_entries 
               WHERE user_id = $user_id AND entry_date >= '$week_start'";
$weekly_result = $conn->query($weekly_sql);
$avg_calories = round($weekly_result->fetch_assoc()['avg_calories'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack Pro - Food Tracking</title>
    <style>
        /* Reusing styles from activities.php with food-specific adjustments */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
             background: linear-gradient(135deg, #c8d1f3ff 0%, #e7d8f8ff 100%);
            min-height: 100vh;
        }
        
        .theme-dark body {
            background: linear-gradient(135deg, #1a2e1a 0%, #0d3b0d 100%);
            color: white;
        }
        
        .theme-high-contrast body {
            background: linear-gradient(135deg, #003300 0%, #001a00 100%);
            color: white;
        }
        
        /* Food-specific colors */
        .stat-value.food {
            color: #4CAF50;
        }
        
        .btn.food {
            background: linear-gradient(90deg, #4CAF50, #45a049);
        }
        
        .btn.food:hover {
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .activity-calories.food {
            color: #4CAF50;
        }
        
        /* Navigation (same as activities) */
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
            background: linear-gradient(90deg, #4CAF50, #45a049);
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
            color: #4CAF50;
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
            background: #4CAF50;
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
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .theme-dark .form-group input,
        .theme-dark .form-group select,
        .theme-high-contrast .form-group input,
        .theme-high-contrast .form-group select {
            background: #222;
            color: white;
            border-color: #555;
        }
        
        .btn {
            background: linear-gradient(90deg, #4CAF50, #45a049);
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
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .food-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .food-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .theme-dark .food-item,
        .theme-high-contrast .food-item {
            border-color: #444;
        }
        
        .food-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .theme-dark .food-info h4,
        .theme-high-contrast .food-info h4 {
            color: white;
        }
        
        .food-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .theme-dark .food-meta,
        .theme-high-contrast .food-meta {
            color: #ccc;
        }
        
        .food-calories {
            font-weight: bold;
            color: #4CAF50;
        }
        
        .meal-badge {
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 8px;
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
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
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to today
            const dateInput = document.getElementById('entry_date');
            if (dateInput && !dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
            
            // Common food database for suggestions
            const foodDatabase = {
                'Apple': 95,
                'Banana': 105,
                'Chicken Breast': 165,
                'Rice (1 cup)': 205,
                'Bread (slice)': 79,
                'Egg': 78,
                'Milk (1 cup)': 149,
                'Pasta (1 cup)': 220,
                'Salmon': 206,
                'Yogurt': 150
            };
            
            // Auto-fill calories based on food name
            const foodNameInput = document.getElementById('food_name');
            const caloriesInput = document.getElementById('calories');
            
            if (foodNameInput && caloriesInput) {
                foodNameInput.addEventListener('blur', function() {
                    const foodName = this.value.trim();
                    if (foodName && foodDatabase[foodName]) {
                        caloriesInput.value = foodDatabase[foodName];
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
            <h2>🥗 Food & Nutrition</h2>
            <p>Track your daily calorie intake</p>
        </div>
        
        <?php if(isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-value food"><?php echo $today_calories; ?></div>
                <div class="stat-label">Today's Calories</div>
            </div>
            <div class="stat-card">
                <div class="stat-value food"><?php echo $avg_calories; ?></div>
                <div class="stat-label">Daily Average (7 days)</div>
            </div>
            <div class="stat-card">
                <?php
                $total_food = $food_result->num_rows;
                $food_result->data_seek(0); // Reset pointer
                ?>
                <div class="stat-value food"><?php echo $total_food; ?></div>
                <div class="stat-label">Total Food Entries</div>
            </div>
            <div class="stat-card">
                <?php
                // Get most common meal type
                $meal_sql = "SELECT meal_type, COUNT(*) as count FROM food_entries 
                            WHERE user_id = $user_id GROUP BY meal_type ORDER BY count DESC LIMIT 1";
                $meal_result = $conn->query($meal_sql);
                $common_meal = $meal_result->num_rows > 0 ? ucfirst($meal_result->fetch_assoc()['meal_type']) : 'N/A';
                ?>
                <div class="stat-value food"><?php echo $common_meal; ?></div>
                <div class="stat-label">Most Common Meal</div>
            </div>
        </div>
        
        <div class="grid">
            <!-- Add Food Form -->
            <div class="card">
                <h3>➕ Add Food Entry</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="meal_type">Meal Type</label>
                        <select id="meal_type" name="meal_type" required>
                            <option value="">Select meal</option>
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="snack">Snack</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="food_name">Food Name</label>
                        <input type="text" id="food_name" name="food_name" 
                               placeholder="e.g., Apple, Chicken Breast" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" 
                               step="0.1" min="0.1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <select id="unit" name="unit">
                            <option value="grams">Grams</option>
                            <option value="pieces">Pieces</option>
                            <option value="cups">Cups</option>
                            <option value="ml">ml</option>
                            <option value="serving">Serving</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="calories">Calories</label>
                        <input type="number" id="calories" name="calories" 
                               min="1" required placeholder="Calories">
                    </div>
                    
                    <div class="form-group">
                        <label for="entry_date">Date</label>
                        <input type="date" id="entry_date" name="entry_date" required>
                    </div>
                    
                    <button type="submit" name="add_food" class="btn">➕ Add Food Entry</button>
                </form>
            </div>
            
            <!-- Food Entries List -->
            <div class="card">
                <h3>📋 Recent Food Entries</h3>
                <div class="food-list">
                    <?php if($food_result->num_rows > 0): ?>
                        <?php while($food = $food_result->fetch_assoc()): ?>
                            <div class="food-item">
                                <div class="food-info">
                                    <h4>
                                        <span class="meal-badge"><?php echo ucfirst($food['meal_type']); ?></span>
                                        <?php echo htmlspecialchars($food['food_name']); ?>
                                    </h4>
                                    <div class="food-meta">
                                        <?php echo date('M d, Y', strtotime($food['entry_date'])); ?> • 
                                        <?php echo $food['quantity']; ?> <?php echo $food['unit']; ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="food-calories">🔥 <?php echo $food['calories']; ?> cal</div> <br>
                                    <a href="food.php?delete=<?php echo $food['food_id']; ?>" 
                                       class="delete-btn" 
                                       onclick="return confirm('Delete this food entry?')">Delete</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <p>No food entries yet.</p>
                            <p>Start tracking your meals!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>