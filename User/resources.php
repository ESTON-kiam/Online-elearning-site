<?php
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';

if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header("Location: dashboard.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$course_id = $_GET['course_id'];


$enrollment_check_query = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?";
$stmt = $conn->prepare($enrollment_check_query);
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$enrollment_result = $stmt->get_result();

if ($enrollment_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}


$course_query = "SELECT c.title, c.description, i.name AS instructor_name 
                 FROM courses c 
                 JOIN course_instructors ci ON c.id = ci.course_id
                 JOIN instructors i ON ci.instructor_id = i.id
                 WHERE c.id = ?";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course_result = $stmt->get_result();
$course = $course_result->fetch_assoc();


$activities_query = "SELECT id, title, description, due_date, type 
                     FROM course_activities 
                     WHERE course_id = ? 
                     ORDER BY due_date";
$stmt = $conn->prepare($activities_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$activities_result = $stmt->get_result();


$notes_query = "SELECT id, title, file_path, upload_date 
                FROM course_notes 
                WHERE course_id = ? 
                ORDER BY upload_date DESC";
$stmt = $conn->prepare($notes_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$notes_result = $stmt->get_result();


$submitted_activities_query = "SELECT activity_id 
                                FROM student_submissions 
                                WHERE student_id = ? AND course_id = ?";
$stmt = $conn->prepare($submitted_activities_query);
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$submitted_activities_result = $stmt->get_result();
$submitted_activities = [];
while ($row = $submitted_activities_result->fetch_assoc()) {
    $submitted_activities[] = $row['activity_id'];
}


$grades_query = "SELECT 
                    a.title AS activity_title, 
                    ss.grade 
                    
                FROM student_submissions ss
                JOIN course_activities a ON ss.activity_id = a.id
                WHERE ss.student_id = ? AND ss.course_id = ?";
$stmt = $conn->prepare($grades_query);
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$grades_result = $stmt->get_result();

$total_points = 0;
$earned_points = 0;
$grade_details = [];

while ($grade_row = $grades_result->fetch_assoc()) {
    $total_points += $grade_row['max_points'];
    $earned_points += $grade_row['grade'];
    $grade_details[] = $grade_row;
}

$overall_percentage = $total_points > 0 ? round(($earned_points / $total_points) * 100, 2) : 0;


function calculateLetterGrade($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}
$letter_grade = calculateLetterGrade($overall_percentage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Course Resources</title>
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
        .course-header {
            background: linear-gradient(135deg, var(--primary-color), #6a5acd);
            color: white;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        .card-custom {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .grade-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .grade-percentage {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .grade-letter {
            font-size: 2rem;
            color: var(--secondary-color);
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
            <!-- Sidebar -->
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
                            <a href="#" class="nav-link active">
                                <i class="bi bi-journal-text me-2"></i>
                                Course Resources
                            </a>
                        </li>
                    </ul>
                    <div class="grade-card mt-auto mb-3">
                        <h4>Course Grade</h4>
                        <div class="grade-percentage"><?php echo $overall_percentage; ?>%</div>
                        <div class="grade-letter"><?php echo $letter_grade; ?></div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="course-header text-center">
                    <div class="container">
                        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p class="lead"><?php echo htmlspecialchars($course['description']); ?></p>
                        <p class="text-white-50">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card card-custom mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h2 class="mb-0">Course Activities</h2>
                                </div>
                                <div class="card-body p-0">
                                    <?php if ($activities_result->num_rows > 0): ?>
                                        <div class="list-group list-group-flush">
                                        <?php while ($activity = $activities_result->fetch_assoc()): ?>
                                            <div class="list-group-item list-group-item-action">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h5>
                                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                        <small class="text-muted">Due: <?php echo date('F j, Y', strtotime($activity['due_date'])); ?></small>
                                                    </div>
                                                    <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($activity['type']); ?></span>
                                                </div>
                                                <?php if (!in_array($activity['id'], $submitted_activities)): ?>
                                                    <a href="submit_activity.php?activity_id=<?php echo $activity['id']; ?>&course_id=<?php echo $course_id; ?>" 
                                                       class="btn btn-submit text-white btn-sm mt-2">Submit Activity</a>
                                                <?php else: ?>
                                                    <span class="badge bg-success mt-2">Submitted</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="p-3 text-center text-muted">No activities available for this course.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card card-custom mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h2 class="mb-0">Course Notes</h2>
                                </div>
                                <div class="card-body p-0">
                                    <?php if ($notes_result->num_rows > 0): ?>
                                        <div class="list-group list-group-flush">
                                        <?php while ($note = $notes_result->fetch_assoc()): ?>
                                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($note['title']); ?></h5>
                                                    <small class="text-muted">Uploaded: <?php echo date('F j, Y', strtotime($note['upload_date'])); ?></small>
                                                </div>
                                                <a href="<?php echo htmlspecialchars($note['file_path']); ?>" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i> View
                                                </a>
                                            </div>
                                        <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="p-3 text-center text-muted">No notes available for this course.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Grades Details Column -->
                        <div class="col-md-4">
                            <div class="card card-custom mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h2 class="mb-0">Grade Breakdown</h2>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($grade_details)): ?>
                                        <ul class="list-group">
                                        <?php foreach ($grade_details as $detail): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($detail['activity_title']); ?>
                                                <span class="badge bg-primary rounded-pill">
                                                    <?php echo $detail['grade'] . '/' . $detail['max_points']; ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                        </ul>
                                        <div class="mt-3 text-center">
                                            <h5>Total Grade</h5>
                                            <p class="lead text-primary">
                                                <?php echo $earned_points . '/' . $total_points; ?> 
                                                (<?php echo $overall_percentage; ?>%)
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center text-muted">No grades available yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>