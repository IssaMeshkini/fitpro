<?php
// register.php - User Registration Page
session_start(); 
require_once 'conn.php';
$message = "";
$message_type = "";

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    
    // Simple validation
    if (empty($username) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields!";
        $message_type = "error";
    } else {
        // Check if user already exists
        $check_sql = "SELECT user_id FROM users WHERE username = '$username' OR email = '$email'";
        $result = $conn->query($check_sql);
        
        if ($result->num_rows > 0) {
            $message = "Username or email already exists!";
            $message_type = "error";
        } else {
            // Insert into database (NO HASHING for simplicity)
            $insert_sql = "INSERT INTO users (username, email, password_hash, full_name) 
                          VALUES ('$username', '$email', '$password', '$full_name')";
            
            if ($conn->query($insert_sql) === TRUE) {
                $message = "Registration successful! You can now login.";
                $message_type = "success";
                // Clear form
                $username = $email = $full_name = "";
            } else {
                $message = "Error: " . $conn->error;
                $message_type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Tracker - Register</title>
    <style>
        /* Colorful Gradient Design */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
    body {
    position: relative;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    overflow: hidden;

}

/* Blurred background layer */
body::before {
    content: "";
    position: absolute;
    top: 0;
   
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('images/bg2.jpg');
    background-size: cover;
    background-position: center;
    filter: blur(3px);   /* adjust blur level */
    z-index: -1;         /* keep it behind content */
    transform: scale(1.1); /* prevents edge clipping after blur */
}

        
        .container {
            width: 100%;
            max-width: 50%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .header {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .form-container {
            padding: 40px 30px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success { 
            background: linear-gradient(90deg, #4CAF50, #45a049);
            color: white;
        }
        
        .error { 
            background: linear-gradient(90deg, #ff416c, #ff4b2b);
            color: white;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4facfe;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.2);
            transform: translateY(-2px);
        }
        
        .form-group input:hover {
            border-color: #4facfe;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, #ff9a9e 0%, #fad0c4 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: linear-gradient(90deg, #fad0c4 0%, #ff9a9e 100%);
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(255, 154, 158, 0.4);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }
        
        .login-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            color: #ff9a9e;
            text-decoration: underline;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                max-width: 90%;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .form-container {
                padding: 30px 20px;
            }
            
            .btn {
                padding: 14px;
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                max-width: 95%;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px 15px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .form-group input {
                padding: 12px 15px;
                font-size: 0.95rem;
            }
        }
        
        /* Decorative Elements */
        .fitness-icons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            opacity: 0.7;
        }
        
        .fitness-icons span {
            font-size: 2rem;
            animation: float 3s ease-in-out infinite;
        }
        
        .fitness-icons span:nth-child(1) { animation-delay: 0s; }
        .fitness-icons span:nth-child(2) { animation-delay: 0.5s; }
        .fitness-icons span:nth-child(3) { animation-delay: 1s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏃‍♂️ FitTrack Pro</h1>
            <p>Start Your Fitness Journey Today!</p>
            <div class="fitness-icons">
                <span>💪</span>
                <span>🏃‍♀️</span>
                <span>🥗</span>
            </div>
        </div>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">👤 Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" 
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="username">👤 Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                           placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">📧 Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                           placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">🔑 Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter password (min 6 characters)" required>
                </div>
                
                <button type="submit" class="btn">🎯 Create Account</button>
                
                <div class="login-link">
                    Already have an account? <a href="index.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Simple form validation animation
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>