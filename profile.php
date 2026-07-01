<?php
// profile.php - Functional Profile Page
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
$email = $_SESSION['email'];

// Get user details
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Get user settings
$settings_sql = "SELECT * FROM user_settings WHERE user_id = $user_id";
$settings_result = $conn->query($settings_sql);
$settings = $settings_result->num_rows > 0 ? $settings_result->fetch_assoc() : null;

// Messages
$message = "";
$message_type = "";

// Process Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_full_name = $_POST['full_name'];
    $new_height = $_POST['height'];
    $new_weight = $_POST['weight'];
    $new_gender = $_POST['gender'];
    
    $update_sql = "UPDATE users SET 
        full_name = '$new_full_name',
        height = " . ($new_height ? "'$new_height'" : "NULL") . ",
        weight = " . ($new_weight ? "'$new_weight'" : "NULL") . ",
        gender = '$new_gender'
        WHERE user_id = $user_id";
    
    if ($conn->query($update_sql)) {
        $_SESSION['full_name'] = $new_full_name;
        $message = "Profile updated successfully!";
        $message_type = "success";
        // Refresh user data
        $user_result = $conn->query($user_sql);
        $user = $user_result->fetch_assoc();
    } else {
        $message = "Error updating profile: " . $conn->error;
        $message_type = "error";
    }
}

// Process Theme Settings Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    $color_scheme = $_POST['color_scheme'];
    $measurement_unit = $_POST['measurement_unit'];
    
    if ($settings) {
        $update_sql = "UPDATE user_settings SET 
            color_scheme = '$color_scheme',
            measurement_unit = '$measurement_unit',
            updated_at = NOW()
            WHERE user_id = $user_id";
    } else {
        $update_sql = "INSERT INTO user_settings (user_id, color_scheme, measurement_unit) 
                      VALUES ($user_id, '$color_scheme', '$measurement_unit')";
    }
    
    if ($conn->query($update_sql)) {
        $message = "Theme settings updated successfully!";
        $message_type = "success";
        // Refresh settings
        $settings_result = $conn->query($settings_sql);
        $settings = $settings_result->fetch_assoc();
    } else {
        $message = "Error updating settings: " . $conn->error;
        $message_type = "error";
    }
}

// Apply current settings
$current_color_scheme = $settings['color_scheme'] ?? 'light';
$current_measurement = $settings['measurement_unit'] ?? 'metric';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack Pro - Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Light Theme (Default) */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Dark Theme */
        body.theme-dark {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #ffffff;
        }
        
        /* High Contrast Theme */
        body.theme-high-contrast {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: #ffffff;
        }
        
        .theme-dark .navbar,
        .theme-high-contrast .navbar {
            background: rgba(0, 0, 0, 0.9);
            color: white;
        }
        
        .theme-dark .card,
        .theme-high-contrast .card {
            background: rgba(0, 0, 0, 0.8);
            color: white;
        }
        
        .theme-dark input,
        .theme-dark select,
        .theme-high-contrast input,
        .theme-high-contrast select {
            background: #222;
            color: white;
            border-color: #555;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo h1 {
            background: linear-gradient(90deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-size: 1.8rem;
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
        
        .container {
            max-width: 80%;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 3rem;
            margin: 0 auto 20px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .profile-header h2 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: white;
        }
        
        .profile-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card h3 {
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .btn {
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(102, 126, 234, 0.4);
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: bold;
            text-align: center;
        }
        
        .success {
            background: linear-gradient(90deg, #4CAF50, #45a049);
            color: white;
        }
        
        .error {
            background: linear-gradient(90deg, #ff416c, #ff4b2b);
            color: white;
        }
        
        .theme-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        
        .theme-option {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .theme-option:hover {
            transform: translateY(-5px);
        }
        
        .theme-option.selected {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .theme-light {
            background: linear-gradient(135deg, #f5f7ff, #e3e6ff);
            color: #333;
        }
        
        .theme-dark-preview {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white;
        }
        
        .theme-high-contrast-preview {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
        }
        
        .theme-option .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .theme-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        // Apply theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = '<?php echo $current_color_scheme; ?>';
            if (savedTheme) {
                document.body.classList.add('theme-' + savedTheme);
            }
        });
    </script>
</head>
<body class="theme-<?php echo $current_color_scheme; ?>">
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
            <form action="logout.php" method="POST" style="display: inline;">
                <button type="submit" class="logout-btn">🚪 Logout</button>
            </form>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="user-avatar">
                <?php echo strtoupper(substr($full_name, 0, 1)); ?>
            </div>
            <h2 style = "color:black;"><?php echo htmlspecialchars($full_name); ?></h2>
            <p style = "color:black;">@<?php echo htmlspecialchars($username); ?> • <?php echo htmlspecialchars($email); ?></p>
        </div>
        
        <!-- Messages -->
        <?php if($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Profile Information -->
            <div class="card">
                <h3>👤 Personal Information</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">📝 Full Name</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">👤 Username</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">📧 Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">⚧ Gender</label>
                        <select id="gender" name="gender">
                            <option value="prefer_not_to_say" <?php echo ($user['gender'] ?? '') == 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            <option value="male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <?php
                    // Display height/weight based on measurement unit
                    $height = $user['height'] ?? 0;
                    $weight = $user['weight'] ?? 0;
                    
                    if ($current_measurement == 'imperial') {
                        // Convert to imperial
                        $height = $height * 0.393701; // cm to inches
                        $weight = $weight * 2.20462;  // kg to lbs
                        $height_label = "Height (inches)";
                        $weight_label = "Weight (lbs)";
                    } else {
                        $height_label = "Height (cm)";
                        $weight_label = "Weight (kg)";
                    }
                    ?>
                    
                    <div class="form-group">
                        <label for="height">📏 <?php echo $height_label; ?></label>
                        <input type="number" id="height" name="height" step="0.1"
                               value="<?php echo $height ? round($height, 1) : ''; ?>" placeholder="Enter your height">
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">⚖️ <?php echo $weight_label; ?></label>
                        <input type="number" id="weight" name="weight" step="0.1"
                               value="<?php echo $weight ? round($weight, 1) : ''; ?>" placeholder="Enter your weight">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">💾 Update Profile</button>
                </form>
            </div>
            
            <!-- Theme Settings -->
            <div class="card">
                <h3>🎨 Theme Settings</h3>
                <form method="POST" action="" id="themeForm">
                    <div class="form-group">
                        <label>Color Scheme</label>
                        <div class="theme-options">
                            <div class="theme-option theme-light <?php echo $current_color_scheme == 'light' ? 'selected' : ''; ?>"
                                 onclick="selectTheme('light')">
                                <div class="icon">🌞</div>
                                <div>Light</div>
                            </div>
                            <div class="theme-option theme-dark-preview <?php echo $current_color_scheme == 'dark' ? 'selected' : ''; ?>"
                                 onclick="selectTheme('dark')">
                                <div class="icon">🌙</div>
                                <div>Dark</div>
                            </div>
                            <div class="theme-option theme-high-contrast-preview <?php echo $current_color_scheme == 'high-contrast' ? 'selected' : ''; ?>"
                                 onclick="selectTheme('high-contrast')">
                                <div class="icon">👁️</div>
                                <div>High Contrast</div>
                            </div>
                        </div>
                        <input type="hidden" name="color_scheme" id="color_scheme" value="<?php echo $current_color_scheme; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="measurement_unit">📐 Measurement Units</label>
                        <select id="measurement_unit" name="measurement_unit">
                            <option value="metric" <?php echo $current_measurement == 'metric' ? 'selected' : ''; ?>>Metric (kg, cm)</option>
                            <option value="imperial" <?php echo $current_measurement == 'imperial' ? 'selected' : ''; ?>>Imperial (lb, inches)</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_settings" class="btn">🎨 Save Theme Settings</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Theme selection
        function selectTheme(theme) {
            // Update hidden input
            document.getElementById('color_scheme').value = theme;
            
            // Update visual selection
            document.querySelectorAll('.theme-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.closest('.theme-option').classList.add('selected');
            
            // Preview theme
            document.body.className = 'theme-' + theme;
        }
        
        // Real-time preview of measurement units
        document.getElementById('measurement_unit').addEventListener('change', function() {
            const unit = this.value;
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            const heightLabel = document.querySelector('label[for="height"]');
            const weightLabel = document.querySelector('label[for="weight"]');
            
            // Update labels
            if (unit === 'imperial') {
                heightLabel.innerHTML = '📏 Height (inches)';
                weightLabel.innerHTML = '⚖️ Weight (lbs)';
                
                // Convert values if they exist
                if (heightInput.value) {
                    heightInput.value = (parseFloat(heightInput.value) * 0.393701).toFixed(1);
                }
                if (weightInput.value) {
                    weightInput.value = (parseFloat(weightInput.value) * 2.20462).toFixed(1);
                }
            } else {
                heightLabel.innerHTML = '📏 Height (cm)';
                weightLabel.innerHTML = '⚖️ Weight (kg)';
                
                // Convert values if they exist
                if (heightInput.value) {
                    heightInput.value = (parseFloat(heightInput.value) / 0.393701).toFixed(1);
                }
                if (weightInput.value) {
                    weightInput.value = (parseFloat(weightInput.value) / 2.20462).toFixed(1);
                }
            }
        });
        
       
        // Show current measurement units
        document.addEventListener('DOMContentLoaded', function() {
            const unit = '<?php echo $current_measurement; ?>';
            if (unit === 'imperial') {
                const heightLabel = document.querySelector('label[for="height"]');
                const weightLabel = document.querySelector('label[for="weight"]');
                heightLabel.innerHTML = '📏 Height (inches)';
                weightLabel.innerHTML = '⚖️ Weight (lbs)';
            }
        });
    </script>
</body>
</html>