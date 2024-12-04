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


function calculateGradePercentage($obtained_marks, $total_marks) {
    return ($total_marks > 0) ? round(($obtained_marks / $total_marks) * 100, 2) : 0;
}


$courses_query = "
    SELECT 
        c.id AS course_id, 
        c.title AS course_title,
        SUM(ss.obtained_marks) AS total_obtained_marks,
        SUM(ca.total_marks) AS total_course_marks
    FROM 
        courses c
    JOIN 
        enrollments e ON c.id = e.course_id
    JOIN 
        course_activities ca ON c.id = ca.course_id
    LEFT JOIN 
        student_submissions ss ON ca.id = ss.activity_id AND e.student_id = ss.student_id
    WHERE 
        e.student_id = ?
    GROUP BY 
        c.id, c.title
";

$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$courses_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .grades-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem 0;
            border-radius: 0 0 20px 20px;
        }
        .course-card {
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .progress { height: 20px; }
    </style>
</head>
<body>


<div class="grades-header text-center mb-4">
    <div class="container">
        <h1>My Academic Performance</h1>
        <p class="lead">Comprehensive Grade Overview</p>
    </div>
</div>

<div class="container">
    <?php if ($courses_result->num_rows == 0): ?>
        <div class="alert alert-info">
            You are not currently enrolled in any courses.
        </div>
    <?php else: ?>
        <?php while ($course = $courses_result->fetch_assoc()): ?>
            <div class="card course-card">
                <div class="card-header bg-primary text-white">
                    <h3><?php echo htmlspecialchars($course['course_title']); ?></h3>
                </div>
                <div class="card-body">
                    <?php 
                    $overall_percentage = calculateGradePercentage(
                        $course['total_obtained_marks'], 
                        $course['total_course_marks']
                    ); 
                    ?>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Total Marks Obtained:</strong> 
                                <?php echo number_format($course['total_obtained_marks'], 2); ?>
                            </p>
                            <p><strong>Total Course Marks:</strong> 
                                <?php echo number_format($course['total_course_marks'], 2); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Overall Performance:</strong></p>
                            <div class="progress">
                                <div 
                                    class="progress-bar <?php 
                                        echo $overall_percentage >= 70 ? 'bg-success' : 
                                            ($overall_percentage >= 50 ? 'bg-warning' : 'bg-danger'); 
                                    ?>" 
                                    role="progressbar" 
                                    style="width: <?php echo $overall_percentage; ?>%"
                                    aria-valuenow="<?php echo $overall_percentage; ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100"
                                >
                                    <?php echo $overall_percentage; ?>%
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="mt-3">
                        <h5>Activity Breakdown</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Marks Obtained</th>
                                    <th>Total Marks</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                
                                $activities_query = "
                                    SELECT 
                                        ca.title,
                                        MAX(ss.obtained_marks) AS max_marks,
                                        ca.total_marks
                                    FROM 
                                        course_activities ca
                                    LEFT JOIN 
                                        student_submissions ss ON ca.id = ss.activity_id 
                                        AND ss.student_id = ?
                                    WHERE 
                                        ca.course_id = ?
                                    GROUP BY 
                                        ca.id, ca.title, ca.total_marks
                                ";
                                $activity_stmt = $conn->prepare($activities_query);
                                $activity_stmt->bind_param("ii", $student_id, $course['course_id']);
                                $activity_stmt->execute();
                                $activities_result = $activity_stmt->get_result();

                                while ($activity = $activities_result->fetch_assoc()):
                                    $activity_percentage = calculateGradePercentage(
                                        $activity['max_marks'], 
                                        $activity['total_marks']
                                    );
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                                        <td><?php echo number_format($activity['max_marks'] ?? 0, 2); ?></td>
                                        <td><?php echo number_format($activity['total_marks'], 2); ?></td>
                                        <td>
                                            <div class="progress">
                                                <div 
                                                    class="progress-bar <?php 
                                                        echo $activity_percentage >= 70 ? 'bg-success' : 
                                                            ($activity_percentage >= 50 ? 'bg-warning' : 'bg-danger'); 
                                                    ?>" 
                                                    role="progressbar" 
                                                    style="width: <?php echo $activity_percentage; ?>%"
                                                    aria-valuenow="<?php echo $activity_percentage; ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100"
                                                >
                                                    <?php echo $activity_percentage; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>