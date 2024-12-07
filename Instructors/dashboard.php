<?php
session_name('instructor_session');
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location:http://localhost:8000/instructors");
    exit();
}

require_once 'include/database.php';

$instructor_id = $_SESSION['instructor_id'];
$instructor_query = "SELECT * FROM instructors WHERE id = ?";
$stmt = $conn->prepare($instructor_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$instructor_result = $stmt->get_result();
$instructor = $instructor_result->fetch_assoc();

$courses_query = "SELECT c.id, c.title, c.description, c.YearOfStudent, c.price, c.is_active,
                         COUNT(DISTINCT ci.id) AS enrolled_students
                  FROM courses c
                  JOIN course_instructors ci ON c.id = ci.course_id
                  WHERE ci.instructor_id = ?
                  GROUP BY c.id";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$courses_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/css/dish.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
           
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky">
                    <div class="profile-header text-center">
                        <i class="fas fa-user-circle fa-3x mb-3"></i>
                        <h5><?php echo htmlspecialchars($instructor['name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($instructor['expertise'] ?? 'Instructor'); ?></p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-tachometer-alt me-2"></i>  Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="create_course.php">
                                <i class="fas fa-plus-circle me-2"></i> Create Course
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="edit_profile.php">
                                <i class="fas fa-user-edit me-2"></i> Edit Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="analytics.php">
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
                    <h1 class="h2">Instructor Dashboard</h1>
                </div>

                
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="h3">Courses Allocated TO</h2>
                            <a href="create_course.php" class="btn btn-custom">
                                <i class="fas fa-plus me-2"></i> Create New Course
                            </a>
                        </div>
                        
                        <?php if ($courses_result->num_rows > 0): ?>
                            <?php while($course = $courses_result->fetch_assoc()): ?>
                                <div class="course-card mb-4 p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="m-0"><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <span class="badge <?php echo $course['is_active'] ? 'bg-success' : 'bg-warning'; ?> badge-status">
                                            <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <p class="text-muted"><?php echo htmlspecialchars($course['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($course['YearOfStudent']); ?> Year</span>
                                          
                                        </div>
                                        <div class="text-muted">
                                            <i class="fas fa-users me-2"></i> 
                                            Students Enrolled: <?php echo intval($course['enrolled_students']); ?>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex">
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-custom me-2">
                                            <i class="fas fa-edit me-1"></i> Edit Course
                                        </a>
                                        <a href="course_details.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You haven't created any courses yet. 
                                <a href="create_course.php" class="alert-link">Create your first course</a>
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