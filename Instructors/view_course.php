<?php
session_name('instructor_session');
session_start();


if (!isset($_SESSION['instructor_id'])) {
    header("Location: http://localhost:8000/User/");
    exit();
}

require_once 'include/database.php';



if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];
$course_id = $_GET['id'];


$course_check_query = "SELECT c.id, c.title, c.description, c.category, c.YearOfStudent, 
                              ci.id as course_instructor_id
                       FROM courses c
                       JOIN course_instructors ci ON c.id = ci.course_id
                       WHERE c.id = ? AND ci.instructor_id = ?";
$stmt = $conn->prepare($course_check_query);
$stmt->bind_param("ii", $course_id, $instructor_id);
$stmt->execute();
$course_result = $stmt->get_result();

if ($course_result->num_rows == 0) {
    header("Location: dashboard.php");
    exit();
}

$course = $course_result->fetch_assoc();


$activities_query = "SELECT 
    id, 
    title, 
    description, 
    due_date, 
    type, 
    (SELECT COUNT(*) FROM student_submissions ss WHERE ss.activity_id = ca.id) as submission_count
FROM course_activities ca
WHERE course_id = ? 
ORDER BY due_date DESC";
$stmt = $conn->prepare($activities_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$activities_result = $stmt->get_result();


$notes_query = "SELECT 
    id, 
    title, 
    file_path, 
    upload_date,
    file_size,
    file_type
FROM course_notes 
WHERE course_id = ? 
ORDER BY upload_date DESC";
$stmt = $conn->prepare($notes_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$notes_result = $stmt->get_result();


$submissions_query = "SELECT 
    ss.id, 
    ss.activity_id, 
    a.title AS activity_title,
    s.username AS student_name,
    ss.submission_date,
    ss.grade,
    (CASE 
        WHEN ss.grade IS NULL THEN 'Ungraded'
        WHEN ss.grade >= 90 THEN 'Excellent'
        WHEN ss.grade >= 80 THEN 'Good'
        WHEN ss.grade >= 70 THEN 'Average'
        ELSE 'Needs Improvement'
    END) AS grade_status
FROM student_submissions ss
JOIN students s ON ss.student_id = s.id
JOIN course_activities a ON ss.activity_id = a.id
WHERE ss.course_id = ?
ORDER BY 
    CASE 
        WHEN ss.grade IS NULL THEN 0 
        ELSE 1 
    END, 
    ss.submission_date DESC
LIMIT 10";
$stmt = $conn->prepare($submissions_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$submissions_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Course Resources</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4a4a9e;
            --secondary-color: #6c757d;
            --light-bg: #f4f6f9;
            --transition-speed: 0.3s;
        }
        body {
            background-color: var(--light-bg);
            font-family: 'Inter', 'Arial', sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), #6a5acd);
            color: white;
            transition: width var(--transition-speed) ease;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .main-content {
            transition: margin-left var(--transition-speed) ease;
        }
        .card-custom {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .list-group-item {
            transition: background-color 0.3s ease;
        }
        .list-group-item:hover {
            background-color: #f8f9fa;
        }
        .course-header {
            background: linear-gradient(135deg, var(--primary-color), #6a5acd);
            color: white;
            padding: 2rem 0;
            margin-bottom: 1.5rem;
        }
        .badge-custom {
            padding: 0.5em 0.75em;
        }
        .btn-action {
            transition: all var(--transition-speed) ease;
        }
        .btn-action:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
           
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="d-flex flex-column h-100 p-3">
                    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <i class="bi bi-book-half me-2 fs-4"></i>
                        <span class="fs-4">Course Hub</span>
                    </a>
                    <hr>
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link text-white">
                                <i class="bi bi-house me-2"></i>Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link active">
                                <i class="bi bi-journal-text me-2"></i>Course Resources
                            </a>
                        </li>
                        <li>
                            <a href="course_analytics.php?course_id=<?php echo $course_id; ?>" class="nav-link text-white">
                                <i class="bi bi-graph-up me-2"></i>Course Analytics
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="text-white">
                        <small>Course Category: <?php echo htmlspecialchars($course['category']); ?></small>
                        <br>
                        <small>Year: <?php echo htmlspecialchars($course['YearOfStudent']); ?></small>
                    </div>
                </div>
            </div>

            
            <div class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="course-header text-center">
                    <div class="container">
                        <h1 class="display-4"><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p class="lead"><?php echo htmlspecialchars($course['description']); ?></p>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row">
                        
                        <div class="col-md-8">
                         
                            <div class="card card-custom mb-4">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><i class="bi bi-list-task me-2"></i>Course Activities</h2>
                                    <div>
                                        <a href="add_activity.php?course_id=<?php echo $course_id; ?>" 
                                           class="btn btn-light btn-sm btn-action" 
                                           data-bs-toggle="tooltip" 
                                           title="Create New Activity">
                                            <i class="bi bi-plus-circle me-1"></i>New Activity
                                        </a>
                                    </div>
                                </div>
                                
                            </div>

                            
                            <div class="card card-custom mb-4">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><i class="bi bi-folder me-2"></i>Course Notes</h2>
                                    <div>
                                        <a href="upload_notes.php?course_id=<?php echo $course_id; ?>" 
                                           class="btn btn-light btn-sm btn-action" 
                                           data-bs-toggle="tooltip" 
                                           title="Upload New Notes">
                                            <i class="bi bi-cloud-upload me-1"></i>Upload Notes
                                        </a>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                       
                        <div class="col-md-4">
                            <div class="card card-custom mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h2 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Submissions</h2>
                                </div>
                                
                                <div class="card-body">
                                    <?php if ($submissions_result->num_rows > 0): ?>
                                        <div class="list-group">
                                        <?php while ($submission = $submissions_result->fetch_assoc()): ?>
                                            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($submission['student_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($submission['activity_title']); ?></small>
                                                    <div class="text-muted small">
                                                        <?php echo date('F j, Y H:i', strtotime($submission['submission_date'])); ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="badge <?php 
                                                        echo $submission['grade'] === null ? 'bg-warning' : 
                                                        ($submission['grade_status'] === 'Excellent' ? 'bg-success' : 
                                                        ($submission['grade_status'] === 'Good' ? 'bg-info' : 
                                                        ($submission['grade_status'] === 'Average' ? 'bg-secondary' : 'bg-danger')))
                                                    ?> badge-custom">
                                                        <?php echo $submission['grade'] === null ? 'Ungraded' : $submission['grade_status']; ?>
                                                    </span>
                                                    <a href="grade_submission.php?submission_id=<?php echo $submission['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary mt-2 btn-action">
                                                        <i class="bi bi-check-circle me-1"></i>Grade
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center text-muted">No recent submissions.</p>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    
    <script>
       
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

       
        document.addEventListener('click', function(e) {
            if (e.target && e.target.matches('[data-confirm]')) {
                e.preventDefault();
                const href = e.target.getAttribute('href');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            }
        });
    </script>
</body>
</html>