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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Course Resources</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
   <link href="assets/css/resource.css" rel="stylesheet">
</head>
<body>
    <div class="course-header text-center">
        <div class="container">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($course['description']); ?></p>
            <p class="text-white-50">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
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
            </div>

            <div class="col-md-6">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>