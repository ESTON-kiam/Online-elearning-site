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

$courses_query = "
    SELECT c.id, c.title, c.description, c.YearOfStudent, c.price, i.name AS instructor_name
    FROM courses c
    JOIN course_instructors ci ON c.id = ci.course_id
    JOIN instructors i ON ci.instructor_id = i.id
    WHERE c.is_active = 1";
$courses_result = $conn->query($courses_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --sidebar-bg: #2c3e50;
            --sidebar-text: #ecf0f1;
            --text-color: #333;
            --bg-color: #f4f6f7;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
        }

        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            transition: width 0.3s ease;
            z-index: 1000;
            overflow-x: hidden;
        }

        .sidebar-mini {
            width: 70px;
        }

        .sidebar-full {
            width: 250px;
        }

        .sidebar-content {
            padding-top: 60px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .sidebar-link {
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 15px;
            display: flex;
            align-items: center;
            transition: background-color 0.3s ease;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-link i {
            margin-right: 15px;
            font-size: 1.2rem;
        }

        .sidebar-link span {
            white-space: nowrap;
        }

        .sidebar-mini .sidebar-link span {
            display: none;
        }

        .toggle-sidebar {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1050;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
        }

        .main-content {
            transition: margin-left 0.3s ease;
            margin-left: 70px;
            width: calc(100% - 70px);
            padding: 20px;
        }

        .main-content-full {
            margin-left: 250px;
            width: calc(100% - 250px);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .course-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .course-card:hover {
            transform: scale(1.02);
        }

        .dashboard-section {
            padding: 2rem 0;
        }

        .btn-custom {
            background-color: var(--primary-color);
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #2980b9;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .toggle-sidebar {
                display: block;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar Toggle Button -->
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
                        <a href="edit_profile.php" class="btn btn-custom mt-3">Edit Profile</a>
                    </div>
                </div>

                <div class="col-md-8">
                    <h2 class="mb-4">Available Courses</h2>
                    <?php if ($courses_result->num_rows > 0): ?>
                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <div class="course-card p-3 mb-3">
                                <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                <p><?php echo htmlspecialchars($course['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($course['YearOfStudent']); ?> Year</span>
                                    <span class="text-muted">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></span>
                                    <span class="fw-bold">KES<?php echo number_format($course['price'], 2); ?></span>
                        </div>
    
    <a href="enrollment.php" class="btn btn-custom mt-3">Enroll Now</a>
</div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">No courses available at the moment.</div>
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
        });
    </script>
</body>

</html>
