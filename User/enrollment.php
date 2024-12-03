<?php
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';

$student_id = $_SESSION['student_id'];

// Check if the student is already enrolled in a course
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Check if the student is already enrolled
    $check_enrollment_query = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = $conn->prepare($check_enrollment_query);
    $stmt->bind_param("ii", $student_id, $course_id);
    $stmt->execute();
    $enrollment_result = $stmt->get_result();

    if ($enrollment_result->num_rows > 0) {
        // Student is already enrolled
        $message = "You are already enrolled in this course!";
    } else {
        // Enroll the student in the course
        $enrollment_query = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
        $stmt = $conn->prepare($enrollment_query);
        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Successfully enrolled in the course!";
        } else {
            $message = "There was an error enrolling in the course.";
        }
    }
} else {
    $message = "No course selected.";
}

// Fetch student details
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

// Fetch available courses
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
       /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

/* Header Styling */
.dashboard-header {
    background-color: #343a40;
    color: #fff;
    padding: 20px 0;
}

.dashboard-header h1 {
    font-size: 2.5rem;
    margin: 0;
}

.dashboard-header p {
    font-size: 1.25rem;
    margin: 5px 0;
}

/* Main Content */
.main-content {
    padding: 20px;
}

/* Dashboard Section */
.dashboard-section {
    margin-top: 30px;
}

/* Course Card Styles */
.course-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.course-card:hover {
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-5px);
}

.course-card h4 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 15px;
}

.course-card p {
    color: #555;
    font-size: 1rem;
    margin-bottom: 15px;
}

.course-card .d-flex {
    margin-top: 10px;
}

.course-card .badge {
    font-size: 0.9rem;
    background-color: #007bff;
    color: white;
}

.course-card .text-muted {
    font-size: 0.9rem;
}

.course-card .fw-bold {
    font-size: 1.1rem;
}

.course-card .btn-custom {
    background-color: #28a745;
    color: white;
    font-weight: bold;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.course-card .btn-custom:hover {
    background-color: #218838;
}

/* Alert Message */
.alert-info {
    background-color: #17a2b8;
    color: white;
    font-weight: bold;
}

/* Responsive Styling */
@media (max-width: 768px) {
    .dashboard-header h1 {
        font-size: 2rem;
    }

    .dashboard-header p {
        font-size: 1rem;
    }

    .course-card {
        margin-bottom: 15px;
    }

    .course-card .btn-custom {
        width: 100%;
    }
}

    </style>
</head>

<body>
    <!-- Sidebar and other content -->
    <div id="mainContent" class="main-content">
        <header class="dashboard-header">
            <div class="container">
                <h1>Welcome, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                <p>Your Personal Learning Dashboard</p>
            </div>
        </header>

        <div class="container dashboard-section">
            <?php if (isset($message)): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
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
                                <a href="enrollment.php?course_id=<?php echo $course['id']; ?>" class="btn btn-custom mt-3">Enroll Now</a>
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
</body>

</html>
