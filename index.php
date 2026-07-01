<?php
session_start();
// login.php - User Login Page
require_once 'conn.php';



$message = "";
$message_type = "";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $message = "Please enter both username and password!";
        $message_type = "error";
    } else {
        // Check user credentials (NO HASHING for simplicity)
        $sql = "SELECT user_id, username, email, full_name FROM users 
                WHERE username = '$username' AND password_hash = '$password'";
        $result = $conn->query($sql);
        
        if ($result->num_rows == 1) {
            // Login successful
            $user = $result->fetch_assoc();
            
            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            // Update last login time
            $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id'];
            $conn->query($update_sql);
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid username or password!";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Tracker - Login</title>
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
    background-image: url('images/bg1.jpg');
    background-size: cover;
    background-position: center;
    filter: blur(3px);   /* adjust blur level */
    z-index: -1;         /* keep it behind content */
    transform: scale(1.1); /* prevents edge clipping after blur */
}

        

        
        .container {
            width: 100%;
            max-width: 40%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .header {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
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
        
        .error { 
            background: linear-gradient(90deg, #ff416c, #ff4b2b);
            color: white;
        }
        
        .welcome {
            background: linear-gradient(90deg, #4CAF50, #45a049);
            color: white;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
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
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .form-group input:hover {
            border-color: #667eea;
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #764ba2;
            transform: translateY(-50%) scale(1.2);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(90deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .links-container {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 15px;
            border-radius: 25px;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .link:hover {
            color: white;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
            
            .links-container {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .link {
                width: 100%;
                text-align: center;
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
        .fitness-icons span:nth-child(4) { animation-delay: 1.5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25% { transform: translateY(-10px) rotate(5deg); }
            75% { transform: translateY(5px) rotate(-5deg); }
        }
        
        /* Demo Accounts */
        .demo-accounts {
            background: linear-gradient(90deg, #f093fb, #f5576c);
            color: white;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .demo-accounts h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .demo-accounts p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏋️‍♀️ Welcome Back!</h1>
            <p>Track Your Fitness Journey Continues</p>
            <div class="fitness-icons">
                <span>🔥</span>
                <span>⚡</span>
                <span>💥</span>
                <span>🌟</span>
            </div>
        </div>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message error">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
                <div class="message welcome">
                    ✅ Registration successful! Please login with your credentials.
                </div>
            <?php endif; ?>
            
            <div class="demo-accounts">
                <h3>Demo Accounts (for testing)</h3>
                <p>👤 Username: john_doe | Password: 123456</p>
                <p>👤 Username: jane_smith | Password: 123456</p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">👤 Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                           placeholder="Enter your username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">🔑 Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="btn">🚀 Login to Dashboard</button>
                
                <div class="links-container">
                    <a href="register.php" class="link">📝 Create New Account</a>
        
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🔒';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
        
        // Add some fun animations
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            const container = document.querySelector('.container');
            
            // Bounce animation on load
            container.style.animation = 'none';
            setTimeout(() => {
                container.style.animation = 'bounceIn 1s ease';
            }, 100);
            
            // Add styles for bounce animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes bounceIn {
                    0% { transform: scale(0.3); opacity: 0; }
                    50% { transform: scale(1.05); }
                    70% { transform: scale(0.9); }
                    100% { transform: scale(1); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            // Input focus effects
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.background = 'linear-gradient(90deg, #ffffff, #f8f9fa)';
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.background = '#f8f9fa';
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
            
            // Demo account auto-fill (for testing)
            const demoAccounts = document.querySelectorAll('.demo-accounts p');
            demoAccounts.forEach(p => {
                p.addEventListener('click', function() {
                    const text = this.textContent;
                    const parts = text.split('|');
                    if (parts.length === 2) {
                        const usernamePart = parts[0].split(':')[1].trim();
                        const passwordPart = parts[1].split(':')[1].trim();
                        
                        document.getElementById('username').value = usernamePart;
                        document.getElementById('password').value = passwordPart;
                        
                        // Show confirmation
                        const message = document.createElement('div');
                        message.className = 'message success';
                        message.textContent = 'Demo credentials filled! Click Login.';
                        message.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1000;';
                        document.body.appendChild(message);
                        
                        setTimeout(() => message.remove(), 3000);
                    }
                });
            });
        });
    </script>
</body>
</html>