<?php
session_name('instructor_session');
session_start();

require_once 'include/database.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);


const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_DURATION = 15 * 60;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $db = new DatabaseConnection();
        $conn = $db->getConnection();

        if (!$conn) {
            throw new Exception("Database connection failed");
        }

       
        $login_input = $db->sanitizeInput($_POST['username']);
        $password = $_POST['password'];

       
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $cache_key = "login_attempts_{$ip_address}";

        
        if (isset($_SESSION[$cache_key]['lockout_until']) && 
            time() < $_SESSION[$cache_key]['lockout_until']) {
            error_log("Login attempt during lockout - IP: {$ip_address}");
            $_SESSION['login_error'] = "Too many failed attempts. Please try again later.";
            header("Location: index.php");
            exit();
        }

      
        $stmt = $conn->prepare("
            SELECT id, username, email, password, last_login, profile_image 
            FROM instructors 
            WHERE (username = ? OR email = ?) AND is_active = 1
        ");

        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $login_input, $login_input);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

         
            if (!empty($user['password']) &&
                (strlen($user['password']) > 20) &&
                password_verify($password, $user['password'])) {
                
                
                unset($_SESSION[$cache_key]);
                unset($_SESSION['login_error']);

             
                $_SESSION['instructor_logged_in'] = true;
                $_SESSION['instructor_id'] = $user['id'];
                $_SESSION['instructor_username'] = $user['username'];
                $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.png';

              
                $update_stmt = $conn->prepare("UPDATE instructors SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();

                error_log("Successful Login - Redirecting User ID: " . $user['id']);
                header("Location: http://localhost:8000/instructors/dashboard.php");
                exit();
            } else {
                
                error_log("Password verification failed for: " . $login_input);
                
                
                if (!isset($_SESSION[$cache_key])) {
                    $_SESSION[$cache_key] = [
                        'attempts' => 1,
                        'first_attempt' => time()
                    ];
                } else {
                    $_SESSION[$cache_key]['attempts']++;
                }

                
                if ($_SESSION[$cache_key]['attempts'] >= MAX_LOGIN_ATTEMPTS) {
                    $_SESSION[$cache_key]['lockout_until'] = time() + LOCKOUT_DURATION;
                    error_log("IP Locked Out - Address: {$ip_address}");
                }

                $_SESSION['login_error'] = "Invalid credentials. Please check your password.";
                header("Location: /instructors");
                exit();
            }
        } else {
            error_log("No user found for: " . $login_input);
            $_SESSION['login_error'] = "User not found. Please check your credentials.";
            header("Location: /instructors");
            exit();
        }
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        $_SESSION['login_error'] = "An unexpected error occurred. Please try again.";
        header("Location: /instructors");
        exit();
    } finally {
        
        if (isset($stmt)) $stmt->close();
        if (isset($update_stmt)) $update_stmt->close();
        if (isset($db)) $db->closeConnection();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructors Login Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-lock"></i>
        </div>
        <h2 class="login-title">Secure Instructor Login Portal</h2>
        <form class="login-form" action="" method="POST">
            <input type="text" name="username" placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="login-button">Log In</button>
            <div class="login-extras">
                <label>
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="#" style="color: var(--secondary-color);">Forgot Password?</a>
            </div>
        </form>    
        <div class="social-login">
    <a href="http://localhost:8000/" class="social-button staff-login">
        <i class="fas fa-user-tie" style="margin-right: 10px;"></i>Return TO Home
    </a>
</div>
        </div> 
    </div>
</body>
</html>