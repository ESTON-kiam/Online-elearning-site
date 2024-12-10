<?php
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';

$student_id = $_SESSION['student_id'];


$courses_query = "SELECT 
                    c.id AS course_id, 
                    c.title AS course_title, 
                    i.name AS instructor_name,
                    COALESCE(
                        (SELECT SUM(ss.grade) 
                         FROM student_submissions ss 
                         JOIN course_activities ca ON ss.activity_id = ca.id 
                         WHERE ss.student_id = e.student_id AND ss.course_id = c.id),
                        0
                    ) AS earned_points,
                    COALESCE(
                        (SELECT SUM(ca.max_points) 
                         FROM course_activities ca 
                         WHERE ca.course_id = c.id),
                        0
                    ) AS total_points
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                JOIN course_instructors ci ON c.id = ci.course_id
                JOIN instructors i ON ci.instructor_id = i.id
                WHERE e.student_id = ?
                ORDER BY c.title";

$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courses_result = $stmt->get_result();


function calculateLetterGrade($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a4a9e;
            --secondary-color: #6c757d;
            --light-bg: #f4f6f9;
        }
        body {
            background-color: var(--light-bg);
            font-family: 'Inter', sans-serif;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--primary-color);
            color: white;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card-custom {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .grade-header {
            background: linear-gradient(135deg, var(--primary-color), #6a5acd);
            color: white;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        .grade-percentage {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .grade-letter {
            font-size: 1.5rem;
            color: var(--secondary-color);
        }
        .course-grade-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .course-grade-card:hover {
            transform: scale(1.05);
        }
        @media (max-width: 992px) {
            .sidebar {
                position: static;
                height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
           
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="d-flex flex-column h-100">
                    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <span class="fs-4">Student Portal</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="bi bi-house me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="dashboard.php" class="nav-link">
                                <i class="bi bi-journal-text me-2"></i>
                                Course Resources
                            </a>
                        </li>
                        <li>
                            <a href="grades.php" class="nav-link active">
                                <i class="bi bi-award me-2"></i>
                                Grades
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

           
            <div class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="grade-header text-center">
                    <div class="container">
                        <h1>Your Grades</h1>
                        <p class="lead">Comprehensive view of your academic performance</p>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row">
                        <?php 
                        $total_overall_earned = 0;
                        $total_overall_points = 0;
                        $course_count = 0;

                        while ($course = $courses_result->fetch_assoc()): 
                            $course_count++;
                            $course_percentage = $course['total_points'] > 0 
                                ? round(($course['earned_points'] / $course['total_points']) * 100, 2) 
                                : 0;
                            $letter_grade = calculateLetterGrade($course_percentage);

                           
                            $total_overall_earned += $course['earned_points'];
                            $total_overall_points += $course['total_points'];
                        ?>
                            <div class="col-md-4 mb-4">
                                <div class="course-grade-card">
                                    <h3><?php echo htmlspecialchars($course['course_title']); ?></h3>
                                    <p class="text-muted">
                                        <i class="bi bi-person-fill me-2"></i>
                                        <?php echo htmlspecialchars($course['instructor_name']); ?>
                                    </p>
                                    <div class="grade-percentage">
                                        <?php echo $course_percentage; ?>%
                                    </div>
                                    <div class="grade-letter">
                                        <?php echo $letter_grade; ?>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <?php echo $course['earned_points'] . '/' . $course['total_points']; ?> points
                                        </small>
                                    </div>
                                    <a href="resources.php?course_id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-primary btn-sm mt-3">
                                        View Course Details
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php if ($course_count == 0): ?>
                            <div class="col-12">
                                <div class="alert alert-info text-center" role="alert">
                                    You are not currently enrolled in any courses.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($course_count > 0): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card card-custom">
                                    <div class="card-header bg-primary text-white">
                                        <h2 class="mb-0">Overall Academic Performance</h2>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php 
                                        $overall_percentage = $total_overall_points > 0 
                                            ? round(($total_overall_earned / $total_overall_points) * 100, 2) 
                                            : 0;
                                        $overall_letter_grade = calculateLetterGrade($overall_percentage);
                                        ?>
                                        <h3 class="grade-percentage"><?php echo $overall_percentage; ?>%</h3>
                                        <h4 class="grade-letter"><?php echo $overall_letter_grade; ?></h4>
                                        <p class="lead">
                                            Total Points: 
                                            <?php echo round($total_overall_earned, 2) . '/' . round($total_overall_points, 2); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>