<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Academy - Super Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .sidebar {
            height: 100vh;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding-top: 60px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            transition: background-color 0.3s ease;
        }
        .sidebar a:hover {
            background-color: #34495e;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .header {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            z-index: 1000;
            height: 60px;
        }
        .profile-dropdown img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <nav class="header navbar navbar-expand-lg navbar-light bg-white">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="path/to/logo.png" alt="E-Learning Academy" width="50" height="50" class="d-inline-block align-text-top">
                E-Learning Academy
            </a>
            
            <!-- Profile Dropdown -->
            <div class="ms-auto me-3">
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="path/to/admin-profile.jpg" alt="Admin Profile" class="me-2">
                        Super Admin
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i>View Profile</a></li>
                        <li><a class="dropdown-item" href="edit-profile.php">
                            <i class="fas fa-edit me-2"></i>Edit Profile</a></li>
                        <li><a class="dropdown-item" href="change-password.php">
                            <i class="fas fa-key me-2"></i>Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <nav>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a href="courses.php" class="nav-link">
                <i class="fas fa-book me-2"></i>Courses Management
            </a>
            <a href="instructors.php" class="nav-link">
                <i class="fas fa-chalkboard-teacher me-2"></i>Instructors Management
            </a>
            <a href="students.php" class="nav-link">
                <i class="fas fa-user-graduate me-2"></i>Students Management
            </a>
            <a href="enrollments.php" class="nav-link">
                <i class="fas fa-graduation-cap me-2"></i>Enrollments
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar me-2"></i>Reports
            </a>
            <a href="system-settings.php" class="nav-link">
                <i class="fas fa-cogs me-2"></i>System Settings
            </a>
        </nav>
    </div>