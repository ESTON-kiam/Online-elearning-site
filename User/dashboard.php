<?php
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';

$student_id = $_SESSION['student_id'];
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

$enrolled_courses_query = "
    SELECT c.id, c.title, c.description, c.YearOfStudent, c.price, i.name AS instructor_name
    FROM courses c
    JOIN enrollments e ON c.id = e.course_id
    JOIN course_instructors ci ON c.id = ci.course_id
    JOIN instructors i ON ci.instructor_id = i.id
    WHERE e.student_id = ? AND c.is_active = 1";
$stmt = $conn->prepare($enrolled_courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolled_courses_result = $stmt->get_result();

$available_courses_query = "
    SELECT c.id, c.title, c.description, c.YearOfStudent, c.price, i.name AS instructor_name
    FROM courses c
    JOIN course_instructors ci ON c.id = ci.course_id
    JOIN instructors i ON ci.instructor_id = i.id
    WHERE c.is_active = 1 AND c.id NOT IN (
        SELECT course_id FROM enrollments WHERE student_id = ?
    )";
$stmt = $conn->prepare($available_courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$available_courses_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/dash.css" rel="stylesheet">
    
</head>

<body>
   
    <?php if (isset($_SESSION['login_message'])): ?>
        <div id="login-message" class="alert alert-success">
            <?php 
            echo htmlspecialchars($_SESSION['login_message']); 
            unset($_SESSION['login_message']); 
            ?>
        </div>
    <?php endif; ?>

    <button id="sidebarToggle" class="toggle-sidebar">
        <i class="bi bi-list"></i>
    </button>

    <div id="sidebar" class="sidebar sidebar-mini">
        <div class="sidebar-content">
            <a href="#" class="sidebar-link active">
                <i class="bi bi-house"></i>
                <span>Dashboard</span>
            </a>
            <a href="courses.php" class="sidebar-link">
                <i class="bi bi-book"></i>
                <span>Courses</span>
            </a>
            <a href="profile.php" class="sidebar-link">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </a>
            <a href="grades.php" class="sidebar-link">
                <i class="bi bi-clipboard-data"></i>
                <span>Grades</span>
            </a>
            <a href="enrollment.php" class="sidebar-link">
                <i class="bi bi-journal-plus"></i>
                <span>Enrollment</span>
            </a>
            <div class="mt-auto">
                <a href="logout.php" class="sidebar-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <div id="mainContent" class="main-content">
        <header class="dashboard-header">
            <div class="container">
                <h1>Welcome, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                <p>Your Personal Learning Dashboard</p>
            </div>
        </header>

        <div class="container dashboard-section">
            <div class="row">
                <div class="col-md-4">
                    <div class="profile-card">
                        <h3 class="mb-4">Profile Details</h3>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone_number'] ?? 'Not provided'); ?></p>
                        <a href="profile.php" class="btn btn-custom mt-3">Edit Profile</a>
                    </div>
                </div>

                <div class="col-md-8">
                    <?php if ($enrolled_courses_result->num_rows > 0): ?>
                        <h2 class="mb-4">My Courses</h2>
                        <?php while ($course = $enrolled_courses_result->fetch_assoc()): ?>
                            <a href="resources.php?course_id=<?php echo $course['id']; ?>" class="text-decoration-none">
                                <div class="course-card p-3 mb-3">
                                    <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($course['YearOfStudent']); ?> Year</span>
                                        <span class="text-muted">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                        <span class="fw-bold">KES<?php echo number_format($course['price'], 2); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <?php if ($available_courses_result->num_rows > 0): ?>
                            <div class="alert alert-info mb-4">
                                <p>You are not currently enrolled in any courses.</p>
                                <a href="enrollment.php" class="btn btn-primary">Enroll in a Course</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p>No courses are currently available for enrollment.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');

            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('sidebar-mini');
                mainContent.classList.toggle('main-content-full');
            });

            const loginMessage = document.getElementById('login-message');
            if (loginMessage) {
                loginMessage.style.display = 'block';
                
                
                setTimeout(function() {
                    loginMessage.style.display = 'none';
                }, 10000);
            }
        });
    </script>
</body>

</html>