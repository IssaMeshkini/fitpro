# FitPro - Fitness Tracker Web Application

A PHP and MySQL-based fitness tracking web application to manage daily activities, meals, step counts, and fitness goals.

---

## 📋 Prerequisites

- **XAMPP** (Apache & MySQL)
- **MySQL Workbench**
- Web Browser (Chrome, Edge, Firefox, etc.)

---

## 🚀 Installation & Setup Guide

### Step 1: Place Project in XAMPP `htdocs`
1. Copy the project folder into your XAMPP `htdocs` directory:
   ```text
   C:\xampp\htdocs\fitpro
   ```
2. Open the **XAMPP Control Panel**.
3. Start both **Apache** and **MySQL** services.

---

### Step 2: Database Setup using MySQL Workbench
1. Open **MySQL Workbench** and connect to your local MySQL server (default user: `root`, port: `3306`).
2. Open a new SQL query tab and create the database:
   ```sql
   CREATE DATABASE IF NOT EXISTS fitness_tracker_db;
   USE fitness_tracker_db;
   ```
3. Open and run the SQL script:
   - Go to **File** > **Open SQL Script...**
   - Select `database.sql` from the project folder.
   - Ensure `fitness_tracker_db` is selected, then click the **Execute (⚡ Lightning Bolt)** icon.

---

### Step 3: Verify Database Connection
Check [`conn.php`](conn.php) to ensure credentials match your MySQL setup:

```php
$host = "localhost";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password is blank
$database = "fitness_tracker_db";
```

---

### Step 4: Run the Application
1. Open your web browser and visit:
   ```text
   http://localhost/fitpro
   ```
   *(Replace `fitpro` with your folder name if different)*

---

## 🔑 Demo Login Credentials

You can use any of the pre-loaded demo accounts or register a new one:

| Username | Password | Email |
| :--- | :--- | :--- |
| `john` | `John@123` | john@gmail.com |
| `Sohail123` | `Sohail@123` | sohail123@gmail.com |
| `carey` | `Carey@123` | carey@gmail.com |
| `root` | `123456` | jeny@gmail.com |

---

## 📁 Key Files
- [`index.php`](index.php) – Login page (entry point)
- [`register.php`](register.php) – User registration
- [`dashboard.php`](dashboard.php) – Main user dashboard
- [`activities.php`](activities.php) – Workout & activity logging
- [`food.php`](food.php) – Meal & nutrition tracking
- [`log_steps.php`](log_steps.php) – Daily step count logging
- [`goals.php`](goals.php) – Target goals management
- [`profile.php`](profile.php) – User profile & settings
- [`database.sql`](database.sql) – Database schema & sample data
