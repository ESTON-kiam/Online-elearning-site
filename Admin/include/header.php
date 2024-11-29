<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/dash.css">
</head>
<header>
        <div class="logo">
            <h1>E-Learning Platform</h1>
        </div>
        <div class="profile-dropdown">
            <div class="profile-icon">
                <img src="<?php echo $_SESSION['profile_image'] ?? 'assets/images/default-profile.png'; ?>" alt="Profile">
                <span><?php echo $_SESSION['admin_username']; ?></span>

            </div>
            <div class="dropdown-content">
                <a href="profile.php">My Profile</a>
                <a href="change_password.php">Change Password</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </header>
    <script src="assets/js/dashboard.js"></script>