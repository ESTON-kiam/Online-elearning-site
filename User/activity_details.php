<?php
session_name('student_session');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';


if (!isset($_GET['activity_id']) || !is_numeric($_GET['activity_id']) || 
    !isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header("Location: dashboard.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$activity_id = $_GET['activity_id'];
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


$activity_query = "SELECT * FROM course_activities WHERE id = ? AND course_id = ?";
$stmt = $conn->prepare($activity_query);
$stmt->bind_param("ii", $activity_id, $course_id);
$stmt->execute();
$activity_result = $stmt->get_result();
$activity = $activity_result->fetch_assoc();

if (!$activity) {
    header("Location: course_resources.php?course_id=" . $course_id);
    exit();
}


$submission_query = "SELECT * FROM student_submissions 
                     WHERE student_id = ? AND activity_id = ? AND course_id = ?";
$stmt = $conn->prepare($submission_query);
$stmt->bind_param("iii", $student_id, $activity_id, $course_id);
$stmt->execute();
$submission_result = $stmt->get_result();
$submission = $submission_result->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['submission_file'])) {
    $file = $_FILES['submission_file'];
    $upload_dir = 'uploads/submissions/';
    
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid('submission_') . '.' . $file_extension;
    $file_path = $upload_dir . $unique_filename;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        
        if ($submission) {
            $update_query = "UPDATE student_submissions 
                             SET file_path = ?, submission_date = NOW() 
                             WHERE student_id = ? AND activity_id = ? AND course_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("siii", $file_path, $student_id, $activity_id, $course_id);
            $stmt->execute();
        } else {
            
            $insert_query = "INSERT INTO student_submissions 
                             (student_id, activity_id, course_id, file_path, submission_date) 
                             VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiis", $student_id, $activity_id, $course_id, $file_path);
            $stmt->execute();
        }

        
        $stmt = $conn->prepare($submission_query);
        $stmt->bind_param("iii", $student_id, $activity_id, $course_id);
        $stmt->execute();
        $submission_result = $stmt->get_result();
        $submission = $submission_result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($activity['title']); ?> - Activity Details</title>
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
        .activity-header {
            background: linear-gradient(135deg, var(--primary-color), #6a5acd);
            color: white;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        .card-custom {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .submission-status {
            font-weight: bold;
        }
        .submission-status.submitted {
            color: green;
        }
        .submission-status.not-submitted {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="activity-header text-center">
            <div class="container">
                <h1><?php echo htmlspecialchars($activity['title']); ?></h1>
                <p class="lead"><?php echo htmlspecialchars($activity['description']); ?></p>
            </div>
        </div>

        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-primary text-white">
                            <h2 class="mb-0">Activity Details</h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($activity['type']); ?></p>
                                    <p><strong>Due Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($activity['due_date'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="submission-status <?php echo $submission ? 'submitted' : 'not-submitted'; ?>">
                                        <?php 
                                        echo $submission 
                                            ? 'Submitted on: ' . date('F j, Y, g:i a', strtotime($submission['submission_date']))
                                            : 'Not Yet Submitted'; 
                                        ?>
                                    </p>
                                    <?php if ($submission): ?>
                                        <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" 
                                           class="btn btn-outline-primary btn-sm" 
                                           target="_blank">
                                            <i class="bi bi-file-earmark-check"></i> View Submission
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (strtotime($activity['due_date']) >= time()): ?>
                            <hr>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="submission_file" class="form-label">
                                        Upload Submission
                                        <?php if ($activity['type'] == 'Assignment'): ?>
                                            (PDF, DOCX, or TXT files)
                                        <?php elseif ($activity['type'] == 'Quiz'): ?>
                                            (Only if allowed by instructor)
                                        <?php endif; ?>
                                    </label>
                                    <input class="form-control" type="file" id="submission_file" name="submission_file" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $submission ? 'Update Submission' : 'Submit Activity'; ?>
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                This activity's submission deadline has passed.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="course_resources.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Course Resources
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>