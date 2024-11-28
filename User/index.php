<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-lock"></i>
        </div>
        <h2 class="login-title">Secure Student Login Portal</h2>
        <form class="login-form" action="process_login.php" method="POST">
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
</body>
</html>