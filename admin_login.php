<?php
/**
 * Admin login page
 */

// Include shared functions
require_once 'functions.php';

// Start session
startSession();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

// Process login
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = $error_messages['invalid_token'];
    } else {
        // Get and sanitize input
        $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Check credentials
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            
            // Log login action
            logAdminAction($username, 'login', 'Admin login successful');
            
            // Redirect to admin panel
            header('Location: admin.php');
            exit;
        } else {
            $error = $error_messages['admin_invalid'];
            
            // Log failed login attempt
            logAdminAction('unknown', 'login_failed', 'Failed login attempt with username: ' . $username);
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول المدير - منصة النشر العربية</title>
    <!-- IBM Plex Sans Arabic font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #ecf0f1;
            --accent-color: #3498db;
            --text-color: #333;
            --light-gray: #f9f9f9;
            --border-color: #ddd;
            --error-color: #e74c3c;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--secondary-color);
            padding: 0;
            margin: 0;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .container {
            max-width: 500px;
            margin: 5rem auto;
            padding: 0 1rem;
        }
        
        .login-form {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            font-size: 1rem;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        
        .btn {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .error-message {
            background-color: var(--error-color);
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--accent-color);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 3rem auto;
            }
            
            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>تسجيل دخول المدير</h1>
    </header>
    
    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="login-form">
            <form method="post" action="admin_login.php">
                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <button type="submit" class="btn">تسجيل الدخول</button>
            </form>
            
            <a href="index.html" class="back-link">العودة للصفحة الرئيسية</a>
        </div>
    </div>
    
    <footer>
        <p>منصة النشر العربية © 2025</p>
    </footer>
</body>
</html>