<?php
session_name('super_admin');
session_start();
require_once 'include/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin');
    exit();
}

$instructors_query = "SELECT COUNT(*) as total_instructors FROM instructors";
$courses_query = "SELECT COUNT(*) as total_courses FROM courses";
$allocated_courses_query = "SELECT COUNT(*) as total_allocated FROM course_instructors";
$students_query = "SELECT COUNT(*) as total_students FROM students";
$pending_allocation_query = "SELECT COUNT(*) AS pending_allocation FROM courses WHERE allocation_status = 'pending'";


$instructors_result = mysqli_query($conn, $instructors_query);
$courses_result = mysqli_query($conn, $courses_query);
$allocated_result = mysqli_query($conn, $allocated_courses_query);
$students_result = mysqli_query($conn, $students_query);
$pending_result = mysqli_query($conn, $pending_allocation_query);


$total_instructors = mysqli_fetch_assoc($instructors_result)['total_instructors'];
$total_courses = mysqli_fetch_assoc($courses_result)['total_courses'];
$total_allocated = mysqli_fetch_assoc($allocated_result)['total_allocated'];
$total_students = mysqli_fetch_assoc($students_result)['total_students'];
$pending_allocation = mysqli_fetch_assoc($pending_result)['pending_allocation'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/dash.css">
</head>
<body>
  
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

    <div class="dashboard-container">
        
        <aside class="sidebar">
            <nav>
                <ul>
                    <li>
                        <a href="dashboard.php" >Dashboard</a>
                        <a href="Register.php" > Add Super Admin</a>
                        <a href="#" class="dropdown-toggle">Courses</a>
                        <ul class="dropdown-menu">
                            <li><a href="add_course.php">Add New Course</a></li>
                            <li><a href="manage_courses.php">Manage Courses</a></li>
                        </ul>
                    </li>
                    
                    <li>
                    
                        <a href="#" class="dropdown-toggle">Instructors</a>
                        <ul class="dropdown-menu">
                            <li><a href="add_instructor.php">Add New Instructor</a></li>
                            <li><a href="manage_instructors.php">Manage Instructors</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="allocate_courses.php">Allocate Courses</a>
                    </li>
                </ul>
            </nav>
        </aside>

        
        <main class="main-content">
            <div class="dashboard-cards">
                <div class="card instructors">
                    <h3>Total Instructors</h3>
                    <p><?php echo $total_instructors; ?></p>
                </div>
                <div class="card courses">
                    <h3>Total Courses</h3>
                    <p><?php echo $total_courses; ?></p>
                </div>
                <div class="card allocated-courses">
                    <h3>Allocated Courses</h3>
                    <p><?php echo $total_allocated; ?></p>
                </div>
                <div class="card allocated-courses">
                    <h3>No Of Students</h3>
                    <p><?php echo $total_students; ?></p>
                </div>
                <div class="card allocated-courses">
                    <h3>Pending Allocation Courses</h3>
                    <p><?php echo $pending_allocation; ?></p>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>