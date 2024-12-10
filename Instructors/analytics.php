<?php
session_name('instructor_session');
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location:http://localhost:8000/instructors");
    exit();
}

require_once 'include/database.php';

$instructor_id = $_SESSION['instructor_id'];


$courses_query = "
    SELECT 
        c.id, 
        c.title 
    FROM 
        courses c
    INNER JOIN 
        course_instructors ci ON c.id = ci.course_id
    WHERE 
        ci.instructor_id = ?
";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$courses_result = $stmt->get_result();

$selected_course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 
    ($courses_result->num_rows > 0 ? $courses_result->fetch_assoc()['id'] : null);


if ($selected_course_id) {
    $courses_result->data_seek(0);
}


$students_query = "
    SELECT 
        s.id AS student_id,
        s.full_name AS student_name,
        s.email AS student_email
    FROM 
        students s
    INNER JOIN 
        enrollments e ON s.id = e.student_id
    WHERE 
        e.course_id = ?
";

$student_results = null;
$course_stats = null;

if ($selected_course_id) {
    $stmt = $conn->prepare($students_query);
    $stmt->bind_param("i", $selected_course_id);
    $stmt->execute();
    $student_results = $stmt->get_result();

    
    $course_stats_query = "
        SELECT 
            COUNT(DISTINCT s.id) AS total_students,
            c.title AS course_title,
            c.YearOfStudent AS student_year,
            c.is_active AS course_status
        FROM 
            courses c
        LEFT JOIN 
            enrollments e ON c.id = e.course_id
        LEFT JOIN 
            students s ON e.student_id = s.id
        WHERE 
            c.id = ?
    ";
    $stmt = $conn->prepare($course_stats_query);
    $stmt->bind_param("i", $selected_course_id);
    $stmt->execute();
    $course_stats = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Analytics</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dish.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
           
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="edit_profile.php">
                                <i class="fas fa-user-edit me-2"></i> Edit Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="analytics.php">
                                <i class="fas fa-chart-line me-2"></i> Course Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 dashboard-content">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Course Analytics</h1>
                </div>

                <div class="row">
                    <div class="col-12 mb-4">
                        <form method="GET" action="analytics.php" class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <select name="course_id" class="form-select" onchange="this.form.submit()">
                                        <?php 
                                        $courses_result->data_seek(0);
                                        while($course = $courses_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($selected_course_id == $course['id'] ? 'selected' : ''); ?>>
                                                <?php echo htmlspecialchars($course['title']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </form>

                        <?php if ($selected_course_id && $course_stats): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title">Course Overview</h5>
                                            <ul class="list-unstyled">
                                                <li><strong>Course:</strong> <?php echo htmlspecialchars($course_stats['course_title']); ?></li>
                                                <li><strong>Total Students:</strong> <?php echo $course_stats['total_students']; ?></li>
                                                <li><strong>Year of Study:</strong> <?php echo htmlspecialchars($course_stats['student_year']); ?></li>
                                                <li><strong>Course Status:</strong> 
                                                    <span class="badge <?php echo $course_stats['course_status'] ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo $course_stats['course_status'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Enrolled Students</h5>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Student Name</th>
                                                            <th>Email</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        if ($student_results->num_rows > 0):
                                                            while($student = $student_results->fetch_assoc()): 
                                                        ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($student['student_email']); ?></td>
                                                            </tr>
                                                        <?php 
                                                            endwhile; 
                                                        else:
                                                        ?>
                                                            <tr>
                                                                <td colspan="2" class="text-center">No students enrolled in this course</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (!$selected_course_id): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No courses available. Please contact the admin.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>